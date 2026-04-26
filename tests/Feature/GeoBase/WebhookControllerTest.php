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
                'spp_program_id' => 3,
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
                'spp_mir_nivel_id' => 2,
                'spp_program_id' => 3,
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
            'data' => ['enrollment_id' => 42, 'old_status' => 'solicitado', 'new_status' => 'aprobado', 'spp_program_id' => 3, 'timestamp' => now()->toIso8601String()],
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
