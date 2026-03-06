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
        $model = new $modelClass();

        // Llenar el modelo con los atributos del resultado
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

### 2. Crear Archivo de Configuración

Crear `config/embedding.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Servicio de Embeddings
    |--------------------------------------------------------------------------
    */

    'api_key' => env('EMBEDDING_API_KEY'),
    'api_url' => env('EMBEDDING_API_URL', 'https://api.openai.com/v1/embeddings'),
    'model' => env('EMBEDDING_MODEL', 'text-embedding-ada-002'),
    'dimension' => env('EMBEDDING_DIMENSION', 1536),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Búsqueda Semántica
    |--------------------------------------------------------------------------
    */

    // Umbral mínimo de similitud para considerar un resultado relevante
    'similarity_threshold' => env('EMBEDDING_SIMILARITY_THRESHOLD', 0.7),

    // Número máximo de resultados por defecto
    'max_results' => env('EMBEDDING_MAX_RESULTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Índices HNSW
    |--------------------------------------------------------------------------
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

Crear `app/Services/SemanticSearchService.php`:

```php
<?php

namespace App\Services;

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
     * @param int $limit Número máximo de resultados
     * @param float $threshold Umbral mínimo de similitud (0-1)
     * @return Collection<SimilarityResult>
     */
    public function findSimilar(
        string $text,
        string $modelClass,
        int $limit = null,
        float $threshold = null
    ): Collection {
        $limit = $limit ?? $this->defaultLimit;
        $threshold = $threshold ?? $this->defaultThreshold;

        // Validar que el modelo es buscable
        if (!in_array($modelClass, $this->searchableModels)) {
            throw new \InvalidArgumentException("Model {$modelClass} is not searchable");
        }

        // Validar threshold
        if ($threshold < 0 || $threshold > 1) {
            throw new \InvalidArgumentException('Threshold must be between 0 and 1');
        }

        try {
            // Generar embedding del texto de búsqueda
            $embedding = $this->embeddingService->generate($text);
            $embeddingString = '[' . implode(',', $embedding) . ']';

            // Obtener tabla del modelo
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

            // Convertir resultados a DTOs
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
     *
     * @param string $text Texto de búsqueda
     * @param array $modelClasses Clases de modelos donde buscar
     * @param int $limitPerModel Límite de resultados por modelo
     * @param float $threshold Umbral mínimo de similitud
     * @return Collection<SimilarityResult> Resultados combinados y ordenados por score
     */
    public function findSimilarInMultiple(
        string $text,
        array $modelClasses,
        int $limitPerModel = 3,
        float $threshold = null
    ): Collection {
        $allResults = collect();

        foreach ($modelClasses as $modelClass) {
            $results = $this->findSimilar($text, $modelClass, $limitPerModel, $threshold);
            $allResults = $allResults->merge($results);
        }

        // Ordenar por score descendente
        return $allResults->sortByDesc('score')->values();
    }

    /**
     * Busca la línea de acción del PED más similar al texto.
     * Método de conveniencia para alineación de MIR.
     */
    public function findSimilarLineaAccion(string $text, int $limit = 5, float $threshold = 0.7): Collection
    {
        return $this->findSimilar($text, \App\Models\PedLineaAccion::class, $limit, $threshold);
    }

    /**
     * Busca objetivos ODS similares al texto.
     * Método de conveniencia para alineación.
     */
    public function findSimilarOds(string $text, int $limit = 5, float $threshold = 0.7): Collection
    {
        $metas = $this->findSimilar($text, \App\Models\OdsMeta::class, $limit, $threshold);
        $objetivos = $this->findSimilar($text, \App\Models\OdsObjetivo::class, $limit, $threshold);

        return $metas->merge($objetivos)->sortByDesc('score')->take($limit)->values();
    }

    /**
     * Busca en toda la cascada de planes.
     * Retorna los resultados más relevantes de cualquier nivel.
     */
    public function searchInCascade(string $text, int $totalLimit = 10, float $threshold = 0.7): Collection
    {
        return $this->findSimilarInMultiple(
            $text,
            $this->searchableModels,
            3, // 3 resultados por modelo
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
}
```

---

### 4. Registrar Servicio en Service Container

Editar `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Contracts\EmbeddingServiceInterface;
use App\Services\EmbeddingService;
use App\Services\SemanticSearchService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Embedding Service (singleton)
        $this->app->singleton(EmbeddingServiceInterface::class, function ($app) {
            return new EmbeddingService();
        });

        // Semantic Search Service (singleton - comparte embedding service)
        $this->app->singleton(SemanticSearchService::class, function ($app) {
            return new SemanticSearchService($app->make(EmbeddingServiceInterface::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ... código existente (observers, gates, etc.)
    }
}
```

---

### 5. Crear Migración para Índices HNSW

```bash
sail artisan make:migration create_hnsw_indexes_for_embeddings
```

Editar el archivo generado en `database/migrations/`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Índices HNSW para búsquedas vectoriales.
     *
     * HNSW (Hierarchical Navigable Small World) es óptimo para:
     * - Datasets < 1M registros
     * - Alto recall (encontrar los vecinos más cercanos)
     * - Consultas de baja latencia
     *
     * Parámetros:
     * - m: Conexiones por nodo (default 16). Mayor = mejor recall, más memoria
     * - ef_construction: Calidad del índice durante construcción (default 64)
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
        DB::statement("CREATE INDEX IF NOT EXISTS ped_ejes_embedding_idx ON ped_ejes USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_temas_embedding_idx ON ped_temas USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_objetivos_estrategicos_embedding_idx ON ped_objetivos_estrategicos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_estrategias_embedding_idx ON ped_estrategias USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
        DB::statement("CREATE INDEX IF NOT EXISTS ped_lineas_accion_embedding_idx ON ped_lineas_accion USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");

        // Programas Derivados
        DB::statement("CREATE INDEX IF NOT EXISTS programas_derivados_objetivos_embedding_idx ON programas_derivados_objetivos USING hnsw (embedding vector_cosine_ops) WITH (m = {$m}, ef_construction = {$efConstruction})");
    }

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
sail artisan make:test SemanticSearchServiceTest --unit
```

Editar `tests/Unit/SemanticSearchServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Contracts\EmbeddingServiceInterface;
use App\DTOs\SimilarityResult;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedLineaAccion;
use App\Services\SemanticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SemanticSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar valores de prueba
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
        // Mock del embedding service
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        // Crear registros con embeddings simulados
        $ods1 = OdsMeta::create([
            'ods_objetivo_id' => OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test'])->id,
            'clave' => '1.1',
            'descripcion' => 'Reducir la pobreza extrema',
        ]);

        $ods2 = OdsMeta::create([
            'ods_objetivo_id' => OdsObjetivo::first()->id,
            'clave' => '1.2',
            'descripcion' => 'Reducir la pobreza en todas sus formas',
        ]);

        // Insertar embeddings manualmente (simulados)
        \DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$ods1->id]);
        \DB::statement("UPDATE ods_metas SET embedding = '[0.4,0.4,0.4]'::vector || ARRAY_FILL(0.4, ARRAY[1533])::vector WHERE id = ?", [$ods2->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilar('reducir pobreza', OdsMeta::class);

        $this->assertCount(2, $results);
        $this->assertInstanceOf(SimilarityResult::class, $results->first());

        // Verificar orden descendente por score
        $this->assertGreaterThanOrEqual($results->last()->score, $results->first()->score);
    }

    public function test_find_similar_respetal_limite(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        // Crear múltiples registros
        $objetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);
        for ($i = 1; $i <= 10; $i++) {
            $meta = OdsMeta::create([
                'ods_objetivo_id' => $objetivo->id,
                'clave' => "1.{$i}",
                'descripcion' => "Meta {$i}",
            ]);
            \DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$meta->id]);
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
        \DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$meta1->id]);

        $meta2 = OdsMeta::create([
            'ods_objetivo_id' => $objetivo->id,
            'clave' => '1.2',
            'descripcion' => 'Baja similitud',
        ]);
        // Vector muy diferente
        \DB::statement("UPDATE ods_metas SET embedding = '[-0.9,-0.9,-0.9]'::vector || ARRAY_FILL(-0.9, ARRAY[1533])::vector WHERE id = ?", [$meta2->id]);

        $service = new SemanticSearchService($mockEmbedding);

        // Con umbral alto, solo debe retornar el primero
        $results = $service->findSimilar('test', OdsMeta::class, threshold: 0.9);

        $this->assertLessThanOrEqual(1, $results->count());
    }

    public function test_busqueda_con_umbral_alto_retorna_coleccion_vacia(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $objetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);
        $meta = OdsMeta::create([
            'ods_objetivo_id' => $objetivo->id,
            'clave' => '1.1',
            'descripcion' => 'Test',
        ]);
        \DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$meta->id]);

        $service = new SemanticSearchService($mockEmbedding);

        // Umbral muy alto (0.99)
        $results = $service->findSimilar('test', OdsMeta::class, threshold: 0.99);

        $this->assertTrue($results->isEmpty());
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

    public function test_lanza_excepcion_si_threshold_negativo(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $service = new SemanticSearchService($mockEmbedding);

        $this->expectException(\InvalidArgumentException::class);

        $service->findSimilar('test', OdsMeta::class, threshold: -0.5);
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
        \DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$meta->id]);

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
        $result = new \App\DTOs\SimilarityResult(
            model: new OdsMeta(),
            score: 0.856,
            distance: 0.144
        );

        $this->assertEquals('85.6%', $result->percentage);
    }

    public function test_dto_is_high_quality(): void
    {
        $highQuality = new \App\DTOs\SimilarityResult(
            model: new OdsMeta(),
            score: 0.90,
            distance: 0.10
        );

        $lowQuality = new \App\DTOs\SimilarityResult(
            model: new OdsMeta(),
            score: 0.80,
            distance: 0.20
        );

        $this->assertTrue($highQuality->isHighQuality());
        $this->assertFalse($lowQuality->isHighQuality());
        $this->assertTrue($lowQuality->isHighQuality(0.75)); // Con umbral personalizado
    }

    // ============================================
    // Tests de Búsqueda Múltiple
    // ============================================

    public function test_find_similar_in_multiple_combina_resultados(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->twice()
            ->andReturn(array_fill(0, 1536, 0.5));

        // Crear datos en ODS y PND
        $ods = OdsMeta::create([
            'ods_objetivo_id' => OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test'])->id,
            'clave' => '1.1',
            'descripcion' => 'Reducir pobreza',
        ]);
        \DB::statement("UPDATE ods_metas SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$ods->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilarInMultiple(
            'reducir pobreza',
            [OdsMeta::class, OdsObjetivo::class],
            limitPerModel: 2
        );

        $this->assertGreaterThanOrEqual(0, $results->count());
    }

    // ============================================
    // Tests de Métodos de Conveniencia
    // ============================================

    public function test_find_similar_linea_accion(): void
    {
        $mockEmbedding = Mockery::mock(EmbeddingServiceInterface::class);
        $mockEmbedding->shouldReceive('generate')
            ->once()
            ->andReturn(array_fill(0, 1536, 0.5));

        $plan = \App\Models\PedPlan::create([
            'nombre' => 'Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);
        $eje = \App\Models\PedEje::create(['ped_plan_id' => $plan->id, 'numero' => '1', 'nombre' => 'Test']);
        $tema = \App\Models\PedTema::create(['ped_eje_id' => $eje->id, 'numero' => '1', 'nombre' => 'Test']);
        $objetivo = \App\Models\PedObjetivoEstrategico::create(['ped_tema_id' => $tema->id, 'clave' => '1', 'descripcion' => 'Test']);
        $estrategia = \App\Models\PedEstrategia::create(['ped_objetivo_estrategico_id' => $objetivo->id, 'clave' => '1', 'descripcion' => 'Test']);
        $linea = \App\Models\PedLineaAccion::create(['ped_estrategia_id' => $estrategia->id, 'clave' => '1', 'descripcion' => 'Reducir pobreza']);
        \DB::statement("UPDATE ped_lineas_accion SET embedding = '[0.5,0.5,0.5]'::vector || ARRAY_FILL(0.5, ARRAY[1533])::vector WHERE id = ?", [$linea->id]);

        $service = new SemanticSearchService($mockEmbedding);
        $results = $service->findSimilarLineaAccion('reducir pobreza');

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
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
sail artisan make:test HnswIndexesTest
```

Editar `tests/Feature/HnswIndexesTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HnswIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_indices_hnsw_creados_correctamente(): void
    {
        // Ejecutar migración
        $this->artisan('migrate');

        // Verificar índices ODS
        $this->assertIndexExists('ods_objetivos_embedding_idx');
        $this->assertIndexExists('ods_metas_embedding_idx');

        // Verificar índices PND
        $this->assertIndexExists('pnd_ejes_embedding_idx');
        $this->assertIndexExists('pnd_objetivos_embedding_idx');
        $this->assertIndexExists('pnd_estrategias_embedding_idx');

        // Verificar índices PED
        $this->assertIndexExists('ped_ejes_embedding_idx');
        $this->assertIndexExists('ped_temas_embedding_idx');
        $this->assertIndexExists('ped_objetivos_estrategicos_embedding_idx');
        $this->assertIndexExists('ped_estrategias_embedding_idx');
        $this->assertIndexExists('ped_lineas_accion_embedding_idx');

        // Verificar índices Programas Derivados
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

        // Verificar que el índice tiene el parámetro m configurado
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

    /**
     * Verifica que un índice existe.
     */
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

    /**
     * Verifica que un índice NO existe.
     */
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

### 8. Ejecutar y Verificar

```bash
# Ejecutar migración de índices
sail artisan migrate

# Verificar índices creados
sail shell
psql -U sail -d laravel -c "\di *embedding*"
# Debe mostrar todos los índices HNSW
exit

# Ejecutar tests
sail artisan test --filter SemanticSearchServiceTest
sail artisan test --filter HnswIndexesTest

# Verificar en Tinker
sail artisan tinker
```

```php
use App\Services\SemanticSearchService;
use App\Models\OdsMeta;

// Crear datos de prueba con embedding
$ods = OdsMeta::create([...]);
DB::statement("UPDATE ods_metas SET embedding = '[0.5,...]'::vector WHERE id = ?", [$ods->id]);

// Probar búsqueda
$service = app(SemanticSearchService::class);
$results = $service->findSimilar('reducir pobreza', OdsMeta::class);

$results->first()->score;
$results->first()->percentage;
```

---

## Criterios de Aceptación

- [ ] `SemanticSearchService` registrado en Service Container
- [ ] Método `findSimilar()` funciona contra todas las tablas con embeddings
- [ ] DTO `SimilarityResult` con propiedades `model`, `score`, `distance`
- [ ] Migración crea índices HNSW en todas las columnas `embedding`
- [ ] Verificar índices con `\di` en psql muestra `hnsw`
- [ ] Umbral mínimo configurable via parámetro (default 0.7)
- [ ] Límite configurable via parámetro (default 5)
- [ ] Test: búsqueda "reducir pobreza" retorna resultados ordenados por score
- [ ] Test: búsqueda con umbral 0.99 retorna colección vacía
- [ ] Test: búsqueda con límite 1 retorna exactamente 1 resultado
- [ ] Test: modelo no buscable lanza `InvalidArgumentException`
- [ ] Configuración en `config/embedding.php` completa

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
