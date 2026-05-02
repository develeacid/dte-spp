<?php

namespace App\Observers;

use App\Enums\TipoNivelMir;
use App\Jobs\GeoBase\DeactivateProgramOnGeoBase;
use App\Jobs\GeoBase\RegisterProgramOnGeoBase;
use App\Jobs\GeoBase\SyncMirNivelToGeoBase;
use App\Jobs\GeoBase\SyncProgramaToGeoBase;
use App\Models\ProgramaPresupuestario;

/**
 * Mantiene en sincronía el estado del padrón en GeoBase respecto a dte-spp.
 *
 * Cubre dos escenarios:
 * 1. Cambio del flag padron_geobase_activo (false↔true): dispatcha el job
 *    correspondiente que delega al PadronProvisioningService. Cubre paths
 *    que evitan el service (seeders, factories, tinker).
 * 2. Cambio de fields identificadores con padrón ya activo: replica la
 *    nueva data upstream (lógica preexistente).
 */
class ProgramaPresupuestarioGeoBaseObserver
{
    /**
     * Fields whose value is published to GeoBase via POST /programs.
     */
    private const REPLICATED_FIELDS = ['nombre', 'clave', 'ejercicio_fiscal'];

    public function updated(ProgramaPresupuestario $programa): void
    {
        // Cambio del flag: dispatcha activación o desactivación upstream.
        // Idempotente — geobase upserts por spp_program_id, así que el path UI/CLI
        // que ya invoca el service síncronamente no produce side effects al
        // re-ejecutarse vía este job.
        if ($programa->wasChanged('padron_geobase_activo')) {
            $programa->padron_geobase_activo
                ? RegisterProgramOnGeoBase::dispatch($programa->id)
                : DeactivateProgramOnGeoBase::dispatch($programa->id);

            // El job re-ejecuta el service que también sincroniza fields, así
            // que SyncProgramaToGeoBase queda silenciado para evitar doble work
            // cuando flag y fields cambian en el mismo update.
            return;
        }

        // Path existente: cambios en fields identificadores cuando el flag ya es true.
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
