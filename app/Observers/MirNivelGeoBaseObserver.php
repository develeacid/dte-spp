<?php

namespace App\Observers;

use App\Enums\TipoNivelMir;
use App\Jobs\GeoBase\SyncMirNivelToGeoBase;
use App\Models\Mml\MirNivel;

/**
 * When a MIR Componente changes inside a programa whose padron is active in
 * GeoBase, mirror the change upstream. Only Componente rows participate;
 * Fin/Propósito/Actividad have no equivalent in GeoBase. Failures are
 * non-blocking (the job retries with backoff and logs on final failure).
 */
class MirNivelGeoBaseObserver
{
    public function saved(MirNivel $mirNivel): void
    {
        if (! $this->shouldSync($mirNivel)) {
            return;
        }

        SyncMirNivelToGeoBase::dispatch(
            mirNivelId: $mirNivel->id,
            sppProgramId: $mirNivel->programa_presupuestario_id,
            programaClave: $mirNivel->programa->clave,
            resumenNarrativo: $mirNivel->resumen_narrativo ?? '',
            activo: true,
        );
    }

    public function deleting(MirNivel $mirNivel): void
    {
        if (! $this->shouldSync($mirNivel)) {
            return;
        }

        SyncMirNivelToGeoBase::dispatch(
            mirNivelId: $mirNivel->id,
            sppProgramId: $mirNivel->programa_presupuestario_id,
            programaClave: $mirNivel->programa->clave,
            resumenNarrativo: $mirNivel->resumen_narrativo ?? '',
            activo: false,
        );
    }

    private function shouldSync(MirNivel $mirNivel): bool
    {
        if ($mirNivel->tipo_nivel !== TipoNivelMir::COMPONENTE) {
            return false;
        }

        return (bool) $mirNivel->programa?->padron_geobase_activo;
    }
}
