# S3-T8: Servicio Centralizado de Llamadas al LLM — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S3-T8
**Tipo:** feat
**Rama:** `feat/S3-T8-servicio-llm`
**Sprint:** 3 — Metodología de Marco Lógico (Etapas 1-4)
**Depende de:** Ninguno (independiente)

**Goal:** Crear la clase `LlmService` que encapsula todas las llamadas al modelo de lenguaje, con prompts almacenados como vistas Blade, rate limiting, logging y manejo de errores.

**Architecture:** Servicio registrado en el Service Container con interfaz para facilitar mocking. Los prompts se almacenan como vistas Blade en `resources/views/prompts/`. Las llamadas se ejecutan vía jobs en cola Redis. Se incluye rate limiting configurable, logging de todas las llamadas (prompt, respuesta, tokens, duración) y manejo robusto de errores.

**Tech Stack:** Laravel 12, OpenAI API (o compatible), Redis Queue, Blade (prompts)

---

## Pre-requisitos

- Redis configurado y funcionando (S0-T4)
- Config de API key para LLM

---

## Pasos

### Task 1: Crear configuración config/llm.php

**Files:**
- Create: `config/llm.php`

**Step 1: Crear el archivo de configuración**

```php
<?php

return [
    'api_key' => env('LLM_API_KEY', ''),
    'api_url' => env('LLM_API_URL', 'https://api.openai.com/v1/chat/completions'),
    'model' => env('LLM_MODEL', 'gpt-4o-mini'),
    'max_tokens' => (int) env('LLM_MAX_TOKENS', 2000),
    'temperature' => (float) env('LLM_TEMPERATURE', 0.7),
    'timeout' => (int) env('LLM_TIMEOUT', 60),

    'rate_limit' => [
        'max_per_minute' => (int) env('LLM_RATE_LIMIT', 30),
    ],

    'queue' => [
        'name' => env('LLM_QUEUE', 'llm'),
        'tries' => (int) env('LLM_JOB_TRIES', 3),
        'backoff' => [10, 60, 300],
        'timeout' => (int) env('LLM_JOB_TIMEOUT', 120),
    ],

    'logging' => [
        'enabled' => env('LLM_LOGGING_ENABLED', true),
        'channel' => env('LLM_LOG_CHANNEL', 'stack'),
    ],
];
```

**Step 2: Commit**

```bash
git add config/llm.php
git commit -m "feat(S3-T8): add LLM configuration file"
```

---

### Task 2: Crear migración para tabla llm_logs

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_llm_logs_table.php`

**Step 1: Crear la migración**

```bash
sail artisan make:migration create_llm_logs_table
```

**Step 2: Escribir la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 30); // suggest, validate, transform
            $table->string('prompt_template')->nullable(); // nombre de la vista Blade
            $table->text('prompt_text');
            $table->text('response_text')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('model')->nullable();
            $table->string('status', 20)->default('pending'); // pending, success, error
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_logs');
    }
};
```

**Step 3: Ejecutar migración**

```bash
sail artisan migrate
```

**Step 4: Commit**

```bash
git add database/migrations/*create_llm_logs_table*
git commit -m "feat(S3-T8): create llm_logs table for request logging"
```

---

### Task 3: Crear modelo LlmLog

**Files:**
- Create: `app/Models/LlmLog.php`

**Step 1: Crear el modelo**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmLog extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'prompt_template',
        'prompt_text',
        'response_text',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'duration_ms',
        'model',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'total_tokens' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

**Step 2: Commit**

```bash
git add app/Models/LlmLog.php
git commit -m "feat(S3-T8): add LlmLog model"
```

---

### Task 4: Crear interfaz LlmServiceInterface y DTO ValidationResult

**Files:**
- Create: `app/Contracts/LlmServiceInterface.php`
- Create: `app/DTOs/LlmValidationResult.php`

**Step 1: Crear la interfaz**

```php
<?php

namespace App\Contracts;

use App\DTOs\LlmValidationResult;

interface LlmServiceInterface
{
    /**
     * Genera una sugerencia de texto basada en el prompt y contexto.
     */
    public function suggest(string $prompt, array $context = []): string;

    /**
     * Valida un texto contra un conjunto de reglas.
     */
    public function validate(string $text, array $rules): LlmValidationResult;

    /**
     * Transforma un texto siguiendo una instrucción.
     */
    public function transform(string $text, string $instruction): string;
}
```

**Step 2: Crear DTO ValidationResult**

```php
<?php

namespace App\DTOs;

class LlmValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $issues,
        public readonly string $suggestion,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isValid: $data['is_valid'] ?? false,
            issues: $data['issues'] ?? [],
            suggestion: $data['suggestion'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'issues' => $this->issues,
            'suggestion' => $this->suggestion,
        ];
    }
}
```

**Step 3: Commit**

```bash
git add app/Contracts/LlmServiceInterface.php app/DTOs/LlmValidationResult.php
git commit -m "feat(S3-T8): add LlmServiceInterface and LlmValidationResult DTO"
```

---

### Task 5: Crear LlmService

**Files:**
- Create: `app/Services/Llm/LlmService.php`
- Create: `tests/Unit/Llm/LlmServiceTest.php`

**Step 1: Escribir tests**

Crear `tests/Unit/Llm/LlmServiceTest.php`:

```php
<?php

namespace Tests\Unit\Llm;

use App\Contracts\LlmServiceInterface;
use App\DTOs\LlmValidationResult;
use App\Models\LlmLog;
use App\Services\Llm\LlmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmServiceTest extends TestCase
{
    use RefreshDatabase;

    private LlmService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['llm.api_key' => 'test-key']);
        config(['llm.api_url' => 'https://api.openai.com/v1/chat/completions']);
        config(['llm.model' => 'gpt-4o-mini']);
        config(['llm.logging.enabled' => true]);
        $this->service = new LlmService();
    }

    public function test_suggest_returns_text(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Sugerencia de IA']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        $result = $this->service->suggest('Mejora esta redacción', ['text' => 'texto original']);

        $this->assertEquals('Sugerencia de IA', $result);
    }

    public function test_suggest_logs_request(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Respuesta']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ], 200),
        ]);

        $this->service->suggest('Test prompt');

        $this->assertDatabaseHas('llm_logs', [
            'method' => 'suggest',
            'status' => 'success',
        ]);
    }

    public function test_validate_returns_validation_result(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'is_valid' => false,
                    'issues' => ['Contiene verbos de solución'],
                    'suggestion' => 'Reformule sin verbos',
                ])]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 15, 'total_tokens' => 25],
            ], 200),
        ]);

        $result = $this->service->validate('Implementar sistema', ['no_verbos_solucion']);

        $this->assertInstanceOf(LlmValidationResult::class, $result);
        $this->assertFalse($result->isValid);
        $this->assertCount(1, $result->issues);
    }

    public function test_transform_returns_transformed_text(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Texto transformado positivamente']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 8, 'total_tokens' => 18],
            ], 200),
        ]);

        $result = $this->service->transform('Alta deserción escolar', 'Convertir a positivo');

        $this->assertEquals('Texto transformado positivamente', $result);
    }

    public function test_handles_api_error(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'Rate limited']], 429),
        ]);

        $this->expectException(\App\Exceptions\LlmException::class);

        $this->service->suggest('Test prompt');
    }

    public function test_logs_error_on_failure(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'Server error']], 500),
        ]);

        try {
            $this->service->suggest('Test prompt');
        } catch (\App\Exceptions\LlmException $e) {
            // Expected
        }

        $this->assertDatabaseHas('llm_logs', [
            'method' => 'suggest',
            'status' => 'error',
        ]);
    }

    public function test_implements_interface(): void
    {
        $this->assertInstanceOf(LlmServiceInterface::class, $this->service);
    }
}
```

**Step 2: Ejecutar tests para verificar que fallan**

```bash
sail artisan test --filter=LlmServiceTest
```

Expected: FAIL.

**Step 3: Crear LlmException**

Crear `app/Exceptions/LlmException.php`:

```php
<?php

namespace App\Exceptions;

use RuntimeException;

class LlmException extends RuntimeException
{
    public static function apiError(string $message, int $statusCode): self
    {
        return new self("LLM API error ({$statusCode}): {$message}", $statusCode);
    }

    public static function timeout(): self
    {
        return new self('LLM API request timed out');
    }

    public static function invalidResponse(string $details): self
    {
        return new self("LLM API returned invalid response: {$details}");
    }
}
```

**Step 4: Crear LlmService**

Crear `app/Services/Llm/LlmService.php`:

```php
<?php

namespace App\Services\Llm;

use App\Contracts\LlmServiceInterface;
use App\DTOs\LlmValidationResult;
use App\Exceptions\LlmException;
use App\Models\LlmLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LlmService implements LlmServiceInterface
{
    public function suggest(string $prompt, array $context = []): string
    {
        $messages = $this->buildMessages($prompt, $context);

        return $this->call('suggest', $messages, $prompt);
    }

    public function validate(string $text, array $rules): LlmValidationResult
    {
        $rulesStr = implode(', ', $rules);
        $prompt = "Valida el siguiente texto contra estas reglas: [{$rulesStr}]. Texto: \"{$text}\". Responde en JSON con: {\"is_valid\": bool, \"issues\": [string], \"suggestion\": string}";

        $messages = [
            ['role' => 'system', 'content' => 'Eres un validador de textos para metodología de marco lógico. Siempre responde en JSON válido.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        $responseText = $this->call('validate', $messages, $prompt);

        $data = json_decode($responseText, true);

        if (!is_array($data)) {
            throw LlmException::invalidResponse('Response is not valid JSON');
        }

        return LlmValidationResult::fromArray($data);
    }

    public function transform(string $text, string $instruction): string
    {
        $prompt = "{$instruction}: \"{$text}\"";

        $messages = [
            ['role' => 'system', 'content' => 'Eres un asistente de redacción para metodología de marco lógico. Responde solo con el texto transformado, sin explicaciones adicionales.'],
            ['role' => 'user', 'content' => $prompt],
        ];

        return $this->call('transform', $messages, $prompt);
    }

    /**
     * Renderiza un prompt desde una vista Blade.
     */
    public function renderPrompt(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }

    /**
     * Llama a la API y registra el log.
     */
    private function call(string $method, array $messages, string $promptText, ?string $promptTemplate = null): string
    {
        $this->checkRateLimit();

        $log = LlmLog::create([
            'user_id' => auth()->id(),
            'method' => $method,
            'prompt_template' => $promptTemplate,
            'prompt_text' => $promptText,
            'model' => config('llm.model'),
            'status' => 'pending',
        ]);

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('llm.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->timeout(config('llm.timeout', 60))
            ->post(config('llm.api_url'), [
                'model' => config('llm.model'),
                'messages' => $messages,
                'max_tokens' => config('llm.max_tokens', 2000),
                'temperature' => config('llm.temperature', 0.7),
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $errorMsg = $response->json('error.message', 'Unknown error');
                $log->update([
                    'status' => 'error',
                    'error_message' => $errorMsg,
                    'duration_ms' => $durationMs,
                ]);
                throw LlmException::apiError($errorMsg, $response->status());
            }

            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? '';
            $usage = $responseData['usage'] ?? [];

            $log->update([
                'status' => 'success',
                'response_text' => $content,
                'prompt_tokens' => $usage['prompt_tokens'] ?? null,
                'completion_tokens' => $usage['completion_tokens'] ?? null,
                'total_tokens' => $usage['total_tokens'] ?? null,
                'duration_ms' => $durationMs,
            ]);

            return trim($content);

        } catch (LlmException $e) {
            throw $e;
        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $log->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                throw LlmException::timeout();
            }

            throw LlmException::apiError($e->getMessage(), 0);
        }
    }

    private function buildMessages(string $prompt, array $context): array
    {
        $messages = [
            ['role' => 'system', 'content' => 'Eres un asistente especializado en metodología de marco lógico para programas presupuestarios del sector público mexicano.'],
        ];

        if (!empty($context)) {
            $contextStr = collect($context)->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");
            $messages[] = ['role' => 'user', 'content' => "Contexto:\n{$contextStr}\n\n{$prompt}"];
        } else {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        return $messages;
    }

    private function checkRateLimit(): void
    {
        $key = 'llm:' . (auth()->id() ?? 'system');
        $maxPerMinute = config('llm.rate_limit.max_per_minute', 30);

        if (!RateLimiter::attempt($key, $maxPerMinute, fn () => true, 60)) {
            throw LlmException::apiError('Rate limit exceeded', 429);
        }
    }
}
```

**Step 5: Ejecutar tests para verificar que pasan**

```bash
sail artisan test --filter=LlmServiceTest
```

Expected: PASS.

**Step 6: Commit**

```bash
git add app/Services/Llm/LlmService.php app/Exceptions/LlmException.php tests/Unit/Llm/LlmServiceTest.php
git commit -m "feat(S3-T8): implement LlmService with logging, rate limiting, and error handling"
```

---

### Task 6: Registrar servicio en Service Container

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`

**Step 1: Agregar binding en register()**

```php
use App\Contracts\LlmServiceInterface;
use App\Services\Llm\LlmService;

// En register():
$this->app->singleton(LlmServiceInterface::class, LlmService::class);
```

**Step 2: Commit**

```bash
git add app/Providers/AppServiceProvider.php
git commit -m "feat(S3-T8): register LlmService in service container"
```

---

### Task 7: Crear carpeta de prompts Blade y prompt de ejemplo

**Files:**
- Create: `resources/views/prompts/mml/validar-problema.blade.php`

**Step 1: Crear la carpeta y el prompt de ejemplo**

Crear `resources/views/prompts/mml/validar-problema.blade.php`:

```blade
Eres un experto en Metodología de Marco Lógico (MML) para el sector público mexicano.

Evalúa si el siguiente texto describe correctamente un problema central:

TEXTO: "{{ $texto }}"

REGLAS DE VALIDACIÓN:
1. NO debe contener verbos que impliquen soluciones (implementar, crear, desarrollar, mejorar)
2. DEBE describir una situación no deseada, no la ausencia de una solución
3. DEBE ser claro, concreto y verificable
4. NO debe ser demasiado amplio ni demasiado específico

Responde en JSON:
{
    "is_valid": true/false,
    "issues": ["lista de problemas encontrados"],
    "suggestion": "versión mejorada del texto si no es válido, o vacío si es válido"
}
```

**Step 2: Commit**

```bash
git add resources/views/prompts/
git commit -m "feat(S3-T8): add prompts Blade folder with MML problem validation prompt"
```

---

### Task 8: Crear job ProcessLlmRequest

**Files:**
- Create: `app/Jobs/ProcessLlmRequest.php`

**Step 1: Crear el job**

```php
<?php

namespace App\Jobs;

use App\Contracts\LlmServiceInterface;
use App\Models\LlmLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLlmRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public array $backoff;
    public int $timeout;

    public function __construct(
        public readonly string $method,
        public readonly string $prompt,
        public readonly array $context = [],
        public readonly ?int $userId = null,
        public readonly ?string $callbackEvent = null,
    ) {
        $this->onQueue(config('llm.queue.name', 'llm'));
        $this->tries = config('llm.queue.tries', 3);
        $this->backoff = config('llm.queue.backoff', [10, 60, 300]);
        $this->timeout = config('llm.queue.timeout', 120);
    }

    public function handle(LlmServiceInterface $llmService): void
    {
        $result = match ($this->method) {
            'suggest' => $llmService->suggest($this->prompt, $this->context),
            'transform' => $llmService->transform($this->prompt, $this->context['instruction'] ?? ''),
            default => $llmService->suggest($this->prompt, $this->context),
        };

        if ($this->callbackEvent) {
            event($this->callbackEvent, ['result' => $result, 'user_id' => $this->userId]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessLlmRequest failed', [
            'method' => $this->method,
            'prompt' => substr($this->prompt, 0, 200),
            'error' => $exception->getMessage(),
        ]);
    }
}
```

**Step 2: Commit**

```bash
git add app/Jobs/ProcessLlmRequest.php
git commit -m "feat(S3-T8): add ProcessLlmRequest queued job"
```

---

### Task 9: Ejecutar test suite completo y verificar

**Step 1: Ejecutar todos los tests**

```bash
sail artisan test
```

Expected: Baseline + todos los nuevos tests pasan.

---

## Criterios de Aceptación

- [ ] Servicio registrado en el Service Container de Laravel
- [ ] Llamadas ejecutadas vía jobs en cola Redis
- [ ] Rate limiting configurable por usuario/sesión
- [ ] Manejo de errores: timeout, API caído, respuesta inválida
- [ ] Logging de todas las llamadas (prompt, respuesta, tokens, duración)
- [ ] Interfaz fácilmente mockeable para tests
- [ ] Carpeta `resources/views/prompts/` con al menos un prompt de ejemplo
- [ ] Tests pasan
