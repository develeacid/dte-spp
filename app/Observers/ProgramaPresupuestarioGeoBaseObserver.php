<?php

namespace App\Observers;

use App\Enums\TipoNivelMir;
use App\Jobs\GeoBase\SyncMirNivelToGeoBase;
use App\Jobs\GeoBase\SyncProgramaToGeoBase;
use App\Models\ProgramaPresupuestario;

/**
 * When a programa with an active GeoBase padron has its identifying fields
 * edited, mirror them upstream. The observer is silent for programas that
 * never had padron activated and for changes outside the replicated set
 * (estado, planeacion_completada_at, the activo flag itself, etc.).
 */
class ProgramaPresupuestarioGeoBaseObserver
{
    /**
     * Fields whose value is published to GeoBase via POST /programs.
     */
    private const REPLICATED_FIELDS = ['nombre', 'clave', 'ejercicio_fiscal'];

    public function updated(ProgramaPresupuestario $programa): void
    {
        // Only programas already linked to GeoBase need to be kept in sync.
        // Activation/deactivation themselves are handled by the
        // PadronProvisioningService and must not retrigger here.
        if (! $programa->padron_geobase_activo) {
            return;
        }

        if (! $programa->wasChanged(self::REPLICATED_FIELDS)) {
            return;
        }

        SyncProgramaToGeoBase::dispatch(
            sppProgramId: $programa->id,
            clave: $programa->clave,
            name: $programa->nombre,
            ejercicioFiscal: (int) $programa->ejercicio_fiscal,
        );

        // Component claves are derived from the parent programa's clave
        // ({programa.clave}-MN{spp_mir_nivel_id}). When the programa clave
        // changes, every Componente needs to be re-synced so the upstream
        // clave catches up.
        if ($programa->wasChanged('clave')) {
            $componentes = $programa->mirNiveles()
                ->where('tipo_nivel', TipoNivelMir::COMPONENTE)
                ->get(['id', 'resumen_narrativo']);

            foreach ($componentes as $componente) {
                SyncMirNivelToGeoBase::dispatch(
                    mirNivelId: $componente->id,
                    sppProgramId: $programa->id,
                    programaClave: $programa->clave,
                    resumenNarrativo: $componente->resumen_narrativo ?? '',
                    activo: true,
                );
            }
        }
    }
}
