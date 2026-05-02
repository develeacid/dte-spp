<?php

namespace Tests\Feature\Padron;

use App\Enums\TipoNivelMir;
use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Events\GeoBase\SnapshotGenerated;
use App\Events\GeoBase\SyncProcessed;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class M5WebhookContractTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret-please-rotate';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.geobase.webhook_secret' => self::SECRET]);

        // Capture sync jobs dispatched by the MirNivelGeoBaseObserver.
        Queue::fake();
    }

    private function postWebhook(string $event, array $payload, ?string $signatureOverride = null): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = $signatureOverride ?? 'sha256=' . hash_hmac('sha256', $body, self::SECRET);

        return $this->call(
            method: 'POST',
            uri: '/api/webhooks/geobase',
            server: [
                'HTTP_X-GeoBase-Event' => $event,
                'HTTP_X-GeoBase-Signature' => $signature,
                'HTTP_X-GeoBase-Delivery' => '42',
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $body,
        );
    }

    public function test_enrollment_status_changed_dispara_evento_interno(): void
    {
        Event::fake([EnrollmentStatusChanged::class]);

        $payload = [
            'enrollment_id' => 123,
            'old_status' => 'solicitado',
            'new_status' => 'aprobado',
            'spp_program_id' => 7,
            'timestamp' => '2026-04-26T12:00:00+00:00',
        ];

        $this->postWebhook('enrollment.status_changed', $payload)
            ->assertOk()
            ->assertJson(['received' => true]);

        Event::assertDispatched(EnrollmentStatusChanged::class, function ($e) use ($payload) {
            return $e->enrollmentId === $payload['enrollment_id']
                && $e->oldStatus === $payload['old_status']
                && $e->newStatus === $payload['new_status']
                && $e->sppProgramId === $payload['spp_program_id'];
        });
    }

    public function test_snapshot_generated_dispara_listener_storesnapshothash(): void
    {
        // Set up programa + indicador + avance + meta_periodo so that
        // StoreSnapshotHash finds an Avance to attach the evidence to.
        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'C1',
            'orden' => 1,
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $componente->id,
            'nombre' => 'Indicador C1',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'orden' => 1,
        ]);
        $meta = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 2,
            'ejercicio_fiscal' => 2026,
            'meta_periodo' => 100,
        ]);
        $user = User::factory()->withPersonalTeam()->create();
        $avance = Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'estado' => 'en_captura',
            'capturado_por' => $user->id,
        ]);

        $payload = [
            'snapshot_id' => 4421,
            'period' => '2026-Q2',
            'sha256' => 'a3f7c9e2deadbeef',
            'spp_mir_nivel_id' => 99,
            'spp_program_id' => $programa->id,
            'valor_oficial' => 1820,
            'timestamp' => '2026-04-26T12:00:00+00:00',
        ];

        $this->postWebhook('snapshot.generated', $payload)
            ->assertOk();

        $this->assertDatabaseHas('avance_evidencias', [
            'avance_id' => $avance->id,
            'hash_archivo' => 'a3f7c9e2deadbeef',
            'geobase_snapshot_id' => 4421,
            'area_generadora' => 'GeoBase (automatico)',
        ]);
    }

    public function test_sync_processed_se_loguea_en_activity_log(): void
    {
        Event::fake([SyncProcessed::class]);

        $payload = [
            'entry_id' => 99,
            'operation' => 'create',
            'result_type' => 'enrollment',
            'result_id' => 123,
            'timestamp' => '2026-04-26T12:00:00+00:00',
        ];

        $this->postWebhook('sync.processed', $payload)->assertOk();

        Event::assertDispatched(SyncProcessed::class);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'geobase-webhook',
            'description' => 'sync.processed',
        ]);
    }

    public function test_signature_invalida_retorna_403(): void
    {
        $payload = ['enrollment_id' => 1, 'old_status' => 'a', 'new_status' => 'b', 'spp_program_id' => 1, 'timestamp' => 't'];

        $this->postWebhook('enrollment.status_changed', $payload, signatureOverride: 'sha256=feedface')
            ->assertForbidden();

        $this->assertDatabaseMissing('activity_log', ['log_name' => 'geobase-webhook']);
    }

    public function test_acepta_signature_sin_prefijo_sha256(): void
    {
        $payload = ['enrollment_id' => 1, 'old_status' => 'a', 'new_status' => 'b', 'spp_program_id' => 1, 'timestamp' => 't'];
        $body = json_encode($payload);
        $bareHex = hash_hmac('sha256', $body, self::SECRET);

        $this->postWebhook('enrollment.status_changed', $payload, signatureOverride: $bareHex)
            ->assertOk();
    }

    public function test_persiste_delivery_row_al_recibir_evento_valido(): void
    {
        Event::fake([SyncProcessed::class]);

        $payload = [
            'entry_id' => 99,
            'operation' => 'create',
            'result_type' => 'enrollment',
            'result_id' => 123,
            'timestamp' => '2026-04-26T12:00:00+00:00',
        ];

        $this->postWebhook('sync.processed', $payload)->assertOk();

        $this->assertDatabaseHas('geobase_webhook_deliveries', [
            'delivery_id' => '42',
            'event_type' => 'sync.processed',
            'signature_valid' => true,
            'status_code' => 200,
        ]);

        $delivery = \App\Models\GeoBase\WebhookDelivery::where('delivery_id', '42')->first();
        $this->assertNotNull($delivery->processed_at);
        $this->assertSame(99, $delivery->payload['entry_id']);
    }

    public function test_firma_invalida_no_persiste_delivery_row(): void
    {
        $payload = ['enrollment_id' => 1, 'old_status' => 'a', 'new_status' => 'b', 'spp_program_id' => 1, 'timestamp' => 't'];

        $this->postWebhook('enrollment.status_changed', $payload, signatureOverride: 'sha256=feedface')
            ->assertForbidden();

        $this->assertDatabaseMissing('geobase_webhook_deliveries', ['delivery_id' => '42']);
    }

    public function test_falta_header_delivery_retorna_400_sin_row(): void
    {
        $payload = [
            'enrollment_id' => 1,
            'old_status' => 'a',
            'new_status' => 'b',
            'spp_program_id' => 1,
            'timestamp' => 't',
        ];

        $body = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $body, self::SECRET);

        $this->call(
            method: 'POST',
            uri: '/api/webhooks/geobase',
            server: [
                'HTTP_X-GeoBase-Event' => 'enrollment.status_changed',
                'HTTP_X-GeoBase-Signature' => $signature,
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: $body,
        )->assertStatus(400);

        $this->assertSame(0, \App\Models\GeoBase\WebhookDelivery::count());
    }

    public function test_delivery_id_repetido_responde_idempotente_sin_redispatch(): void
    {
        Event::fake([SyncProcessed::class]);

        $payload = [
            'entry_id' => 99,
            'operation' => 'create',
            'result_type' => 'enrollment',
            'result_id' => 123,
            'timestamp' => '2026-04-26T12:00:00+00:00',
        ];

        $this->postWebhook('sync.processed', $payload)
            ->assertOk()
            ->assertJson(['received' => true]);

        Event::assertDispatchedTimes(SyncProcessed::class, 1);

        $this->postWebhook('sync.processed', $payload)
            ->assertOk()
            ->assertJson([
                'received' => true,
                'idempotent' => true,
            ]);

        Event::assertDispatchedTimes(SyncProcessed::class, 1);

        $this->assertSame(
            1,
            \App\Models\GeoBase\WebhookDelivery::where('delivery_id', '42')->count()
        );
    }
}
