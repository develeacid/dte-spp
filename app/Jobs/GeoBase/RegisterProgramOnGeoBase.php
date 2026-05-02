<?php

namespace App\Jobs\GeoBase;

use App\Models\ProgramaPresupuestario;
use App\Services\Padron\PadronProvisioningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Registers a programa upstream in GeoBase, dispatched by the
 * ProgramaPresupuestarioGeoBaseObserver when padron_geobase_activo
 * transitions false → true. Idempotent: GeoBase upserts by spp_program_id,
 * so the UI/CLI paths that already call PadronProvisioningService::register()
 * synchronously remain correct — this job re-runs the same upstream calls
 * with no observable side effect.
 */
class RegisterProgramOnGeoBase implements ShouldQueue
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
            Log::info('RegisterProgramOnGeoBase: programa eliminado antes de ejecutar', [
                'programa_id' => $this->programaId,
            ]);

            return;
        }

        $service->register($programa);
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('RegisterProgramOnGeoBase: gave up after retries', [
            'programa_id' => $this->programaId,
            'error' => $e->getMessage(),
        ]);
    }
}
