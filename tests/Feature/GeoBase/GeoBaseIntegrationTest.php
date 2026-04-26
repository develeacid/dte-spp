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
            '*/snapshots/generate' => Http::response(['data' => ['id' => 1, 'snapshot_hash' => 'abc']], 201),
        ]);

        $client = app(\App\Services\GeoBase\GeoBaseClient::class);

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
