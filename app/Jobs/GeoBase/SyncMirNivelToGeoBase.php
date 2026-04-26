<?php

namespace App\Jobs\GeoBase;

use App\Models\Mml\MirNivel;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Replicates a MIR Componente row to GeoBase. Idempotent on the server side
 * (POST /components is updateOrCreate by spp_mir_nivel_id), so retries are
 * safe.
 *
 * The job is dispatched from MirNivelGeoBaseObserver only when the parent
 * programa has padron_geobase_activo=true.
 */
class SyncMirNivelToGeoBase implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int> */
    public array $backoff = [10, 30, 120, 600, 1800];

    public function __construct(
        public readonly int $mirNivelId,
        public readonly int $sppProgramId,
        public readonly string $programaClave,
        public readonly string $resumenNarrativo,
        public readonly bool $activo = true,
    ) {
        $this->onQueue('geobase-sync');
    }

    public function handle(GeoBaseClient $client): void
    {
        try {
            $client->registerComponent([
                'spp_mir_nivel_id' => $this->mirNivelId,
                'spp_program_id' => $this->sppProgramId,
                'clave' => sprintf('%s-MN%d', $this->programaClave, $this->mirNivelId),
                'name' => (string) str($this->resumenNarrativo)->limit(255),
                'description' => $this->resumenNarrativo,
                'activo' => $this->activo,
            ]);
        } catch (GeoBaseException $e) {
            // Bubble for queue retry; final failure logged by failed().
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('SyncMirNivelToGeoBase: gave up after retries', [
            'mir_nivel_id' => $this->mirNivelId,
            'spp_program_id' => $this->sppProgramId,
            'error' => $e->getMessage(),
        ]);
    }

    public static function fromModel(MirNivel $mirNivel, bool $activo = true): self
    {
        $programa = $mirNivel->programa;

        return new self(
            mirNivelId: $mirNivel->id,
            sppProgramId: $programa->id,
            programaClave: $programa->clave,
            resumenNarrativo: $mirNivel->resumen_narrativo ?? '',
            activo: $activo,
        );
    }
}
