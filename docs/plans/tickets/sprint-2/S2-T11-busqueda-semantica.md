# Plan: S2-T11 — Servicio de Búsqueda Semántica por Similitud

**Ticket:** S2-T11
**Tipo:** feat
**Rama:** `feat/S2-T11-busqueda-semantica`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T10 (Pipeline de Embeddings)

---

## Contexto

Este servicio implementa búsquedas por similitud de cosenos usando pgvector, siendo el motor de sugerencias inteligentes del sistema. Se utiliza para:

- **Matriz de Alineación**: Sugerir correspondencias entre niveles de la cascada
- **MIR (Sprint 4)**: Sugerir alineación automática con Líneas de Acción del PED

**Conceptos Clave:**

| Concepto         | Descripción                                                                       |
| ---------------- | --------------------------------------------------------------------------------- |
| Distancia Coseno | Medida de similitud entre vectores (0 = idénticos, 2 = opuestos)                  |
| Similitud        | `1 - distancia` → Rango 0 a 1 (1 = máxima similitud)                              |
| HNSW             | Índice optimizado para búsquedas vectoriales (Hierarchical Navigable Small World) |
| Operador `<=>`   | Operador de pgvector para distancia coseno                                        |

**Esta versión implementa:**
- Servicio en `App\Services\Embeddings\`
- DTO en `App\DTOs\`
- Configuración unificada en `config/embedding.php`
- Índices HNSW optimizados

---

## Pre-requisitos

- S2-T10 completado (pipeline de embeddings con registros generados)
- Extensión pgvector habilitada (S0-T2)
- Al menos algunos registros con embeddings en las tablas

---

## Pasos

### 1. Crear DTO para Resultados de Similitud

```bash
mkdir -p app/DTOs
```

Crear `app/DTOs/SimilarityResult.php`:

```php
<?php

namespace App\DTOs;

use Illuminate\Database\Eloquent\Model;

readonly class SimilarityResult
{
    /**
     * @param Model $model Modelo encontrado
     * @param float $score Score de similitud (0-1, donde 1 es máxima similitud)
     * @param float $distance Distancia coseno (0-2, donde 0 es idéntico)
     */
    public function __construct(
        public Model $model,
        public float $score,
        public float $distance,
    ) {}

    /**
     * Crea una instancia desde un resultado de query.
     */
    public static function fromQuery(object $result, string $modelClass): self
    {
        /** @var Model $model */
        $model = (new $modelClass())->newInstance();
        
        $attributes = (array) $result;
        unset($attributes['score'], $attributes['distance'], $attributes['embedding']);

        $model->setRawAttributes($attributes, true);
        $model->exists = true;

        return new self(
            model: $model,
            score: (float) $result->score,
            distance: (float) $result->distance,
        );
    }

    /**
     * Retorna el porcentaje de similitud formateado.
     */
    public function getPercentageAttribute(): string
    {
        return round($this->score * 100, 1) . '%';
    }

    /**
     * Indica si el resultado supera un umbral de calidad.
     */
    public function isHighQuality(float $threshold = 0.85): bool
    {
        return $this->score >= $threshold;
    }
}
```

---

### 2. Actualizar Archivo de Configuración (Unificado)

Actualizar `config/embedding.php` agregando las claves de búsqueda:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    
    'api_key' => env('EMBEDDING_API_KEY'),
    'api_url' => env('EMBEDDING_API_URL', 'https://api.openai.com/v1/embeddings'),
    'model' => env('EMBEDDING_MODEL', 'text-embedding-ada-002'),
    'dimension' => env('EMBEDDING_DIMENSION', 1536),
    
    'rate_limit' => env('EMBEDDING_RATE_LIMIT', 60),
    'timeout' => env('EMBEDDING_TIMEOUT', 30),
    
    'queue' => env('EMBEDDING_QUEUE', 'embeddings'),
    'tries' => env('EMBEDDING_JOB_TRIES', 3),
    'backoff' => [10, 60, 300],
    'timeout_job' => env('EMBEDDING_JOB_TIMEOUT', 60),
    
    'max_tokens' => env('EMBEDDING_MAX_TOKENS', 8000),
    
    'observers_enabled' => env('EMBEDDING_OBSERVERS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Semantic Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el servicio de búsqueda semántica.
    |
    */

    // Umbral mínimo de similitud para considerar un resultado relevante (0-1)
    'similarity_threshold' => env('EMBEDDING_SIMILARITY_THRESHOLD', 0.7),

    // Número máximo de resultados por defecto
    'max_results' => env('EMBEDDING_MAX_RESULTS', 5),

    /*
    |--------------------------------------------------------------------------
    | HNSW Index Configuration
    |--------------------------------------------------------------------------
    |
    | Parámetros para índices Hierarchical Navigable Small World (HNSW).
    | Optimizados para búsquedas vectoriales de alta velocidad.
    |
    */

    // Parámetro M: número de conexiones bidireccionales por nodo
    // Valores más altos = mejor recall, más memoria
    'hnsw_m' => env('EMBEDDING_HNSW_M', 16),

    // Parámetro ef_construction: tamaño de la lista de candidatos dinámicos
    // Valores más altos = mejor calidad de índice, construcción más lenta
    'hnsw_ef_construction' => env('EMBEDDING_HNSW_EF_CONSTRUCTION', 64),

    // Parámetro ef_search: tamaño de la lista de candidatos durante búsqueda
    // Valores más altos = mejor recall, búsqueda más lenta
    'hnsw_ef_search' => env('EMBEDDING_HNSW_EF_SEARCH', 40),
];
```

---

### 3. Crear Servicio de Búsqueda Semántica

Crear `app/Services/Embeddings/SemanticSearchService.php`:

```php
<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use App\DTOs\SimilarityResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SemanticSearchService
{
    protected EmbeddingServiceInterface $embeddingService;
    protected float $defaultThreshold;
    protected int $defaultLimit;
    protected int $hnswEfSearch;

    /**
     * Modelos que tienen columna embedding y son buscables.
     */
    protected array $searchableModels = [
        \App\Models\OdsObjetivo::class,
        \App\Models\OdsMeta::class,
        \App\Models\PndEje::class,
        \App\Models\PndObjetivo::class,
        \App\Models\PndEstrategia::class,
        \App\Models\PedPlan::class,
        \App\Models\PedEje::class,
        \App\Models\PedTema::class,
        \App\Models\PedObjetivoEstrategico::class,
        \App\Models\PedEstrategia::class,
        \App\Models\PedLineaAccion::class,
        \App\Models\ProgramaDerivadoObjetivo::class,
    ];

    public function __construct(EmbeddingServiceInterface $embeddingService)
    {
        $this->embeddingService = $embeddingService;
        $this->defaultThreshold = config('embedding.similarity_threshold', 0.7);
        $this->defaultLimit = config('embedding.max_results', 5);
        $this->hnswEfSearch = config('embedding.hnsw_ef_search', 40);
    }

    /**
     * Busca registros similares al texto proporcionado.
     *
     * @param string $text Texto de búsqueda
     * @param string $modelClass Clase del modelo donde buscar
     * @param int|null $limit Número máximo de resultados
     * @param float|null $threshold Umbral mínimo de similitud (0-1)
     * @return Collection<SimilarityResult>
     */
    public function findSimilar(
        string $text,
        string $modelClass,
        ?int $limit = null,
        ?float $threshold = null
    ): Collection {
        $limit = $limit ?? $this->defaultLimit;
        $threshold = $threshold ?? $this->defaultThreshold;

        $this->validateModel($modelClass);
        $this->validateThreshold($threshold);

        try {
            // Generar embedding del texto de búsqueda
            $embedding = $this->embeddingService->generate($text);
            $embeddingString = '[' . implode(',', $embedding) . ']';

            /** @var Model $modelInstance */
            $modelInstance = new $modelClass();
            $tableName = $modelInstance->getTable();

            // Configurar ef_search para esta sesión (optimización HNSW)
            DB::statement("SET hnsw.ef_search = {$this->hnswEfSearch}");

            // Ejecutar búsqueda vectorial
            // <=> es distancia coseno: 0 = idéntico, 2 = opuesto
            // score = 1 - distancia (para que 1 sea máxima similitud)
            $results = DB::select("
                SELECT
                    *,
                    embedding <=> ?::vector AS distance,
                    1 - (embedding <=> ?::vector) AS score
                FROM {$tableName}
                WHERE embedding IS NOT NULL
                  AND 1 - (embedding <=> ?::vector) >= ?
                ORDER BY embedding <=> ?::vector
                LIMIT ?
            ", [$embeddingString, $embeddingString, $embeddingString, $threshold, $embeddingString, $limit]);

            return collect($results)->map(
                fn($result) => SimilarityResult::fromQuery($result, $modelClass)
            );

        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'text_length' => strlen($text),
                'model_class' => $modelClass,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Busca en múltiples modelos simultáneamente.
     */
    public function findSimilarInMultiple(
        string $text,
        array $modelClasses,
        int $limitPerModel = 3,
        ?float $threshold = null
    ): Collection {
        $allResults = collect();

        foreach ($modelClasses as $modelClass) {
            $results = $this->findSimilar($text, $modelClass, $limitPerModel, $threshold);
            $allResults = $allResults->merge($results);
        }

        return $allResults->sortByDesc('score')->values();
    }

    /**
     * Busca la línea de acción del PED más similar al texto.
     */
    public function findSimilarLineaAccion(string $text, int $limit = 5, float $threshold = 0.7): Collection
    {
        return $this->findSimilar($text, \App\Models\PedLineaAccion::class, $limit, $threshold);
    }

    /**
     * Busca objetivos ODS similares al texto.
     */
    public function findSimilarOds(string $text, int $limit = 5, float $threshold = 0.7): Collection
    {
        $metas = $this->findSimilar($text, \App\Models\OdsMeta::class, $limit, $threshold);
        $objetivos = $this->findSimilar($text, \App\Models\OdsObjetivo::class, $limit, $threshold);

        return $metas->merge($objetivos)->sortByDesc('score')->take($limit)->values();
    }

    /**
     * Busca en toda la cascada de planes.
     */
    public function searchInCascade(string $text, int $totalLimit = 10, float $threshold = 0.7): Collection
    {
        return $this->findSimilarInMultiple(
            $text,
            $this->searchableModels,
            3,
            $threshold
        )->take($totalLimit);
    }

    /**
     * Retorna los modelos buscables configurados.
     */
    public function getSearchableModels(): array
    {
        return $this->searchableModels;
    }

    /**
     * Verifica si un modelo es buscable.
     */
    public function isSearchable(string $modelClass): bool
    {
        return in_array($modelClass, $this->searchableModels);
    }

    /**
     * Valida que el modelo sea buscable.
     */
    protected function validateModel(string $modelClass): void
    {
        if (!in_array($modelClass, $this->searchableModels)) {
            throw new \InvalidArgumentException("Model {$modelClass} is not searchable");
        }
    }

    /**
     * Valida el umbral de similitud.
     */
    protected function validateThreshold(float $threshold): void
    {
        if ($threshold < 0 || $threshold > 1) {
            throw new \InvalidArgumentException('Threshold must be between 0 and 1');
        }
    }
}
```

---

### 4. Registrar Servicio en Service Container

Editar `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Contracts\EmbeddingServiceInterface;
use App\Services\Embeddings\EmbeddingService;
use App\Services\Embeddings\SemanticSearchService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmbeddingServiceInterface::class, function ($app) {
            return new EmbeddingService();
        });

        $this->app->singleton(SemanticSearchService::class, function ($app) {
            return new SemanticSearchService($app->make(EmbeddingServiceInterface::class));
        });
    }

    public function boot(): void
    {
        // ... código existente
    }
}
```

---

### 5. Crear Migración para Índices HNSW

```bash
sail artisan make:migration create_hnsw_indexes_for_embeddings
```

Editar el archivo generado:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $m = config('embedding.hnsw_m', 16);
        $efConstruction = config('embedding.hnsw_ef_construction', 64);

        // ODS
        DB::statement("CREATE INDEX IF NOT EXISTS ods_objetivos_embedding_idx ON ods_objetivos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ods_metas_embedding_idx ON ods_metas USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");

        // PND
        DB::statement("CREATE INDEX IF NOT EXISTS pnd_ejes_embedding_idx ON pnd_ejes USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS pnd_objetivos_embedding_idx ON pnd_objetivos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS pnd_estrategias_embedding_idx ON pnd_estrategias USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");

        // PED
        DB::statement("CREATE INDEX IF NOT EXISTS ped_planes_embedding_idx ON ped_planes USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_ejes_embedding_idx ON ped_ejes USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_temas_embedding_idx ON ped_temas USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_objetivos_estrategicos_embedding_idx ON ped_objetivos_estrategicos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_estrategias_embedding_idx ON ped_estrategias USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_lineas_accion_embedding_idx ON ped_lineas_accion USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");

        // Programas Derivados
        DB::statement("CREATE INDEX IF NOT EXISTS programas_derivados_objetivos_embedding_idx ON programas_derivados_objetivos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ODS
        DB::statement('DROP INDEX IF EXISTS ods_objetivos_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ods_metas_embedding_idx');

        // PND
        DB::statement('DROP INDEX IF EXISTS pnd_ejes_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS pnd_objetivos_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS pnd_estrategias_embedding_idx');

        // PED
        DB::statement('DROP INDEX IF EXISTS ped_planes_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_ejes_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_temas_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_objetivos_estrategicos_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_estrategias_embedding_idx');
        DB::statement('DROP INDEX IF EXISTS ped_lineas_accion_embedding_idx');

        // Programas Derivados
        DB::statement('DROP INDEX IF EXISTS programas_derivados_objetivos_embedding_idx');
    }
};
```

---

### 6. Crear Tests Unitarios

```bash
sail artisan make:test Unit/Embeddings/SemanticSearchServiceTest --unit
```

Editar `tests/Unit/Embeddings/SemanticSearchServiceTest.php`:

```php
<?php

namespace Tests\Unit\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use App\DTOs\SimilarityResult;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedLineaAccion;
use App\Services\Embeddings\SemanticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SemanticSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'embedding.similarity_threshold' => 0.7,
            'embedding.max_results' => 5,
            'embedding.hnsw_ef_search' => 40,
        ]);
    }

    // ============================================
    // Tests de Búsqueda Básica
    // ============================================

    public function test_find_similar_retorna_resultados_ordenados_por_score(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $ods = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);

        $meta1 = OdsMeta::create([
            'ods_objetivo_id' => $ods->id,
            'clave' => '1.1',
            'descripcion' => 'Reducir la pobreza extrema',
        ]);

        $meta2 = OdsMeta::create([
            'ods_objetivo_id' => $ods->id,
            'clave' => '1.2',
            'descripcion' => 'Mejorar la educación',
        ]);

        // Insertar embeddings simulados
        DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$meta1->id]);
        DB::statement("UPDATE ods_metas SET embedding = '[0.4,0.4,0.4]'::vector || ARRAY_FILL(0.4, ARRAY[1533])::vector WHERE id = ?", [$meta2->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('reducir pobreza', OdsMeta::class);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(SimilarityResult::class, $results->first());
        $this->assertGreaterThanOrEqual($results->last()->score, $results->first()->score);
    }

    public function test_find_similar_respeta_limite(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $objetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);
        
        for ($i = 1; $i <= 10; $i++) {
            $meta = OdsMeta::create([
                'ods_objetivo_id' => $objetivo->id,
                'clave' => "1.{$i}",
                'descripcion' => "Meta {$i}",
            ]);
            DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$meta->id]);
        }

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('test', OdsMeta::class, limit: 3);

        $this->assertCount(3, $results);
    }

    public function test_find_similar_filtra_por_umbral(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $objetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);

        $meta1 = OdsMeta::create([
            'ods_objetivo_id' => $objetivo->id,
            'clave' => '1.1',
            'descripcion' => 'Alta similitud',
        ]);
        DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$meta1->id]);

        $meta2 = OdsMeta::create([
            'ods_objetivo_id' => $objetivo->id,
            'clave' => '1.2',
            'descripcion' => 'Baja similitud',
        ]);
        DB::statement("UPDATE ods_metas SET embedding = '[-0.9,-0.9,-0.9]'::vector || ARRAY_FILL(-0.9, ARRAY[1533])::vector WHERE id = ?", [$meta2->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('test', OdsMeta::class, threshold: 0.9);

        $this->assertLessThanOrEqual(1, $results->count());
    }

    // ============================================
    // Tests de Validación
    // ============================================

    public function test_lanza_excepcion_si_modelo_no_es_buscable(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not searchable');

        $service->findSimilar('test', \App\Models\User::class);
    }

    public function test_lanza_excepcion_si_threshold_invalido(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Threshold must be between 0 and 1');

        $service->findSimilar('test', OdsMeta::class, threshold: 1.5);
    }

    // ============================================
    // Tests de DTO
    // ============================================

    public function test_dto_contiene_propiedades_correctas(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $objetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);
        $meta = OdsMeta::create([
            'ods_objetivo_id' => $objetivo->id,
            'clave' => '1.1',
            'descripcion' => 'Test descripcion',
        ]);
        DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$meta->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('test', OdsMeta::class);

        $first = $results->first();

        $this->assertInstanceOf(OdsMeta::class, $first->model);
        $this->assertIsFloat($first->score);
        $this->assertIsFloat($first->distance);
        $this->assertGreaterThanOrEqual(0, $first->score);
        $this->assertLessThanOrEqual(1, $first->score);
    }

    public function test_dto_get_percentage_attribute(): void
    {
        $result = new SimilarityResult(
            model: new OdsMeta(),
            score: 0.856,
            distance: 0.144
        );

        $this->assertEquals('85.6%', $result->percentage);
    }

    public function test_dto_is_high_quality(): void
    {
        $highQuality = new SimilarityResult(
            model: new OdsMeta(),
            score: 0.90,
            distance: 0.10
        );

        $lowQuality = new SimilarityResult(
            model: new OdsMeta(),
            score: 0.80,
            distance: 0.20
        );

        $this->assertTrue($highQuality->isHighQuality());
        $this->assertFalse($lowQuality->isHighQuality());
        $this->assertTrue($lowQuality->isHighQuality(0.75));
    }

    // ============================================
    // Tests de Manejo de Errores
    // ============================================

    public function test_retorna_coleccion_vacia_si_embedding_service_falla(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('test', OdsMeta::class);

        $this->assertTrue($results->isEmpty());
    }

    // ============================================
    // Tests de Utilidades
    // ============================================

    public function test_get_searchable_models(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $models = $service->getSearchableModels();

        $this->assertIsArray($models);
        $this->assertContains(OdsMeta::class, $models);
        $this->assertContains(PedLineaAccion::class, $models);
    }

    public function test_is_searchable(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $this->assertTrue($service->isSearchable(OdsMeta::class));
        $this->assertFalse($service->isSearchable(\App\Models\User::class));
    }
}
```

---

### 7. Crear Test de Integración con Índices

```bash
sail artisan make:test Feature/Embeddings/HnswIndexesTest
```

Editar `tests/Feature/Embeddings/HnswIndexesTest.php`:

```php
<?php

namespace Tests\Feature\Embeddings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HnswIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_indices_hnsw_creados_correctamente(): void
    {
        $this->artisan('migrate');

        $this->assertIndexExists('ods_objetivos_embedding_idx');
        $this->assertIndexExists('ods_metas_embedding_idx');
        $this->assertIndexExists('pnd_ejes_embedding_idx');
        $this->assertIndexExists('pnd_objetivos_embedding_idx');
        $this->assertIndexExists('pnd_estrategias_embedding_idx');
        $this->assertIndexExists('ped_planes_embedding_idx');
        $this->assertIndexExists('ped_ejes_embedding_idx');
        $this->assertIndexExists('ped_temas_embedding_idx');
        $this->assertIndexExists('ped_objetivos_estrategicos_embedding_idx');
        $this->assertIndexExists('ped_estrategias_embedding_idx');
        $this->assertIndexExists('ped_lineas_accion_embedding_idx');
        $this->assertIndexExists('programas_derivados_objetivos_embedding_idx');
    }

    public function test_indices_usan_hnsw(): void
    {
        $this->artisan('migrate');

        $result = DB::selectOne("
            SELECT am.amname AS index_method
            FROM pg_index i
            JOIN pg_class c ON c.oid = i.indexrelid
            JOIN pg_am am ON am.oid = c.relam
            WHERE c.relname = 'ods_objetivos_embedding_idx'
        ");

        $this->assertEquals('hnsw', $result->index_method);
    }

    public function test_indices_tienen_parametros_correctos(): void
    {
        $this->artisan('migrate');

        $result = DB::selectOne("
            SELECT pg_catalog.pg_get_indexdef(c.oid) AS indexdef
            FROM pg_class c
            WHERE c.relname = 'ods_objetivos_embedding_idx'
        ");

        $this->assertStringContainsString('hnsw', $result->indexdef);
        $this->assertStringContainsString('vector_cosine_ops', $result->indexdef);
    }

    public function test_migracion_rollback_elimina_indices(): void
    {
        $this->artisan('migrate');
        $this->artisan('migrate:rollback', ['--step' => 1]);

        $this->assertIndexNotExists('ods_objetivos_embedding_idx');
    }

    protected function assertIndexExists(string $indexName): void
    {
        $result = DB::selectOne("
            SELECT 1
            FROM pg_class
            WHERE relname = ?
              AND relkind = 'i'
        ", [$indexName]);

        $this->assertNotNull($result, "Index {$indexName} does not exist");
    }

    protected function assertIndexNotExists(string $indexName): void
    {
        $result = DB::selectOne("
            SELECT 1
            FROM pg_class
            WHERE relname = ?
              AND relkind = 'i'
        ", [$indexName]);

        $this->assertNull($result, "Index {$indexName} should not exist");
    }
}
```

---

### 8. Actualizar Variables de Entorno

Editar `.env.example`:

```ini
# ============================================
# SEMANTIC SEARCH CONFIGURATION
# ============================================

# Search Thresholds
EMBEDDING_SIMILARITY_THRESHOLD=0.7
EMBEDDING_MAX_RESULTS=5

# HNSW Index Parameters
EMBEDDING_HNSW_M=16
EMBEDDING_HNSW_EF_CONSTRUCTION=64
EMBEDDING_HNSW_EF_SEARCH=40
```

---

### 9. Ejecutar y Verificar

```bash
# Ejecutar migración de índices
sail artisan migrate

# Verificar índices creados
sail shell
psql -U sail -d laravel -c "\di *embedding*"
exit

# Ejecutar tests
sail artisan test --filter SemanticSearchServiceTest
sail artisan test --filter HnswIndexesTest
```

---

## Criterios de Aceptación

- [ ] `SemanticSearchService` en namespace `App\Services\Embeddings\`
- [ ] Servicio registrado en `AppServiceProvider`
- [ ] DTO `SimilarityResult` en `App\DTOs\`
- [ ] Configuración unificada en `config/embedding.php`
- [ ] Método `findSimilar()` funciona contra todas las tablas con embeddings
- [ ] Migración crea índices HNSW con `vector_cosine_ops`
- [ ] Verificar índices con `\di` muestra `hnsw`
- [ ] Umbral mínimo configurable (default 0.7)
- [ ] Límite configurable (default 5)
- [ ] Test: búsqueda retorna resultados ordenados por score
- [ ] Test: modelo no buscable lanza `InvalidArgumentException`
- [ ] Test: threshold inválido lanza `InvalidArgumentException`

---

---

## Notas

### Parámetros HNSW

| Parámetro         | Default | Efecto                                                                     |
| ----------------- | ------- | -------------------------------------------------------------------------- |
| `m`               | 16      | Conexiones por nodo. Mayor = mejor recall, más memoria                     |
| `ef_construction` | 64      | Calidad durante construcción. Mayor = mejor índice, construcción más lenta |
| `ef_search`       | 40      | Lista de candidatos en búsqueda. Mayor = mejor recall, búsqueda más lenta  |

### Operadores pgvector

| Operador | Descripción             | Uso                                  |
| -------- | ----------------------- | ------------------------------------ |
| `<=>`    | Distancia coseno        | Recomendado para embeddings de texto |
| `<->`    | Distancia L2            | Alternativa para otros casos         |
| `<#>`    | Producto punto negativo | Para embeddings normalizados         |

### Performance

```sql
-- Verificar que el índice se está usando
EXPLAIN ANALYZE
SELECT * FROM ods_metas
ORDER BY embedding <=> '[0.5,...]'::vector
LIMIT 5;

-- Debe mostrar "Index Scan" en lugar de "Seq Scan"
```

### Configuración en Producción

```bash
# Variables de entorno recomendadas para producción
EMBEDDING_SIMILARITY_THRESHOLD=0.75
EMBEDDING_MAX_RESULTS=10
EMBEDDING_HNSW_M=24          # Más conexiones = mejor recall
EMBEDDING_HNSW_EF_CONSTRUCTION=100  # Mejor calidad de índice
EMBEDDING_HNSW_EF_SEARCH=60   # Mejor recall en búsqueda
```

---
