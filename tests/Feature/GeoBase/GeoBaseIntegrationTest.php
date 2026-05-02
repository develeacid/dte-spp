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

    private function postWebhook(string $event, array $payload, string $deliveryId): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $this->secret);

        return $this->call(
            method: 'POST',
            uri: '/api/webhooks/geobase',
            server: [
                'HTTP_X-GeoBase-Event' => $event,
                'HTTP_X-GeoBase-Signature' => $signature,
                'HTTP_X-GeoBase-Delivery' => $deliveryId,
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $body,
        );
    }

    public function test_full_flow_webhook_to_coverage_refresh(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $user->currentTeam->id,
            'padron_geobase_activo' => true,
        ]);

        Http::fake([
            "*/programs/{$programa->id}/coverage" => Http::response([
                'data' => ['total_enrollments' => 501, 'aprobados' => 451],
            ], 200),
        ]);

        $payload = [
            'enrollment_id' => 42,
            'old_status' => 'solicitado',
            'new_status' => 'aprobado',
            'spp_program_id' => $programa->id,
            'timestamp' => '2026-03-18T12:00:00-06:00',
        ];

        // 1. Webhook arrives
        $this->postWebhook('enrollment.status_changed', $payload, 'integ-1')
            ->assertStatus(200);

        // 2. Coverage was refreshed via HTTP
        Http::assertSent(function ($request) use ($programa) {
            return str_contains($request->url(), "/programs/{$programa->id}/coverage");
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
            'snapshot_id' => 7,
            'period' => '2026-Q1',
            'sha256' => 'abc123def456',
            'spp_mir_nivel_id' => 2,
            'spp_program_id' => 3,
            'valor_oficial' => 150,
            'timestamp' => '2026-03-18T14:00:00-06:00',
        ];

        $this->postWebhook('snapshot.generated', $payload, 'integ-2')->assertStatus(200);

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
        $client->requestSnapshot(['spp_mir_nivel_id' => 1, 'period' => '2026-Q1', 'cutoff_date' => '2026-03-31']);

        Http::assertSentCount(7);
    }

    public function test_hmac_verification_rejects_tampered_payload(): void
    {
        $payload = [
            'enrollment_id' => 42,
            'old_status' => 'a',
            'new_status' => 'b',
            'spp_program_id' => 1,
            'timestamp' => now()->toIso8601String(),
        ];

        // Sign one body, then send a tampered one
        $originalBody = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $originalBody, $this->secret);

        $tampered = $payload;
        $tampered['enrollment_id'] = 999;
        $tamperedBody = json_encode($tampered);

        $response = $this->call(
            method: 'POST',
            uri: '/api/webhooks/geobase',
            server: [
                'HTTP_X-GeoBase-Event' => 'enrollment.status_changed',
                'HTTP_X-GeoBase-Signature' => $signature,
                'HTTP_X-GeoBase-Delivery' => 'integ-3',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $tamperedBody,
        );

        $response->assertStatus(403);
    }
}
