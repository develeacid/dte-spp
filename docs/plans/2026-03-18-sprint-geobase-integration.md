# Sprint: Integración MIR ↔ GeoBase — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Conectar dte-spp-2026 (MIR) con GeoBase para consultar padrón, recibir webhooks y solicitar snapshots.

**Tech Stack:** Laravel 12, PHP 8.2+, PostgreSQL 16, PHPUnit 11, Livewire 3

**Design Doc:** `docs/plans/2026-03-18-sprint-geobase-integration-design.md`

**Baseline:** 426 tests passed, 7 skipped, 0 failures

---

## Task 1: Configuración y GeoBaseClient base

**Files:**
- Modify: `config/services.php`
- Modify: `.env.example`
- Create: `app/Services/GeoBase/GeoBaseClient.php`
- Create: `app/Services/GeoBase/GeoBaseException.php`
- Test: `tests/Unit/Services/GeoBase/GeoBaseClientTest.php`

**Step 1: Write failing tests**

```php
// tests/Unit/Services/GeoBase/GeoBaseClientTest.php
<?php

namespace Tests\Unit\Services\GeoBase;

use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoBaseClientTest extends TestCase
{
    private GeoBaseClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.geobase.url' => 'http://geobase-test:8081/api/v1/geobase',
            'services.geobase.token' => 'test-token-123',
            'services.geobase.timeout' => 10,
            'services.geobase.retry_times' => 2,
            'services.geobase.retry_sleep' => 100,
        ]);

        $this->client = app(GeoBaseClient::class);
    }

    public function test_get_beneficiary_returns_data(): void
    {
        Http::fake([
            '*/beneficiaries/42' => Http::response([
                'data' => [
                    'id' => 42,
                    'type' => 'persona_fisica',
                    'nombre' => 'Carlos',
                    'apellidos' => 'García',
                    'address_municipality' => 'Oaxaca de Juárez',
                ],
            ], 200),
        ]);

        $result = $this->client->getBeneficiary(42);

        $this->assertEquals(42, $result['data']['id']);
        $this->assertEquals('Carlos', $result['data']['nombre']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/beneficiaries/42')
                && $request->hasHeader('Authorization', 'Bearer test-token-123');
        });
    }

    public function test_upsert_beneficiary_sends_correct_payload(): void
    {
        Http::fake([
            '*/beneficiaries' => Http::response([
                'beneficiary_id' => 42,
                'created' => true,
            ], 201),
        ]);

        $data = [
            'curp_rfc' => 'GARC850101HOCRRL09',
            'type' => 'persona_fisica',
            'nombre' => 'Carlos',
            'apellidos' => 'García López',
            'address_municipality' => 'Oaxaca de Juárez',
            'address_state' => 'Oaxaca',
        ];

        $result = $this->client->upsertBeneficiary($data);

        $this->assertEquals(42, $result['beneficiary_id']);
        $this->assertTrue($result['created']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/beneficiaries')
                && $request['curp_rfc'] === 'GARC850101HOCRRL09';
        });
    }

    public function test_get_program_coverage_returns_stats(): void
    {
        Http::fake([
            '*/programs/3/coverage' => Http::response([
                'data' => [
                    'total_enrollments' => 500,
                    'aprobados' => 450,
                    'rechazados' => 20,
                    'pendientes' => 30,
                ],
            ], 200),
        ]);

        $result = $this->client->getProgramCoverage(3);

        $this->assertEquals(500, $result['data']['total_enrollments']);
        $this->assertEquals(450, $result['data']['aprobados']);
    }

    public function test_request_snapshot_sends_params(): void
    {
        Http::fake([
            '*/snapshot' => Http::response([
                'data' => [
                    'id' => 7,
                    'snapshot_hash' => 'a1b2c3d4e5f6',
                    'valor_oficial' => 150,
                    'evidencia_url' => 'https://minio.local/snapshots/7.csv',
                ],
            ], 201),
        ]);

        $result = $this->client->requestSnapshot([
            'component_id' => 45,
            'period' => '2026-Q1',
            'cutoff_date' => '2026-03-31',
        ]);

        $this->assertEquals('a1b2c3d4e5f6', $result['data']['snapshot_hash']);
        $this->assertEquals(150, $result['data']['valor_oficial']);
    }

    public function test_validate_curp_returns_existence(): void
    {
        Http::fake([
            '*/validation/curp' => Http::response([
                'exists' => true,
                'beneficiary_id' => 42,
                'active' => true,
            ], 200),
        ]);

        $result = $this->client->validateCurp('GARC850101HOCRRL09');

        $this->assertTrue($result['exists']);
        $this->assertEquals(42, $result['beneficiary_id']);
    }

    public function test_throws_exception_on_server_error(): void
    {
        Http::fake([
            '*/beneficiaries/999' => Http::response(['error' => 'Not found'], 404),
        ]);

        $this->expectException(GeoBaseException::class);

        $this->client->getBeneficiary(999);
    }

    public function test_throws_exception_on_connection_failure(): void
    {
        Http::fake([
            '*/beneficiaries/1' => Http::response(null, 500),
        ]);

        $this->expectException(GeoBaseException::class);

        $this->client->getBeneficiary(1);
    }

    public function test_sends_authorization_header(): void
    {
        Http::fake([
            '*/beneficiaries/1' => Http::response(['data' => ['id' => 1]], 200),
        ]);

        $this->client->getBeneficiary(1);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-token-123')
                && $request->hasHeader('Accept', 'application/json');
        });
    }
}
```

**Step 2: Run tests to verify they fail**

Run: `./vendor/bin/sail artisan test tests/Unit/Services/GeoBase/GeoBaseClientTest.php`
Expected: FAIL — classes not found

**Step 3: Add config and env vars**

In `config/services.php`, add after the last entry:

```php
'geobase' => [
    'url' => env('GEOBASE_API_URL', 'http://localhost:8081/api/v1/geobase'),
    'token' => env('GEOBASE_API_TOKEN'),
    'webhook_secret' => env('GEOBASE_WEBHOOK_SECRET'),
    'timeout' => (int) env('GEOBASE_API_TIMEOUT', 15),
    'retry_times' => (int) env('GEOBASE_API_RETRY_TIMES', 3),
    'retry_sleep' => (int) env('GEOBASE_API_RETRY_SLEEP', 500),
],
```

In `.env.example`, add at the end:

```
# GeoBase Integration
GEOBASE_API_URL=http://localhost:8081/api/v1/geobase
GEOBASE_API_TOKEN=
GEOBASE_WEBHOOK_SECRET=
GEOBASE_API_TIMEOUT=15
GEOBASE_API_RETRY_TIMES=3
GEOBASE_API_RETRY_SLEEP=500
```

**Step 4: Implement GeoBaseException**

```php
// app/Services/GeoBase/GeoBaseException.php
<?php

namespace App\Services\GeoBase;

use RuntimeException;

class GeoBaseException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?array $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
```

**Step 5: Implement GeoBaseClient**

```php
// app/Services/GeoBase/GeoBaseClient.php
<?php

namespace App\Services\GeoBase;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeoBaseClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.geobase.url'), '/');
        $this->token = config('services.geobase.token');
    }

    // --- Beneficiarios ---

    public function getBeneficiary(int $id): array
    {
        return $this->get("/beneficiaries/{$id}");
    }

    public function upsertBeneficiary(array $data): array
    {
        return $this->post('/beneficiaries', $data);
    }

    // --- Inscripciones ---

    public function createEnrollment(array $data): array
    {
        return $this->post('/enrollments', $data);
    }

    public function getEnrollments(array $filters = []): array
    {
        return $this->get('/enrollments', $filters);
    }

    // --- Validación ---

    public function validateCurp(string $curp): array
    {
        return $this->post('/validation/curp', ['curp' => $curp]);
    }

    public function validateLocation(float $lat, float $lng, int $programId): array
    {
        return $this->post('/validation/location', [
            'lat' => $lat,
            'lng' => $lng,
            'program_id' => $programId,
        ]);
    }

    // --- Programas ---

    public function getProgramCoverage(int $programId): array
    {
        return $this->get("/programs/{$programId}/coverage");
    }

    // --- Snapshots ---

    public function requestSnapshot(array $params): array
    {
        return $this->post('/snapshot', $params);
    }

    // --- HTTP helpers ---

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->timeout(config('services.geobase.timeout', 15))
            ->retry(
                config('services.geobase.retry_times', 3),
                config('services.geobase.retry_sleep', 500),
            );
    }

    private function get(string $path, array $query = []): array
    {
        $response = $this->request()->get($path, $query);

        return $this->handleResponse($response);
    }

    private function post(string $path, array $data = []): array
    {
        $response = $this->request()->post($path, $data);

        return $this->handleResponse($response);
    }

    private function handleResponse(Response $response): array
    {
        if ($response->failed()) {
            throw new GeoBaseException(
                message: "GeoBase API error: {$response->status()} — {$response->body()}",
                statusCode: $response->status(),
                responseBody: $response->json(),
            );
        }

        return $response->json();
    }
}
```

**Step 6: Run tests to verify they pass**

Run: `./vendor/bin/sail artisan test tests/Unit/Services/GeoBase/GeoBaseClientTest.php`
Expected: PASS (8 tests)

**Step 7: Commit**

```bash
git add app/Services/GeoBase/ config/services.php .env.example tests/Unit/Services/GeoBase/
git commit -m "feat(geobase): add GeoBaseClient HTTP service with config

Wraps all GeoBase API endpoints with retry, timeout, and error
handling. Http::fake() compatible for testing.

Resolves DTE-XXX"
```

---

## Task 2: Migración `geobase_program_id` + UI de enlace (Momento 1)

**Files:**
- Create: `database/migrations/XXXX_add_geobase_program_id_to_programa_presupuestarios.php`
- Modify: `app/Models/ProgramaPresupuestario.php`
- Test: `tests/Feature/GeoBase/ProgramLinkTest.php`

**Step 1: Write failing tests**

```php
// tests/Feature/GeoBase/ProgramLinkTest.php
<?php

namespace Tests\Feature\GeoBase;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProgramLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    public function test_programa_can_store_geobase_program_id(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'geobase_program_id' => 3,
        ]);

        $this->assertEquals(3, $programa->geobase_program_id);
        $this->assertDatabaseHas('programa_presupuestarios', [
            'id' => $programa->id,
            'geobase_program_id' => 3,
        ]);
    }

    public function test_geobase_program_id_is_nullable(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->assertNull($programa->geobase_program_id);
    }

    public function test_has_geobase_link_returns_correct_boolean(): void
    {
        $linked = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'geobase_program_id' => 3,
        ]);
        $unlinked = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->assertTrue($linked->hasGeoBaseLink());
        $this->assertFalse($unlinked->hasGeoBaseLink());
    }

    public function test_can_fetch_geobase_coverage(): void
    {
        Http::fake([
            '*/programs/3/coverage' => Http::response([
                'data' => [
                    'total_enrollments' => 500,
                    'aprobados' => 450,
                ],
            ], 200),
        ]);

        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'geobase_program_id' => 3,
        ]);

        $coverage = $programa->getGeoBaseCoverage();

        $this->assertEquals(500, $coverage['data']['total_enrollments']);
    }

    public function test_geobase_coverage_returns_null_when_not_linked(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->assertNull($programa->getGeoBaseCoverage());
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test tests/Feature/GeoBase/ProgramLinkTest.php`
Expected: FAIL

**Step 3: Create migration**

```php
// database/migrations/XXXX_add_geobase_program_id_to_programa_presupuestarios.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->unsignedBigInteger('geobase_program_id')->nullable()->after('estado');
            $table->index('geobase_program_id');
        });
    }

    public function down(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->dropIndex(['geobase_program_id']);
            $table->dropColumn('geobase_program_id');
        });
    }
};
```

**Step 4: Update model**

Add to `ProgramaPresupuestario` fillable array:

```php
'geobase_program_id',
```

Add methods:

```php
public function hasGeoBaseLink(): bool
{
    return $this->geobase_program_id !== null;
}

public function getGeoBaseCoverage(): ?array
{
    if (! $this->hasGeoBaseLink()) {
        return null;
    }

    return app(\App\Services\GeoBase\GeoBaseClient::class)
        ->getProgramCoverage($this->geobase_program_id);
}
```

**Step 5: Run migration and tests**

Run: `./vendor/bin/sail artisan migrate`
Then: `./vendor/bin/sail artisan test tests/Feature/GeoBase/ProgramLinkTest.php`
Expected: PASS (5 tests)

**Step 6: Commit**

```bash
git add database/migrations/ app/Models/ProgramaPresupuestario.php tests/Feature/GeoBase/
git commit -m "feat(geobase): add geobase_program_id to programas and coverage query

Nullable FK for Momento 1 (enlace lógico). hasGeoBaseLink() and
getGeoBaseCoverage() methods for Momento 2 (enlace operativo).

Resolves DTE-XXX"
```

---

## Task 3: Webhook Handler con verificación HMAC

**Files:**
- Create: `app/Http/Middleware/VerifyGeoBaseWebhook.php`
- Create: `app/Http/Controllers/GeoBase/WebhookController.php`
- Create: `app/Events/GeoBase/EnrollmentStatusChanged.php`
- Create: `app/Events/GeoBase/SnapshotGenerated.php`
- Create: `app/Events/GeoBase/SyncProcessed.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/GeoBase/WebhookControllerTest.php`

**Step 1: Write failing tests**

```php
// tests/Feature/GeoBase/WebhookControllerTest.php
<?php

namespace Tests\Feature\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Events\GeoBase\SnapshotGenerated;
use App\Events\GeoBase\SyncProcessed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-webhook-secret-64chars-here-abcdefghijklmnopqrstuvwxyz123456';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.geobase.webhook_secret' => $this->secret]);
    }

    private function signPayload(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->secret);
    }

    public function test_accepts_valid_webhook_with_correct_signature(): void
    {
        Event::fake();

        $payload = [
            'event' => 'enrollment.status_changed',
            'data' => [
                'enrollment_id' => 42,
                'old_status' => 'solicitado',
                'new_status' => 'aprobado',
                'program_id' => 3,
                'timestamp' => '2026-03-18T12:00:00-06:00',
            ],
        ];

        $signature = $this->signPayload($payload);

        $response = $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        Event::assertDispatched(EnrollmentStatusChanged::class);
    }

    public function test_rejects_webhook_with_invalid_signature(): void
    {
        $payload = [
            'event' => 'enrollment.status_changed',
            'data' => ['enrollment_id' => 42],
        ];

        $response = $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(403);
    }

    public function test_rejects_webhook_without_signature(): void
    {
        $response = $this->postJson('/api/webhooks/geobase', [
            'event' => 'enrollment.status_changed',
            'data' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_dispatches_snapshot_generated_event(): void
    {
        Event::fake();

        $payload = [
            'event' => 'snapshot.generated',
            'data' => [
                'snapshot_id' => 7,
                'period' => '2026-Q1',
                'sha256' => 'a1b2c3d4e5f6',
                'component_id' => 2,
                'program_id' => 3,
                'valor_oficial' => 150,
                'timestamp' => '2026-03-18T12:00:00-06:00',
            ],
        ];

        $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => $this->signPayload($payload),
        ])->assertStatus(200);

        Event::assertDispatched(SnapshotGenerated::class, function ($event) {
            return $event->snapshotId === 7
                && $event->snapshotHash === 'a1b2c3d4e5f6';
        });
    }

    public function test_dispatches_sync_processed_event(): void
    {
        Event::fake();

        $payload = [
            'event' => 'sync.processed',
            'data' => [
                'entry_id' => 145,
                'operation' => 'create_beneficiary',
                'result_type' => 'App\\Models\\Beneficiary',
                'result_id' => 8492,
                'timestamp' => '2026-03-18T14:30:00-06:00',
            ],
        ];

        $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => $this->signPayload($payload),
        ])->assertStatus(200);

        Event::assertDispatched(SyncProcessed::class);
    }

    public function test_returns_200_for_unknown_event_type(): void
    {
        $payload = [
            'event' => 'unknown.event',
            'data' => ['foo' => 'bar'],
        ];

        $response = $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => $this->signPayload($payload),
        ]);

        // Acknowledge receipt, don't error — GeoBase may add new events
        $response->assertStatus(200);
    }

    public function test_logs_webhook_receipt(): void
    {
        $payload = [
            'event' => 'enrollment.status_changed',
            'data' => ['enrollment_id' => 42, 'old_status' => 'solicitado', 'new_status' => 'aprobado', 'program_id' => 3, 'timestamp' => now()->toIso8601String()],
        ];

        $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => $this->signPayload($payload),
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'geobase-webhook',
            'description' => 'enrollment.status_changed',
        ]);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test tests/Feature/GeoBase/WebhookControllerTest.php`
Expected: FAIL — classes/route not found

**Step 3: Implement middleware**

```php
// app/Http/Middleware/VerifyGeoBaseWebhook.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyGeoBaseWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-GeoBase-Signature');
        $secret = config('services.geobase.webhook_secret');

        if (! $signature || ! $secret) {
            return response()->json(['error' => 'Missing signature'], 403);
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
```

**Step 4: Implement events**

```php
// app/Events/GeoBase/EnrollmentStatusChanged.php
<?php

namespace App\Events\GeoBase;

use Illuminate\Foundation\Events\Dispatchable;

class EnrollmentStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $enrollmentId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly int $programId,
        public readonly string $timestamp,
    ) {}
}
```

```php
// app/Events/GeoBase/SnapshotGenerated.php
<?php

namespace App\Events\GeoBase;

use Illuminate\Foundation\Events\Dispatchable;

class SnapshotGenerated
{
    use Dispatchable;

    public function __construct(
        public readonly int $snapshotId,
        public readonly string $period,
        public readonly string $snapshotHash,
        public readonly int $componentId,
        public readonly int $programId,
        public readonly int $valorOficial,
        public readonly string $timestamp,
    ) {}
}
```

```php
// app/Events/GeoBase/SyncProcessed.php
<?php

namespace App\Events\GeoBase;

use Illuminate\Foundation\Events\Dispatchable;

class SyncProcessed
{
    use Dispatchable;

    public function __construct(
        public readonly int $entryId,
        public readonly string $operation,
        public readonly ?string $resultType,
        public readonly ?int $resultId,
        public readonly string $timestamp,
    ) {}
}
```

**Step 5: Implement controller**

```php
// app/Http/Controllers/GeoBase/WebhookController.php
<?php

namespace App\Http\Controllers\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Events\GeoBase\SnapshotGenerated;
use App\Events\GeoBase\SyncProcessed;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $eventType = $request->input('event');
        $data = $request->input('data', []);

        // Log with spatie activity log
        activity('geobase-webhook')
            ->withProperties($data)
            ->log($eventType);

        match ($eventType) {
            'enrollment.status_changed' => EnrollmentStatusChanged::dispatch(
                enrollmentId: $data['enrollment_id'],
                oldStatus: $data['old_status'],
                newStatus: $data['new_status'],
                programId: $data['program_id'],
                timestamp: $data['timestamp'],
            ),
            'snapshot.generated' => SnapshotGenerated::dispatch(
                snapshotId: $data['snapshot_id'],
                period: $data['period'],
                snapshotHash: $data['sha256'],
                componentId: $data['component_id'],
                programId: $data['program_id'],
                valorOficial: $data['valor_oficial'],
                timestamp: $data['timestamp'],
            ),
            'sync.processed' => SyncProcessed::dispatch(
                entryId: $data['entry_id'],
                operation: $data['operation'],
                resultType: $data['result_type'] ?? null,
                resultId: $data['result_id'] ?? null,
                timestamp: $data['timestamp'],
            ),
            default => null, // Acknowledge unknown events gracefully
        };

        return response()->json(['received' => true]);
    }
}
```

**Step 6: Register route**

Add to `routes/api.php`:

```php
use App\Http\Controllers\GeoBase\WebhookController;
use App\Http\Middleware\VerifyGeoBaseWebhook;

Route::post('/webhooks/geobase', [WebhookController::class, 'handle'])
    ->middleware(VerifyGeoBaseWebhook::class)
    ->name('webhooks.geobase');
```

**Step 7: Run tests**

Run: `./vendor/bin/sail artisan test tests/Feature/GeoBase/WebhookControllerTest.php`
Expected: PASS (7 tests)

**Step 8: Run full suite to verify no regressions**

Run: `./vendor/bin/sail artisan test`
Expected: 426+ passed, 7 skipped, 0 failures

**Step 9: Commit**

```bash
git add app/Http/Middleware/VerifyGeoBaseWebhook.php app/Http/Controllers/GeoBase/ app/Events/GeoBase/ routes/api.php tests/Feature/GeoBase/WebhookControllerTest.php
git commit -m "feat(geobase): add webhook handler with HMAC verification

POST /api/webhooks/geobase receives enrollment.status_changed,
snapshot.generated, and sync.processed events. HMAC-SHA256
signature verification via X-GeoBase-Signature header.
Logs all events to activity_log.

Resolves DTE-XXX"
```

---

## Task 4: Listener para actualizar avance MIR desde webhook (Momento 2)

**Files:**
- Create: `app/Listeners/GeoBase/UpdateAvanceFromEnrollment.php`
- Modify: `app/Providers/EventServiceProvider.php` (si existe) o registrar en `AppServiceProvider`
- Test: `tests/Feature/GeoBase/UpdateAvanceFromEnrollmentTest.php`

**Context:** Cuando GeoBase notifica un cambio de estado de inscripción, la MIR debe actualizar el avance del indicador correspondiente si el programa está vinculado.

**Step 1: Write failing test**

```php
// tests/Feature/GeoBase/UpdateAvanceFromEnrollmentTest.php
<?php

namespace Tests\Feature\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateAvanceFromEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_change_triggers_coverage_refresh(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $user->currentTeam->id,
            'geobase_program_id' => 3,
        ]);

        Http::fake([
            '*/programs/3/coverage' => Http::response([
                'data' => [
                    'total_enrollments' => 501,
                    'aprobados' => 451,
                ],
            ], 200),
        ]);

        // Dispatch the event as if GeoBase webhook sent it
        EnrollmentStatusChanged::dispatch(
            enrollmentId: 42,
            oldStatus: 'solicitado',
            newStatus: 'aprobado',
            programId: 3,
            timestamp: now()->toIso8601String(),
        );

        // Verify HTTP call was made to refresh coverage
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/programs/3/coverage');
        });
    }

    public function test_enrollment_change_ignored_for_unlinked_program(): void
    {
        Http::fake();

        // No programa linked to geobase_program_id = 99
        EnrollmentStatusChanged::dispatch(
            enrollmentId: 42,
            oldStatus: 'solicitado',
            newStatus: 'aprobado',
            programId: 99,
            timestamp: now()->toIso8601String(),
        );

        // No HTTP calls should be made
        Http::assertNothingSent();
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test tests/Feature/GeoBase/UpdateAvanceFromEnrollmentTest.php`
Expected: FAIL

**Step 3: Implement listener**

```php
// app/Listeners/GeoBase/UpdateAvanceFromEnrollment.php
<?php

namespace App\Listeners\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Support\Facades\Log;

class UpdateAvanceFromEnrollment
{
    public function __construct(
        private GeoBaseClient $client,
    ) {}

    public function handle(EnrollmentStatusChanged $event): void
    {
        $programa = ProgramaPresupuestario::where('geobase_program_id', $event->programId)->first();

        if (! $programa) {
            return;
        }

        try {
            $coverage = $this->client->getProgramCoverage($event->programId);

            Log::info('GeoBase coverage refreshed', [
                'programa_id' => $programa->id,
                'geobase_program_id' => $event->programId,
                'coverage' => $coverage['data'] ?? [],
            ]);
        } catch (GeoBaseException $e) {
            Log::warning('Failed to refresh GeoBase coverage', [
                'programa_id' => $programa->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Step 4: Register listener**

Check how events are registered in the project (EventServiceProvider or AppServiceProvider), then add:

```php
Event::listen(
    \App\Events\GeoBase\EnrollmentStatusChanged::class,
    \App\Listeners\GeoBase\UpdateAvanceFromEnrollment::class,
);
```

**Step 5: Run tests**

Run: `./vendor/bin/sail artisan test tests/Feature/GeoBase/UpdateAvanceFromEnrollmentTest.php`
Expected: PASS (2 tests)

**Step 6: Commit**

```bash
git add app/Listeners/GeoBase/ app/Providers/ tests/Feature/GeoBase/UpdateAvanceFromEnrollmentTest.php
git commit -m "feat(geobase): add listener to refresh coverage on enrollment changes

When GeoBase notifies enrollment.status_changed, the listener
looks up the linked programa and refreshes coverage data.
Ignores events for unlinked programs.

Resolves DTE-XXX"
```

---

## Task 5: Integration test end-to-end (webhook → listener → coverage)

**Files:**
- Create: `tests/Feature/GeoBase/GeoBaseIntegrationTest.php`

**Step 1: Write integration test**

```php
// tests/Feature/GeoBase/GeoBaseIntegrationTest.php
<?php

namespace Tests\Feature\GeoBase;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoBaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'integration-test-secret-64chars-abcdefghijklmnopqrstuvwxyz123456';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.geobase.webhook_secret' => $this->secret,
            'services.geobase.url' => 'http://geobase-test:8081/api/v1/geobase',
            'services.geobase.token' => 'test-token',
        ]);
    }

    private function signPayload(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), $this->secret);
    }

    public function test_full_flow_webhook_to_coverage_refresh(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        ProgramaPresupuestario::factory()->create([
            'team_id' => $user->currentTeam->id,
            'geobase_program_id' => 3,
        ]);

        Http::fake([
            '*/programs/3/coverage' => Http::response([
                'data' => ['total_enrollments' => 501, 'aprobados' => 451],
            ], 200),
        ]);

        $payload = [
            'event' => 'enrollment.status_changed',
            'data' => [
                'enrollment_id' => 42,
                'old_status' => 'solicitado',
                'new_status' => 'aprobado',
                'program_id' => 3,
                'timestamp' => '2026-03-18T12:00:00-06:00',
            ],
        ];

        // 1. Webhook arrives
        $response = $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => $this->signPayload($payload),
        ]);
        $response->assertStatus(200);

        // 2. Coverage was refreshed via HTTP
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/programs/3/coverage');
        });

        // 3. Activity was logged
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'geobase-webhook',
            'description' => 'enrollment.status_changed',
        ]);
    }

    public function test_snapshot_webhook_is_logged(): void
    {
        $payload = [
            'event' => 'snapshot.generated',
            'data' => [
                'snapshot_id' => 7,
                'period' => '2026-Q1',
                'sha256' => 'abc123def456',
                'component_id' => 2,
                'program_id' => 3,
                'valor_oficial' => 150,
                'timestamp' => '2026-03-18T14:00:00-06:00',
            ],
        ];

        $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => $this->signPayload($payload),
        ])->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'geobase-webhook',
            'description' => 'snapshot.generated',
        ]);
    }

    public function test_client_can_query_all_endpoints(): void
    {
        Http::fake([
            '*/beneficiaries' => Http::response(['beneficiary_id' => 1, 'created' => true], 201),
            '*/beneficiaries/1' => Http::response(['data' => ['id' => 1]], 200),
            '*/enrollments' => Http::response(['data' => ['id' => 1, 'status' => 'aprobado']], 201),
            '*/validation/curp' => Http::response(['exists' => false], 200),
            '*/validation/location' => Http::response(['valid' => true, 'geography_id' => 5], 200),
            '*/programs/3/coverage' => Http::response(['data' => ['total_enrollments' => 100]], 200),
            '*/snapshot' => Http::response(['data' => ['id' => 1, 'snapshot_hash' => 'abc']], 201),
        ]);

        $client = app(\App\Services\GeoBase\GeoBaseClient::class);

        // All methods should work without exceptions
        $client->upsertBeneficiary(['curp_rfc' => 'TEST', 'type' => 'persona_fisica', 'nombre' => 'A', 'apellidos' => 'B', 'address_municipality' => 'X', 'address_state' => 'Y']);
        $client->getBeneficiary(1);
        $client->createEnrollment(['beneficiary_id' => 1, 'program_id' => 3]);
        $client->validateCurp('TEST');
        $client->validateLocation(17.07, -96.72, 3);
        $client->getProgramCoverage(3);
        $client->requestSnapshot(['component_id' => 1, 'period' => '2026-Q1', 'cutoff_date' => '2026-03-31']);

        Http::assertSentCount(7);
    }

    public function test_hmac_verification_rejects_tampered_payload(): void
    {
        $payload = [
            'event' => 'enrollment.status_changed',
            'data' => ['enrollment_id' => 42, 'old_status' => 'a', 'new_status' => 'b', 'program_id' => 1, 'timestamp' => now()->toIso8601String()],
        ];

        // Sign with correct secret, then change payload
        $signature = $this->signPayload($payload);
        $payload['data']['enrollment_id'] = 999; // tampered

        $response = $this->postJson('/api/webhooks/geobase', $payload, [
            'X-GeoBase-Signature' => $signature,
        ]);

        $response->assertStatus(403);
    }
}
```

**Step 2: Run all GeoBase tests**

Run: `./vendor/bin/sail artisan test tests/Unit/Services/GeoBase/ tests/Feature/GeoBase/`
Expected: ALL PASS (~22 tests)

**Step 3: Run full suite**

Run: `./vendor/bin/sail artisan test`
Expected: 426 + ~22 new = ~448 passed, 7 skipped, 0 failures

**Step 4: Commit**

```bash
git add tests/Feature/GeoBase/GeoBaseIntegrationTest.php
git commit -m "test(geobase): add end-to-end integration tests

Full flow: webhook arrival → HMAC verification → event dispatch →
listener → coverage refresh → activity log. Client endpoint
coverage for all 7 API methods. Tamper detection test.

Resolves DTE-XXX"
```

---

## Post-Sprint Checklist

After all tasks complete:

1. **Run full test suite:** `./vendor/bin/sail artisan test` — target: ~448 passed, 7 skipped, 0 failures
2. **Verify config:** Check `.env.example` has all GEOBASE_ vars
3. **Manual verification with GeoBase running:**
   - Start both Sail environments (dte-spp :80, GeoBase :8081)
   - Create Sanctum token in GeoBase for MIR
   - Set `GEOBASE_API_TOKEN` in dte-spp `.env`
   - Test `GeoBaseClient` from tinker: `app(GeoBaseClient::class)->validateCurp('...')`
   - Create webhook subscription in GeoBase pointing to `http://dte-spp:80/api/webhooks/geobase`
   - Trigger an enrollment status change in GeoBase, verify webhook arrives
4. **Merge to `desarrollo`**
