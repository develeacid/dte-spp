# Plan: S2-T10 — Pipeline de Generación de Embeddings

**Ticket:** S2-T10
**Tipo:** feat
**Rama:** `feat/S2-T10-pipeline-embeddings`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T1, S2-T2, S2-T3 (Tablas), S0-T4 (Redis Colas)

---

## Contexto

Los embeddings vectoriales permiten realizar búsquedas semánticas (por significado, no por palabras exactas) en la cascada de planes. Este ticket implementa el pipeline completo: servicio que encapsula la llamada al API, job asíncrono procesado en cola Redis, y observers que disparan la generación automáticamente al crear/actualizar descripciones.

**Flujo del Pipeline:**

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Crear/Update  │───→│   Observer       │───→│   Job Dispatch  │
│   Modelo        │    │   (isDirty)      │    │   (Cola Redis)  │
└─────────────────┘    └──────────────────┘    └────────┬────────┘
                                                        │
                                                        ↓
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Guardar en BD │←───│   PG Vector      │←───│   Embedding API │
│   embedding     │    │   (vector 1536)  │    │   (OpenAI/etc)  │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

---

## Pre-requisitos

- S0-T4: Redis configurado como driver de colas
- S2-T1, S2-T2, S2-T3: Tablas con columnas `embedding vector(1536)`
- API key del proveedor de embeddings (OpenAI `text-embedding-ada-002` o compatible)

---

## Pasos

### 1. Crear Interfaz del Servicio

```bash
mkdir -p app/Contracts
```

Crear `app/Contracts/EmbeddingServiceInterface.php`:

```php
<?php

namespace App\Contracts;

interface EmbeddingServiceInterface
{
    /**
     * Genera un embedding vectorial para el texto proporcionado.
     *
     * @param string $text Texto a convertir en embedding
     * @return array Array de floats (dimensión según modelo, típicamente 1536)
     * @throws \Exception Si el API falla
     */
    public function generate(string $text): array;

    /**
     * Obtiene la dimensión del embedding (número de elementos).
     */
    public function getDimension(): int;
}
```

---

### 2. Crear Servicio de Embeddings

Crear `app/Services/EmbeddingService.php`:

```php
<?php

namespace App\Services;

use App\Contracts\EmbeddingServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService implements EmbeddingServiceInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected int $dimension;

    public function __construct()
    {
        $this->apiKey = config('services.embedding.api_key');
        $this->apiUrl = config('services.embedding.api_url', 'https://api.openai.com/v1/embeddings');
        $this->model = config('services.embedding.model', 'text-embedding-ada-002');
        $this->dimension = config('services.embedding.dimension', 1536);
    }

    /**
     * Genera un embedding vectorial para el texto proporcionado.
     */
    public function generate(string $text): array
    {
        // Validar que hay texto
        if (empty(trim($text))) {
            throw new \InvalidArgumentException('El texto no puede estar vacío');
        }

        // Truncar texto si es muy largo (límite del modelo)
        $text = $this->truncateText($text);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($this->apiUrl, [
                'model' => $this->model,
                'input' => $text,
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message', 'Error desconocido');
                Log::error('Embedding API error', [
                    'status' => $response->status(),
                    'error' => $error,
                    'text_length' => strlen($text),
                ]);
                throw new \Exception("Embedding API error: {$error}");
            }

            $embedding = $response->json('data.0.embedding');

            if (!is_array($embedding) || count($embedding) !== $this->dimension) {
                throw new \Exception('Invalid embedding response dimension');
            }

            return $embedding;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Embedding API connection error', [
                'message' => $e->getMessage(),
            ]);
            throw new \Exception('Connection error to Embedding API');
        }
    }

    /**
     * Obtiene la dimensión del embedding.
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * Trunca el texto si excede el límite de tokens aproximado.
     */
    protected function truncateText(string $text, int $maxTokens = 8000): string
    {
        // Aproximación: 4 caracteres por token en español
        $maxLength = $maxTokens * 4;

        if (strlen($text) > $maxLength) {
            return substr($text, 0, $maxLength);
        }

        return $text;
    }
}
```

---

### 3. Configurar Servicio en Laravel

Crear `config/services.php` o agregar al existente:

```php
<?php

return [
    // ... otros servicios

    'embedding' => [
        'api_key' => env('EMBEDDING_API_KEY'),
        'api_url' => env('EMBEDDING_API_URL', 'https://api.openai.com/v1/embeddings'),
        'model' => env('EMBEDDING_MODEL', 'text-embedding-ada-002'),
        'dimension' => env('EMBEDDING_DIMENSION', 1536),
    ],
];
```

Registrar como singleton en `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Contracts\EmbeddingServiceInterface;
use App\Services\EmbeddingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EmbeddingServiceInterface::class, function ($app) {
            return new EmbeddingService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gate global para admin (existente de S1-T5)
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });
    }
}
```

---

### 4. Crear Job para Generación de Embeddings

```bash
sail artisan make:job GenerateEmbedding
```

Editar `app/Jobs/GenerateEmbedding.php`:

```php
<?php

namespace App\Jobs;

use App\Contracts\EmbeddingServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de intentos antes de fallar.
     */
    public int $tries = 3;

    /**
     * Backoff exponencial entre reintentos (en segundos).
     */
    public array $backoff = [10, 60, 300];

    /**
     * Tiempo máximo de ejecución del job.
     */
    public int $timeout = 60;

    /**
     * Modelo para el que se generará el embedding.
     */
    protected string $modelClass;

    /**
     * ID del modelo.
     */
    protected int $modelId;

    /**
     * Texto para generar embedding.
     */
    protected string $text;

    /**
     * Columna donde guardar el embedding.
     */
    protected string $embeddingColumn;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $modelClass,
        int $modelId,
        string $text,
        string $embeddingColumn = 'embedding'
    ) {
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->text = $text;
        $this->embeddingColumn = $embeddingColumn;

        // Usar cola específica para embeddings
        $this->onQueue('embeddings');
    }

    /**
     * Execute the job.
     */
    public function handle(EmbeddingServiceInterface $embeddingService): void
    {
        // Obtener el modelo
        $model = $this->modelClass::find($this->modelId);

        if (!$model) {
            Log::warning('Model not found for embedding generation', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
            ]);
            return;
        }

        try {
            // Generar embedding
            $embedding = $embeddingService->generate($this->text);

            // Convertir a formato PostgreSQL
            $embeddingString = '[' . implode(',', $embedding) . ']';

            // Guardar en la base de datos usando raw SQL
            // Esto es necesario porque Eloquent no soporta nativamente columnas vectoriales
            $tableName = $model->getTable();
            DB::statement(
                "UPDATE {$tableName} SET {$this->embeddingColumn} = ?::vector WHERE id = ?",
                [$embeddingString, $this->modelId]
            );

            Log::info('Embedding generated successfully', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'dimension' => count($embedding),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate embedding', [
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Embedding job failed permanently', [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'error' => $exception->getMessage(),
        ]);

        // El registro queda con embedding null, pero el job se marca como failed
        // El usuario no es bloqueado
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'embedding',
            "model:{$this->modelClass}",
            "id:{$this->modelId}",
        ];
    }
}
```

---

### 5. Crear Observer Base Reutilizable

Crear `app/Observers/EmbeddingObserver.php`:

```php
<?php

namespace App\Observers;

use App\Jobs\GenerateEmbedding;
use Illuminate\Database\Eloquent\Model;

class EmbeddingObserver
{
    /**
     * Nombre del campo de descripción a usar para el embedding.
     */
    protected string $descripcionField = 'descripcion';

    /**
     * Nombre del campo donde guardar el embedding.
     */
    protected string $embeddingField = 'embedding';

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->dispatchEmbeddingJob($model);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Solo regenerar si cambió la descripción
        if ($model->isDirty($this->descripcionField)) {
            $this->dispatchEmbeddingJob($model);
        }
    }

    /**
     * Despacha el job de generación de embedding.
     */
    protected function dispatchEmbeddingJob(Model $model): void
    {
        $text = $model->{$this->descripcionField};

        // No generar embedding si no hay descripción
        if (empty($text)) {
            return;
        }

        // Despachar job a la cola
        GenerateEmbedding::dispatch(
            get_class($model),
            $model->id,
            $text,
            $this->embeddingField
        );
    }

    /**
     * Configura el campo de descripción personalizado.
     */
    public function setDescripcionField(string $field): self
    {
        $this->descripcionField = $field;
        return $this;
    }
}
```

---

### 6. Crear Observers Específicos por Modelo

Crear `app/Observers/OdsObjetivoObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\OdsObjetivo;

class OdsObjetivoObserver extends EmbeddingObserver
{
    protected string $descripcionField = 'nombre';
}
```

Crear `app/Observers/OdsMetaObserver.php`:

```php
<?php

namespace App\Observers;

class OdsMetaObserver extends EmbeddingObserver
{
    // Usa descripción por defecto
}
```

Crear `app/Observers/PndEjeObserver.php`:

```php
<?php

namespace App\Observers;

class PndEjeObserver extends EmbeddingObserver
{
    protected string $descripcionField = 'nombre';
}
```

Crear `app/Observers/PndObjetivoObserver.php`:

```php
<?php

namespace App\Observers;

class PndObjetivoObserver extends EmbeddingObserver
{
    // Usa descripción por defecto
}
```

Crear `app/Observers/PndEstrategiaObserver.php`:

```php
<?php

namespace App\Observers;

class PndEstrategiaObserver extends EmbeddingObserver
{
    // Usa descripción por defecto
}
```

Crear `app/Observers/PedObserver.php`:

```php
<?php

namespace App\Observers;

class PedObserver extends EmbeddingObserver
{
    // Observer base para todos los modelos PED
    // Usa descripción por defecto
}
```

---

### 7. Registrar Observers en AppServiceProvider

Editar `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Contracts\EmbeddingServiceInterface;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use App\Models\PndEje;
use App\Models\PndEstrategia;
use App\Models\PndObjetivo;
use App\Observers\OdsMetaObserver;
use App\Observers\OdsObjetivoObserver;
use App\Observers\PedObserver;
use App\Observers\PndEjeObserver;
use App\Observers\PndEstrategiaObserver;
use App\Observers\PndObjetivoObserver;
use App\Services\EmbeddingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EmbeddingServiceInterface::class, function ($app) {
            return new EmbeddingService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gate global para admin
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // Registrar Observers para embeddings
        // Solo si no estamos en ambiente de testing (se puede desactivar en tests)
        if (!$this->app->environment('testing') || config('app.enable_embedding_observers', false)) {
            $this->registerEmbeddingObservers();
        }
    }

    /**
     * Registra los observers para generación de embeddings.
     */
    protected function registerEmbeddingObservers(): void
    {
        // ODS
        OdsObjetivo::observe(OdsObjetivoObserver::class);
        OdsMeta::observe(OdsMetaObserver::class);

        // PND
        PndEje::observe(PndEjeObserver::class);
        PndObjetivo::observe(PndObjetivoObserver::class);
        PndEstrategia::observe(PndEstrategiaObserver::class);

        // PED (todos usan el mismo observer base)
        PedPlan::observe(PedObserver::class);
        PedEje::observe(PedObserver::class);
        PedTema::observe(PedObserver::class);
        PedObjetivoEstrategico::observe(PedObserver::class);
        PedEstrategia::observe(PedObserver::class);
        PedLineaAccion::observe(PedObserver::class);
    }
}
```

---

### 8. Configurar Cola de Embeddings

Editar `config/queue.php`:

```php
<?php

return [
    'default' => env('QUEUE_CONNECTION', 'redis'),

    'connections' => [
        // ...

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => 5,
            'after_commit' => true,
        ],
    ],

    // Colas específicas con prioridad
    'queues' => [
        'default' => 10,
        'embeddings' => 5,  // Menor prioridad, más workers dedicados
    ],
];
```

---

### 9. Crear Comando para Procesar Cola de Embeddings

```bash
sail artisan make:command ProcessEmbeddingsQueue
```

Editar `app/Console/Commands/ProcessEmbeddingsQueue.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessEmbeddingsQueue extends Command
{
    protected $signature = 'queue:embeddings {--timeout=60} {--tries=3}';

    protected $description = 'Procesa la cola de embeddings exclusivamente';

    public function handle(): int
    {
        $this->info('Iniciando worker para cola de embeddings...');
        $this->info('Presiona Ctrl+C para detener');

        $this->call('queue:work', [
            '--queue' => 'embeddings',
            '--timeout' => $this->option('timeout'),
            '--tries' => $this->option('tries'),
            '--max-jobs' => 100,
            '--max-time' => 3600,
        ]);

        return Command::SUCCESS;
    }
}
```

---

### 10. Actualizar Variables de Entorno

Editar `.env.example`:

```ini
# ============================================
# EMBEDDINGS CONFIGURATION
# ============================================
# API Key para el servicio de embeddings (OpenAI, Azure, etc.)
EMBEDDING_API_KEY=your-api-key-here

# URL del API de embeddings
EMBEDDING_API_URL=https://api.openai.com/v1/embeddings

# Modelo de embeddings a utilizar
EMBEDDING_MODEL=text-embedding-ada-002

# Dimensión del vector (1536 para ada-002)
EMBEDDING_DIMENSION=1536
```

---

### 11. Crear Tests

```bash
sail artisan make:test EmbeddingServiceTest --unit
sail artisan make:test EmbeddingObserverTest
sail artisan make:test GenerateEmbeddingJobTest --unit
```

Editar `tests/Unit/EmbeddingServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Contracts\EmbeddingServiceInterface;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configurar valores de prueba
        config([
            'services.embedding.api_key' => 'test-api-key',
            'services.embedding.api_url' => 'https://api.test.com/v1/embeddings',
            'services.embedding.model' => 'test-model',
            'services.embedding.dimension' => 1536,
        ]);
    }

    public function test_genera_embedding_correctamente(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    [
                        'embedding' => array_fill(0, 1536, 0.1),
                    ]
                ],
            ], 200),
        ]);

        $service = new EmbeddingService();
        $embedding = $service->generate('Texto de prueba');

        $this->assertCount(1536, $embedding);
        $this->assertEquals(0.1, $embedding[0]);
    }

    public function test_lanza_excepcion_si_texto_vacio(): void
    {
        $service = new EmbeddingService();

        $this->expectException(\InvalidArgumentException::class);
        $service->generate('');
    }

    public function test_lanza_excepcion_si_api_falla(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'error' => ['message' => 'Invalid API key'],
            ], 401),
        ]);

        $service = new EmbeddingService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Embedding API error');

        $service->generate('Texto de prueba');
    }

    public function test_lanza_excepcion_si_respuesta_invalida(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => [0.1, 0.2]], // Dimensión incorrecta
                ],
            ], 200),
        ]);

        $service = new EmbeddingService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid embedding response dimension');

        $service->generate('Texto de prueba');
    }

    public function test_trunca_texto_largo(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)],
                ],
            ], 200),
        ]);

        $service = new EmbeddingService();
        $textoLargo = str_repeat('a', 50000); // 50,000 caracteres

        $embedding = $service->generate($textoLargo);

        $this->assertCount(1536, $embedding);

        // Verificar que se truncó antes de enviar
        Http::assertSent(function ($request) {
            $input = $request->data()['input'];
            return strlen($input) <= 32000; // 8000 tokens * 4 chars
        });
    }

    public function test_get_dimension(): void
    {
        $service = new EmbeddingService();

        $this->assertEquals(1536, $service->getDimension());
    }
}
```

Editar `tests/Feature/EmbeddingObserverTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Jobs\GenerateEmbedding;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedPlan;
use App\Models\PndEje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmbeddingObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Habilitar observers para este test
        config(['app.enable_embedding_observers' => true]);
    }

    // ============================================
    // Tests de Creación
    // ============================================

    public function test_crear_ods_objetivo_despacha_job(): void
    {
        Queue::fake();

        $ods = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Fin de la Pobreza',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, function ($job) use ($ods) {
            return $job->modelClass === OdsObjetivo::class
                && $job->modelId === $ods->id;
        });
    }

    public function test_crear_ods_meta_despacha_job(): void
    {
        Queue::fake();

        $odsObjetivo = OdsObjetivo::create(['numero' => 1, 'nombre' => 'Test']);

        $meta = OdsMeta::create([
            'ods_objetivo_id' => $odsObjetivo->id,
            'clave' => '1.1',
            'descripcion' => 'Meta de prueba',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, function ($job) use ($meta) {
            return $job->modelClass === OdsMeta::class
                && $job->modelId === $meta->id
                && $job->text === 'Meta de prueba';
        });
    }

    public function test_crear_registro_sin_descripcion_no_despacha_job(): void
    {
        Queue::fake();

        // Crear PED primero
        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        // Crear eje sin descripción (usa nombre)
        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje Test',
            'descripcion' => null,
        ]);

        // No debe despachar job porque descripcion es null y nombre no genera embedding en PED
        // Pero según la implementación, el observer base usa descripcionField = 'descripcion'
        // Si descripcion es null, no despacha
        Queue::assertNotPushed(GenerateEmbedding::class);
    }

    // ============================================
    // Tests de Actualización
    // ============================================

    public function test_actualizar_descripcion_despacha_job(): void
    {
        Queue::fake();

        $odsObjetivo = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Nombre Original',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, 1); // Del create

        Queue::fake(); // Limpiar

        $odsObjetivo->update(['nombre' => 'Nombre Actualizado']);

        Queue::assertPushed(GenerateEmbedding::class, function ($job) use ($odsObjetivo) {
            return $job->modelId === $odsObjetivo->id;
        });
    }

    public function test_actualizar_otro_campo_no_despacha_job(): void
    {
        Queue::fake();

        $odsObjetivo = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Nombre Test',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, 1); // Del create

        Queue::fake(); // Limpiar

        // Actualizar solo el número (no el nombre)
        $odsObjetivo->update(['numero' => 2]);

        Queue::assertNotPushed(GenerateEmbedding::class);
    }

    // ============================================
    // Tests de Cola Específica
    // ============================================

    public function test_job_se_despacha_a_cola_embeddings(): void
    {
        Queue::fake();

        OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Test',
        ]);

        Queue::assertPushedOn('embeddings', GenerateEmbedding::class);
    }

    // ============================================
    // Tests con Modelos PED
    // ============================================

    public function test_crear_eje_ped_despacha_job(): void
    {
        Queue::fake();

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Queue::fake(); // Limpiar el job del plan

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje de Prueba',
            'descripcion' => 'Descripción del eje',
        ]);

        Queue::assertPushed(GenerateEmbedding::class, function ($job) use ($eje) {
            return $job->modelClass === PedEje::class
                && $job->modelId === $eje->id;
        });
    }
}
```

Editar `tests/Unit/GenerateEmbeddingJobTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Contracts\EmbeddingServiceInterface;
use App\Jobs\GenerateEmbedding;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class GenerateEmbeddingJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_guarda_embedding_correctamente(): void
    {
        // Mock del servicio
        $mockService = Mockery::mock(EmbeddingServiceInterface::class);
        $mockService->shouldReceive('generate')
            ->once()
            ->with('Texto de prueba')
            ->andReturn(array_fill(0, 1536, 0.5));

        $this->app->instance(EmbeddingServiceInterface::class, $mockService);

        // Crear modelo sin embedding
        $odsObjetivo = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Test',
        ]);

        // Ejecutar job
        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            $odsObjetivo->id,
            'Texto de prueba'
        );

        $job->handle($mockService);

        // Verificar que el embedding se guardó
        $this->assertDatabaseHas('ods_objetivos', [
            'id' => $odsObjetivo->id,
        ]);

        // Verificar el embedding directamente en BD
        $result = DB::selectOne(
            "SELECT embedding FROM ods_objetivos WHERE id = ?",
            [$odsObjetivo->id]
        );

        $this->assertNotNull($result->embedding);
    }

    public function test_job_no_falla_si_modelo_no_existe(): void
    {
        Log::shouldReceive('warning')->once();

        $mockService = Mockery::mock(EmbeddingServiceInterface::class);
        $mockService->shouldNotReceive('generate');

        $this->app->instance(EmbeddingServiceInterface::class, $mockService);

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            9999, // ID inexistente
            'Texto de prueba'
        );

        $job->handle($mockService);

        // No debe lanzar excepción
        $this->assertTrue(true);
    }

    public function test_job_reintenta_si_api_falla(): void
    {
        $mockService = Mockery::mock(EmbeddingServiceInterface::class);
        $mockService->shouldReceive('generate')
            ->times(3) // Intentos = 3
            ->andThrow(new \Exception('API Error'));

        $this->app->instance(EmbeddingServiceInterface::class, $mockService);

        $odsObjetivo = OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Test',
        ]);

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            $odsObjetivo->id,
            'Texto de prueba'
        );

        // El job debe tener tries = 3
        $this->assertEquals(3, $job->tries);

        // Simular reintentos
        $exception = null;
        for ($i = 0; $i < 3; $i++) {
            try {
                $job->handle($mockService);
            } catch (\Exception $e) {
                $exception = $e;
            }
        }

        $this->assertNotNull($exception);
    }

    public function test_job_tiene_backoff_configurado(): void
    {
        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $this->assertEquals([10, 60, 300], $job->backoff);
    }

    public function test_job_tiene_tags_correctos(): void
    {
        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            123,
            'Test'
        );

        $tags = $job->tags();

        $this->assertContains('embedding', $tags);
        $this->assertContains('model:' . OdsObjetivo::class, $tags);
        $this->assertContains('id:123', $tags);
    }
}
```

---

### 12. Ejecutar y Verificar

```bash
# Ejecutar tests unitarios
sail artisan test --filter EmbeddingServiceTest
sail artisan test --filter GenerateEmbeddingJobTest

# Ejecutar tests de feature
sail artisan test --filter EmbeddingObserverTest

# Iniciar worker de embeddings
sail artisan queue:embeddings

# Verificar que los jobs se procesan
sail artisan queue:work --queue=embeddings --once
```

Verificación manual:

```bash
# En Tinker
sail artisan tinker
```

```php
use App\Models\OdsObjetivo;

// Crear un ODS
$ods = OdsObjetivo::create(['numero' => 99, 'nombre' => 'Test Embedding']);

// Verificar que el job está en la cola
// (usar otro terminal con queue:work)

// Verificar embedding guardado
DB::select("SELECT embedding FROM ods_objetivos WHERE id = ?", [$ods->id]);
```

---

## Criterios de Aceptación

- [ ] Interfaz `EmbeddingServiceInterface` creada
- [ ] `EmbeddingService` registrado como singleton en `AppServiceProvider`
- [ ] `EmbeddingService::generate()` retorna array de 1536 floats
- [ ] `GenerateEmbedding` job usa cola `embeddings`
- [ ] Job con `$tries = 3` y `$backoff = [10, 60, 300]`
- [ ] Observers registrados en todos los modelos con columna `embedding`
- [ ] Observer solo despacha job si `descripcion` cambió (`isDirty`)
- [ ] Al crear registro, embedding se genera automáticamente
- [ ] Al actualizar descripción, embedding se regenera
- [ ] Si API falla después de 3 intentos, job marca como `failed` sin bloquear
- [ ] Test: crear modelo despacha job
- [ ] Test: actualizar descripción despacha job; actualizar otro campo no
- [ ] `.env.example` documentado con variables de configuración

---

## Notas

### Workers Separados

```bash
# Worker dedicado para embeddings (puede tener más workers)
sail artisan queue:work --queue=embeddings --daemon

# Worker para cola principal (otros jobs)
sail artisan queue:work --queue=default --daemon

# Worker híbrido (prioriza default)
sail artisan queue:work --queue=default,embeddings
```

### Mock en Tests

```php
// Desactivar observers en test específico
protected function setUp(): void
{
    parent::setUp();
    config(['app.enable_embedding_observers' => false]);
}

// O usando withoutEvents
use Illuminate\Foundation\Testing\RefreshDatabase;

protected function setUp(): void
{
    parent::setUp();
    Model::withoutEvents(function () {
        // Crear modelos sin disparar observers
    });
}
```

### Proveedores de Embeddings Alternativos

El servicio es configurable para usar diferentes proveedores:

| Proveedor      | API URL                                                                                | Modelo                   | Dimensión |
| -------------- | -------------------------------------------------------------------------------------- | ------------------------ | --------- |
| OpenAI         | `https://api.openai.com/v1/embeddings`                                                 | `text-embedding-ada-002` | 1536      |
| OpenAI (nuevo) | `https://api.openai.com/v1/embeddings`                                                 | `text-embedding-3-small` | 1536      |
| OpenAI (large) | `https://api.openai.com/v1/embeddings`                                                 | `text-embedding-3-large` | 3072      |
| Azure OpenAI   | `https://YOUR_RESOURCE.openai.azure.com/openai/deployments/YOUR_DEPLOYMENT/embeddings` | Custom                   | Variable  |
| Local (Ollama) | `http://localhost:11434/api/embeddings`                                                | `nomic-embed-text`       | 768       |

### Monitoreo de Cola

```bash
# Ver jobs en cola
sail artisan queue:monitor embeddings

# Ver jobs fallidos
sail artisan queue:failed

// Reintentar job fallido
sail artisan queue:retry {job_id}
```

---
