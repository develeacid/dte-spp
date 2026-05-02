<?php

namespace Tests\Feature\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Events\GeoBase\SnapshotGenerated;
use App\Events\GeoBase\SyncProcessed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
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

    private function postWebhook(string $event, array $payload, string $deliveryId = 'wct-1'): TestResponse
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

    public function test_accepts_valid_webhook_with_correct_signature(): void
    {
        Event::fake([EnrollmentStatusChanged::class]);

        $payload = [
            'enrollment_id' => 42,
            'old_status' => 'solicitado',
            'new_status' => 'aprobado',
            'spp_program_id' => 3,
            'timestamp' => '2026-03-18T12:00:00-06:00',
        ];

        $this->postWebhook('enrollment.status_changed', $payload)->assertStatus(200);
        Event::assertDispatched(EnrollmentStatusChanged::class);
    }

    public function test_rejects_webhook_with_invalid_signature(): void
    {
        $body = json_encode(['enrollment_id' => 42]);

        $response = $this->call(
            method: 'POST',
            uri: '/api/webhooks/geobase',
            server: [
                'HTTP_X-GeoBase-Event' => 'enrollment.status_changed',
                'HTTP_X-GeoBase-Signature' => 'sha256=invalid-signature',
                'HTTP_X-GeoBase-Delivery' => 'wct-2',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $body,
        );

        $response->assertStatus(403);
    }

    public function test_rejects_webhook_without_signature(): void
    {
        $body = json_encode(['enrollment_id' => 42]);

        $response = $this->call(
            method: 'POST',
            uri: '/api/webhooks/geobase',
            server: [
                'HTTP_X-GeoBase-Event' => 'enrollment.status_changed',
                'HTTP_X-GeoBase-Delivery' => 'wct-3',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $body,
        );

        $response->assertStatus(403);
    }

    public function test_dispatches_snapshot_generated_event(): void
    {
        Event::fake([SnapshotGenerated::class]);

        $payload = [
            'snapshot_id' => 7,
            'period' => '2026-Q1',
            'sha256' => 'a1b2c3d4e5f6',
            'spp_mir_nivel_id' => 2,
            'spp_program_id' => 3,
            'valor_oficial' => 150,
            'timestamp' => '2026-03-18T12:00:00-06:00',
        ];

        $this->postWebhook('snapshot.generated', $payload, 'wct-4')->assertStatus(200);

        Event::assertDispatched(SnapshotGenerated::class, function ($event) {
            return $event->snapshotId === 7
                && $event->snapshotHash === 'a1b2c3d4e5f6';
        });
    }

    public function test_dispatches_sync_processed_event(): void
    {
        Event::fake([SyncProcessed::class]);

        $payload = [
            'entry_id' => 145,
            'operation' => 'create_beneficiary',
            'result_type' => 'App\\Models\\Beneficiary',
            'result_id' => 8492,
            'timestamp' => '2026-03-18T14:30:00-06:00',
        ];

        $this->postWebhook('sync.processed', $payload, 'wct-5')->assertStatus(200);

        Event::assertDispatched(SyncProcessed::class);
    }

    public function test_returns_200_for_unknown_event_type(): void
    {
        $payload = ['foo' => 'bar'];

        $response = $this->postWebhook('unknown.event', $payload, 'wct-6');

        // Acknowledge receipt, don't error — GeoBase may add new events
        $response->assertStatus(200);
    }

    public function test_logs_webhook_receipt(): void
    {
        $payload = [
            'enrollment_id' => 42,
            'old_status' => 'solicitado',
            'new_status' => 'aprobado',
            'spp_program_id' => 3,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->postWebhook('enrollment.status_changed', $payload, 'wct-7')->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'geobase-webhook',
            'description' => 'enrollment.status_changed',
        ]);
    }
}
