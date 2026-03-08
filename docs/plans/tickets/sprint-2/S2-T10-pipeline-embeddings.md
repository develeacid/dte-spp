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

**Esta versión implementa:**
- Servicio en `App\Services\Embeddings\`
- Interface en `App\Contracts\`
- Job en `App\Jobs\Embeddings\`
- Observers específicos por modelo
- Configuración centralizada en `config/embedding.php`
- Tests organizados por dominio

---

## Pre-requisitos

- S0-T4: Redis configurado como driver de colas
- S2-T1, S2-T2, S2-T3: Tablas con columnas `embedding vector(1536)`
- API key del proveedor de embeddings (OpenAI `text-embedding-ada-002` o compatible)
- Extensión PostgreSQL `pgvector` habilitada

---

## Pasos

### 1. Crear Archivo de Configuración Dedicado

Crear `config/embedding.php`:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del Servicio de Embeddings
    |--------------------------------------------------------------------------
    |
    | Este archivo centraliza toda la configuración relacionada con la generación
    | y búsqueda de embeddings vectoriales.
    |
    */

    // ============================================
    // API Configuration
    // ============================================
    
    'api_key' => env('EMBEDDING_API_KEY'),
    'api_url' => env('EMBEDDING_API_URL', 'https://api.openai.com/v1/embeddings'),
    'model' => env('EMBEDDING_MODEL', 'text-embedding-ada-002'),
    'dimension' => env('EMBEDDING_DIMENSION', 1536),

    // ============================================
    // Rate Limiting
    // ============================================
    
    // Máximo número de requests por minuto al API
    'rate_limit' => env('EMBEDDING_RATE_LIMIT', 60),

    // Timeout en segundos para requests
    'timeout' => env('EMBEDDING_TIMEOUT', 30),

    // ============================================
    // Queue Configuration
    // ============================================
    
    // Cola específica para jobs de embeddings
    'queue' => env('EMBEDDING_QUEUE', 'embeddings'),

    // Número de reintentos antes de marcar como failed
    'tries' => env('EMBEDDING_JOB_TRIES', 3),

    // Backoff exponencial entre reintentos (segundos)
    'backoff' => [10, 60, 300],

    // Tiempo máximo de ejecución del job
    'timeout_job' => env('EMBEDDING_JOB_TIMEOUT', 60),

    // ============================================
    // Chunking
    // ============================================
    
    // Máximo de tokens por request (aproximación: 4 chars = 1 token)
    'max_tokens' => env('EMBEDDING_MAX_TOKENS', 8000),

    // ============================================
    // Observers
    // ============================================
    
    // Habilitar/deshabilitar observers globalmente (útil para tests)
    'observers_enabled' => env('EMBEDDING_OBSERVERS_ENABLED', true),
];
```

---

### 2. Crear Interfaz del Servicio

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
     * @throws \InvalidArgumentException Si el texto está vacío
     * @throws \RuntimeException Si el API falla
     */
    public function generate(string $text): array;

    /**
     * Obtiene la dimensión del embedding (número de elementos).
     */
    public function getDimension(): int;

    /**
     * Obtiene el modelo de embeddings configurado.
     */
    public function getModel(): string;
}
```

---

### 3. Crear Servicio de Embeddings (Organizado por Dominio)

```bash
mkdir -p app/Services/Embeddings
```

Crear `app/Services/Embeddings/EmbeddingService.php`:

```php
<?php

namespace App\Services\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class EmbeddingService implements EmbeddingServiceInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected int $dimension;
    protected int $timeout;
    protected int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('embedding.api_key');
        $this->apiUrl = config('embedding.api_url');
        $this->model = config('embedding.model');
        $this->dimension = config('embedding.dimension');
        $this->timeout = config('embedding.timeout', 30);
        $this->maxTokens = config('embedding.max_tokens', 8000);
    }

    /**
     * Genera un embedding vectorial para el texto proporcionado.
     */
    public function generate(string $text): array
    {
        $this->validateInput($text);

        $text = $this->truncateText($text);

        return $this->callApi($text);
    }

    /**
     * Obtiene la dimensión del embedding.
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * Obtiene el modelo configurado.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Valida el texto de entrada.
     */
    protected function validateInput(string $text): void
    {
        if (empty(trim($text))) {
            throw new \InvalidArgumentException('El texto no puede estar vacío');
        }
    }

    /**
     * Trunca el texto si excede el límite de tokens.
     */
    protected function truncateText(string $text): string
    {
        $maxLength = $this->maxTokens * 4; // Aproximación: 4 chars = 1 token

        if (strlen($text) > $maxLength) {
            Log::info('Text truncated for embedding generation', [
                'original_length' => strlen($text),
                'truncated_length' => $maxLength,
            ]);

            return substr($text, 0, $maxLength);
        }

        return $text;
    }

    /**
     * Realiza la llamada al API de embeddings.
     */
    protected function callApi(string $text): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post($this->apiUrl, [
                'model' => $this->model,
                'input' => $text,
            ]);

            return $this->parseResponse($response);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Embedding API connection error', [
                'message' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);

            throw new \RuntimeException('Connection error to Embedding API');
        }
    }

    /**
     * Parsea la respuesta del API.
     */
    protected function parseResponse($response): array
    {
        if (!$response->successful()) {
            $error = $response->json('error.message', 'Error desconocido');
            $statusCode = $response->status();

            Log::error('Embedding API error', [
                'status' => $statusCode,
                'error' => $error,
            ]);

            throw new \RuntimeException("Embedding API error ({$statusCode}): {$error}");
        }

        $embedding = $response->json('data.0.embedding');

        $this->validateEmbedding($embedding);

        return $embedding;
    }

    /**
     * Valida la respuesta del embedding.
     */
    protected function validateEmbedding(?array $embedding): void
    {
        if (!is_array($embedding)) {
            throw new \RuntimeException('Invalid embedding response: not an array');
        }

        if (count($embedding) !== $this->dimension) {
            throw new \RuntimeException(
                "Invalid embedding dimension: expected {$this->dimension}, got " . count($embedding)
            );
        }

        // Verificar que todos los elementos son numéricos
        foreach ($embedding as $i => $value) {
            if (!is_float($value) && !is_int($value)) {
                throw new \RuntimeException("Invalid embedding value at index {$i}");
            }
        }
    }
}
```

---

### 4. Crear Job para Generación de Embeddings

```bash
mkdir -p app/Jobs/Embeddings
```

Crear `app/Jobs/Embeddings/GenerateEmbedding.php`:

```php
<?php

namespace App\Jobs\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
    public int $tries;

    /**
     * Backoff exponencial entre reintentos (en segundos).
     */
    public array $backoff;

    /**
     * Tiempo máximo de ejecución del job.
     */
    public int $timeout;

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

        // Configuración desde config
        $this->tries = config('embedding.tries', 3);
        $this->backoff = config('embedding.backoff', [10, 60, 300]);
        $this->timeout = config('embedding.timeout_job', 60);

        // Usar cola específica para embeddings
        $this->onQueue(config('embedding.queue', 'embeddings'));
    }

    /**
     * Execute the job.
     */
    public function handle(EmbeddingServiceInterface $embeddingService): void
    {
        // Verificar que el modelo existe
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

            // Guardar en la base de datos
            $this->saveEmbedding($model, $embedding);

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
     * Guarda el embedding en la base de datos.
     */
    protected function saveEmbedding($model, array $embedding): void
    {
        $tableName = $model->getTable();
        $embeddingString = '[' . implode(',', $embedding) . ']';

        // Usar raw SQL porque Eloquent no soporta nativamente columnas vectoriales
        DB::statement(
            "UPDATE {$tableName} SET {$this->embeddingColumn} = ?::vector WHERE id = ?",
            [$embeddingString, $this->modelId]
        );
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

        // El registro queda con embedding null
        // El job se marca como failed, el usuario no es bloqueado
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

    /**
     * Determina el tiempo de espera antes del próximo intento.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }
}
```

---

### 5. Crear Observer Base Reutilizable

Crear `app/Observers/EmbeddingObserver.php`:

```php
<?php

namespace App\Observers;

use App\Jobs\Embeddings\GenerateEmbedding;
use Illuminate\Database\Eloquent\Model;

class EmbeddingObserver
{
    /**
     * Nombre del campo de descripción a usar para el embedding.
     */
    protected string $descriptionField = 'descripcion';

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
        // Solo regenerar si cambió el campo de descripción
        if ($model->isDirty($this->descriptionField)) {
            $this->dispatchEmbeddingJob($model);
        }
    }

    /**
     * Despacha el job de generación de embedding.
     */
    protected function dispatchEmbeddingJob(Model $model): void
    {
        $text = $model->{$this->descriptionField};

        // No generar embedding si no hay texto
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
    public function setDescriptionField(string $field): self
    {
        $this->descriptionField = $field;
        return $this;
    }

    /**
     * Configura el campo de embedding personalizado.
     */
    public function setEmbeddingField(string $field): self
    {
        $this->embeddingField = $field;
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

class OdsObjetivoObserver extends EmbeddingObserver
{
    protected string $descriptionField = 'nombre';
}
```

Crear `app/Observers/OdsMetaObserver.php`:

```php
<?php

namespace App\Observers;

class OdsMetaObserver extends EmbeddingObserver
{
    // Usa 'descripcion' por defecto
}
```

Crear `app/Observers/PndEjeObserver.php`:

```php
<?php

namespace App\Observers;

class PndEjeObserver extends EmbeddingObserver
{
    protected string $descriptionField = 'nombre';
}
```

Crear `app/Observers/PndObjetivoObserver.php`:

```php
<?php

namespace App\Observers;

class PndObjetivoObserver extends EmbeddingObserver
{
    // Usa 'descripcion' por defecto
}
```

Crear `app/Observers/PndEstrategiaObserver.php`:

```php
<?php

namespace App\Observers;

class PndEstrategiaObserver extends EmbeddingObserver
{
    // Usa 'descripcion' por defecto
}
```

Crear `app/Observers/PedObserver.php`:

```php
<?php

namespace App\Observers;

class PedObserver extends EmbeddingObserver
{
    // Observer base para todos los modelos PED
    // Usa 'descripcion' por defecto
}
```

Crear `app/Observers/ProgramaDerivadoObjetivoObserver.php`:

```php
<?php

namespace App\Observers;

class ProgramaDerivadoObjetivoObserver extends EmbeddingObserver
{
    // Usa 'descripcion' por defecto
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
use App\Models\ProgramaDerivadoObjetivo;
use App\Observers\OdsMetaObserver;
use App\Observers\OdsObjetivoObserver;
use App\Observers\PedObserver;
use App\Observers\PndEjeObserver;
use App\Observers\PndEstrategiaObserver;
use App\Observers\PndObjetivoObserver;
use App\Observers\ProgramaDerivadoObjetivoObserver;
use App\Services\Embeddings\EmbeddingService;
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
        if ($this->shouldRegisterObservers()) {
            $this->registerEmbeddingObservers();
        }
    }

    /**
     * Determina si los observers deben registrarse.
     */
    protected function shouldRegisterObservers(): bool
    {
        // No registrar en testing a menos que se solicite explícitamente
        if ($this->app->environment('testing')) {
            return config('embedding.observers_enabled', false);
        }

        return config('embedding.observers_enabled', true);
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

        // Programas Derivados
        ProgramaDerivadoObjetivo::observe(ProgramaDerivadoObjetivoObserver::class);
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
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => 5,
            'after_commit' => true,
        ],
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table' => 'failed_jobs',
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
    protected $signature = 'queue:embeddings 
        {--timeout= : Timeout en segundos}
        {--tries= : Número de intentos}
        {--max-jobs=100 : Máximo de jobs antes de parar}
        {--max-time=3600 : Máximo tiempo de ejecución}
        {--stop-when-empty : Parar cuando la cola esté vacía}';

    protected $description = 'Procesa la cola de embeddings exclusivamente';

    public function handle(): int
    {
        $this->info('Iniciando worker para cola de embeddings...');
        $this->info('Presiona Ctrl+C para detener');

        $params = [
            '--queue' => config('embedding.queue', 'embeddings'),
            '--timeout' => $this->option('timeout') ?? config('embedding.timeout_job', 60),
            '--tries' => $this->option('tries') ?? config('embedding.tries', 3),
            '--max-jobs' => $this->option('max-jobs'),
            '--max-time' => $this->option('max-time'),
        ];

        if ($this->option('stop-when-empty')) {
            $params['--stop-when-empty'] = true;
        }

        $this->call('queue:work', $params);

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

# API Configuration
EMBEDDING_API_KEY=your-api-key-here
EMBEDDING_API_URL=https://api.openai.com/v1/embeddings
EMBEDDING_MODEL=text-embedding-ada-002
EMBEDDING_DIMENSION=1536

# Rate Limiting
EMBEDDING_RATE_LIMIT=60
EMBEDDING_TIMEOUT=30

# Queue Configuration
EMBEDDING_QUEUE=embeddings
EMBEDDING_JOB_TRIES=3
EMBEDDING_JOB_TIMEOUT=60

# Chunking
EMBEDDING_MAX_TOKENS=8000

# Observers
EMBEDDING_OBSERVERS_ENABLED=true
```

---

### 11. Crear Tests Unitarios

```bash
sail artisan make:test Unit/Embeddings/EmbeddingServiceTest --unit
sail artisan make:test Unit/Embeddings/GenerateEmbeddingJobTest --unit
```

Editar `tests/Unit/Embeddings/EmbeddingServiceTest.php`:

```php
<?php

namespace Tests\Unit\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use App\Services\Embeddings\EmbeddingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'embedding.api_key' => 'test-api-key',
            'embedding.api_url' => 'https://api.test.com/v1/embeddings',
            'embedding.model' => 'test-model',
            'embedding.dimension' => 1536,
            'embedding.timeout' => 30,
            'embedding.max_tokens' => 8000,
        ]);
    }

    public function test_genera_embedding_correctamente(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.1)],
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
        $this->expectExceptionMessage('no puede estar vacío');
        
        $service->generate('');
    }

    public function test_lanza_excepcion_si_texto_solo_espacios(): void
    {
        $service = new EmbeddingService();

        $this->expectException(\InvalidArgumentException::class);
        
        $service->generate('   ');
    }

    public function test_lanza_excepcion_si_api_falla(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'error' => ['message' => 'Invalid API key'],
            ], 401),
        ]);

        $service = new EmbeddingService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Embedding API error');
        
        $service->generate('Texto de prueba');
    }

    public function test_lanza_excepcion_si_respuesta_dimension_incorrecta(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => [0.1, 0.2]], // Solo 2 elementos
                ],
            ], 200),
        ]);

        $service = new EmbeddingService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid embedding dimension');
        
        $service->generate('Texto de prueba');
    }

    public function test_lanza_excepcion_si_respuesta_no_es_array(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => 'invalid'],
                ],
            ], 200),
        ]);

        $service = new EmbeddingService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not an array');
        
        $service->generate('Texto de prueba');
    }

    public function test_trunca_texto_largo(): void
    {
        Http::fake([
            'api.test.com/*' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 1536, 0.5)],
                ],
            ], 200),
        ]);

        $service = new EmbeddingService();
        $textoLargo = str_repeat('a', 50000);
        
        $embedding = $service->generate($textoLargo);

        $this->assertCount(1536, $embedding);
        
        // Verificar que se truncó antes de enviar
        Http::assertSent(function ($request) {
            $input = $request->data()['input'];
            return strlen($input) <= 32000; // 8000 * 4
        });
    }

    public function test_get_dimension(): void
    {
        $service = new EmbeddingService();
        
        $this->assertEquals(1536, $service->getDimension());
    }

    public function test_get_model(): void
    {
        $service = new EmbeddingService();
        
        $this->assertEquals('test-model', $service->getModel());
    }

    public function test_connection_error_generates_log(): void
    {
        Http::fake([
            'api.test.com/*' => Http::failedConnection(),
        ]);

        $service = new EmbeddingService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection error');
        
        $service->generate('Texto de prueba');
    }
}
```

Editar `tests/Unit/Embeddings/GenerateEmbeddingJobTest.php`:

```php
<?php

namespace Tests\Unit\Embeddings;

use App\Contracts\EmbeddingServiceInterface;
use App\Jobs\Embeddings\GenerateEmbedding;
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
        $mockService = Mockery::mock(EmbeddingServiceInterface::class);
        $mockService->shouldReceive('generate')
            ->once()
            ->with('Texto de prueba')
            ->andReturn(array_fill(0, 1536, 0.5));

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

        $job->handle($mockService);

        $this->assertDatabaseHas('ods_objetivos', [
            'id' => $odsObjetivo->id,
        ]);

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
            9999,
            'Texto de prueba'
        );

        $job->handle($mockService);

        $this->assertTrue(true);
    }

    public function test_job_tiene_tries_configurado(): void
    {
        config(['embedding.tries' => 5]);
        
        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $this->assertEquals(5, $job->tries);
    }

    public function test_job_tiene_backoff_configurado(): void
    {
        config(['embedding.backoff' => [15, 120, 600]]);
        
        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $this->assertEquals([15, 120, 600], $job->backoff);
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

    public function test_job_usa_cola_correcta(): void
    {
        config(['embedding.queue' => 'custom-embeddings']);
        
        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $this->assertEquals('custom-embeddings', $job->queue);
    }

    public function test_job_failed_registra_error(): void
    {
        Log::shouldReceive('error')->once();

        $job = new GenerateEmbedding(
            OdsObjetivo::class,
            1,
            'Test'
        );

        $job->failed(new \Exception('Test error'));

        // No debe lanzar excepción
        $this->assertTrue(true);
    }
}
```

---

### 12. Crear Tests de Feature

```bash
sail artisan make:test Feature/Embeddings/EmbeddingObserverTest
```

Editar `tests/Feature/Embeddings/EmbeddingObserverTest.php`:

```php
<?php

namespace Tests\Feature\Embeddings;

use App\Jobs\Embeddings\GenerateEmbedding;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmbeddingObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['embedding.observers_enabled' => true]);
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

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Queue::fake();

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje Test',
            'descripcion' => null,
        ]);

        // Observer base usa 'descripcion', si es null no despacha
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

        Queue::assertPushed(GenerateEmbedding::class, 1);

        Queue::fake();

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

        Queue::assertPushed(GenerateEmbedding::class, 1);

        Queue::fake();

        $odsObjetivo->update(['numero' => 2]);

        Queue::assertNotPushed(GenerateEmbedding::class);
    }

    // ============================================
    // Tests de Cola
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
    // Tests de Observers Deshabilitados
    // ============================================

    public function test_observers_deshabilitados_no_despachan_job(): void
    {
        config(['embedding.observers_enabled' => false]);

        Queue::fake();

        OdsObjetivo::create([
            'numero' => 1,
            'nombre' => 'Test',
        ]);

        Queue::assertNotPushed(GenerateEmbedding::class);
    }
}
```

---

### 13. Ejecutar y Verificar

```bash
# Ejecutar tests
sail artisan test --filter EmbeddingServiceTest
sail artisan test --filter GenerateEmbeddingJobTest
sail artisan test --filter EmbeddingObserverTest

# Iniciar worker de embeddings
sail artisan queue:embeddings

# Verificar jobs en cola
sail artisan queue:work --queue=embeddings --once
```

---

## Criterios de Aceptación

- [ ] Interfaz `EmbeddingServiceInterface` creada en `App\Contracts\`
- [ ] `EmbeddingService` registrado como singleton en `AppServiceProvider`
- [ ] `EmbeddingService::generate()` retorna array de 1536 floats
- [ ] `GenerateEmbedding` job usa cola `embeddings`
- [ ] Job con `$tries` configurable desde config
- [ ] Job con `$backoff` configurable desde config
- [ ] Observers registrados en todos los modelos con columna `embedding`
- [ ] Observer solo despacha job si campo de descripción cambió (`isDirty`)
- [ ] Al crear registro, embedding se genera automáticamente
- [ ] Al actualizar descripción, embedding se regenera
- [ ] Si API falla después de reintentos, job marca como `failed` sin bloquear
- [ ] Test: crear modelo despacha job
- [ ] Test: actualizar descripción despacha job; actualizar otro campo no
- [ ] `.env.example` documentado con variables de configuración
- [ ] Configuración centralizada en `config/embedding.php`

---

## Resumen de Correcciones Aplicadas