<?php

namespace App\Jobs\GeoBase;

use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Replicates a programa's identifying fields (clave, name, ejercicio_fiscal)
 * to GeoBase. Idempotent on the server side (POST /programs is
 * updateOrCreate by spp_program_id), so retries are safe.
 *
 * Dispatched from ProgramaPresupuestarioGeoBaseObserver only when the
 * programa has padron_geobase_activo=true and one of the replicated fields
 * has actually changed.
 */
class SyncProgramaToGeoBase implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int> */
    public array $backoff = [10, 30, 120, 600, 1800];

    public function __construct(
        public readonly int $sppProgramId,
        public readonly string $clave,
        public readonly string $name,
        public readonly int $ejercicioFiscal,
    ) {
        $this->onQueue('geobase-sync');
    }

    public function handle(GeoBaseClient $client): void
    {
        try {
            $client->registerProgram([
                'spp_program_id' => $this->sppProgramId,
                'clave' => $this->clave,
                'name' => $this->name,
                'ejercicio_fiscal' => $this->ejercicioFiscal,
                'activo' => true,
            ]);
        } catch (GeoBaseException $e) {
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('SyncProgramaToGeoBase: gave up after retries', [
            'spp_program_id' => $this->sppProgramId,
            'error' => $e->getMessage(),
        ]);
    }

    public static function fromModel(ProgramaPresupuestario $programa): self
    {
        return new self(
            sppProgramId: $programa->id,
            clave: $programa->clave,
            name: $programa->nombre,
            ejercicioFiscal: (int) $programa->ejercicio_fiscal,
        );
    }
}
