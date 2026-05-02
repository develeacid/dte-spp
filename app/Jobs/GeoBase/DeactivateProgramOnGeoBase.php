<?php

namespace App\Jobs\GeoBase;

use App\Models\ProgramaPresupuestario;
use App\Services\Padron\PadronProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Deactivates a programa upstream in GeoBase (sets activo=false on the
 * programa and its componentes), dispatched by the observer when
 * padron_geobase_activo transitions true → false. Idempotent.
 */
class DeactivateProgramOnGeoBase implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $programaId)
    {
        $this->onQueue('geobase-sync');
    }

    public function handle(PadronProvisioningService $service): void
    {
        $programa = ProgramaPresupuestario::find($this->programaId);
        if ($programa === null) {
            Log::info('DeactivateProgramOnGeoBase: programa eliminado antes de ejecutar', [
                'programa_id' => $this->programaId,
            ]);

            return;
        }

        $service->deactivate($programa);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('DeactivateProgramOnGeoBase: gave up after retries', [
            'programa_id' => $this->programaId,
            'error' => $e->getMessage(),
        ]);
    }
}
