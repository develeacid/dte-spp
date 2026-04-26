<?php

namespace Tests\Feature\Padron;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Documenta el contrato esperado del receptor M5 (webhooks de GeoBase).
 *
 * El receptor (App\Http\Controllers\GeoBase\WebhookController) y el
 * middleware HMAC ya existen, pero GeoBase aún no implementa el
 * Observer + Job que los emite. Estos tests están skipped hasta que
 * GeoBase cierre M5 (sprint posterior, ver design doc 2026-04-26 §11).
 */
class M5WebhookContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_status_changed_dispara_evento_interno(): void
    {
        $this->markTestSkipped(
            'Pendiente: GeoBase debe implementar EnrollmentObserver + SendWebhookToSpp job. '
            . 'Cuando emita, este test debe verificar HMAC válido y disparo de UpdateAvanceFromEnrollment.'
        );
    }

    public function test_snapshot_generated_dispara_listener_storesnapshothash(): void
    {
        $this->markTestSkipped(
            'Pendiente: GeoBase observer post-snapshot. Verifica que StoreSnapshotHash '
            . 'se ejecute con el payload {snapshot_id, hash, program_id, component_id, period}.'
        );
    }

    public function test_sync_processed_se_loguea_en_activity_log(): void
    {
        $this->markTestSkipped(
            'Pendiente: GeoBase observer post-sync. Verifica activity_log con log_name=geobase-sync.'
        );
    }
}
