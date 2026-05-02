<?php

namespace Tests\Feature\Padron;

use App\Models\GeoBase\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class M5WebhookDeliveryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_payload_se_castea_a_array(): void
    {
        $delivery = WebhookDelivery::create([
            'delivery_id' => 'd-1',
            'event_type' => 'enrollment.status_changed',
            'signature_valid' => true,
            'payload' => ['enrollment_id' => 1],
        ]);

        $this->assertIsArray($delivery->fresh()->payload);
        $this->assertSame(1, $delivery->fresh()->payload['enrollment_id']);
    }

    public function test_prunable_borra_deliveries_mas_viejos_que_retencion(): void
    {
        config(['services.geobase.delivery_retention_days' => 90]);

        WebhookDelivery::create([
            'delivery_id' => 'd-old',
            'event_type' => 'sync.processed',
            'signature_valid' => true,
            'payload' => [],
            'created_at' => now()->subDays(91),
        ]);

        WebhookDelivery::create([
            'delivery_id' => 'd-new',
            'event_type' => 'sync.processed',
            'signature_valid' => true,
            'payload' => [],
        ]);

        $this->artisan('model:prune', ['--model' => [WebhookDelivery::class]])->assertExitCode(0);

        $this->assertDatabaseMissing('geobase_webhook_deliveries', ['delivery_id' => 'd-old']);
        $this->assertDatabaseHas('geobase_webhook_deliveries', ['delivery_id' => 'd-new']);
    }

    public function test_unique_constraint_en_delivery_id(): void
    {
        WebhookDelivery::create([
            'delivery_id' => 'd-dup',
            'event_type' => 'sync.processed',
            'signature_valid' => true,
            'payload' => [],
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        WebhookDelivery::create([
            'delivery_id' => 'd-dup',
            'event_type' => 'sync.processed',
            'signature_valid' => true,
            'payload' => [],
        ]);
    }
}
