<?php

namespace App\Services\Padron;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Idempotently provisions a programa and its MIR componentes into GeoBase
 * using dte-spp ids as the source of truth, then flips
 * padron_geobase_activo to true.
 */
class PadronProvisioningService
{
    public function __construct(
        private GeoBaseClient $client,
    ) {}

    /**
     * @return array{programa: int, componentes_registrados: int}
     *
     * @throws GeoBaseException bubbled up if geobase fails — caller decides
     *                          whether to surface a flash or rethrow.
     */
    public function register(ProgramaPresupuestario $programa): array
    {
        $this->client->registerProgram([
            'spp_program_id' => $programa->id,
            'clave' => $programa->clave,
            'name' => $programa->nombre,
            'ejercicio_fiscal' => $programa->ejercicio_fiscal,
            'activo' => true,
        ]);

        $componentes = $programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE)
            ->orderBy('orden')
            ->get();

        $registered = 0;
        foreach ($componentes as $componente) {
            try {
                $this->client->registerComponent([
                    'spp_mir_nivel_id' => $componente->id,
                    'spp_program_id' => $programa->id,
                    // Use the immutable spp_mir_nivel_id in the clave so that
                    // reordering components in dte-spp does not produce a new
                    // clave on each sync (orden is mutable, mir_nivel id is not).
                    'clave' => sprintf('%s-MN%d', $programa->clave, $componente->id),
                    'name' => (string) str($componente->resumen_narrativo)->limit(255),
                    'description' => $componente->resumen_narrativo,
                    'activo' => true,
                ]);
                $registered++;
            } catch (GeoBaseException $e) {
                // One component failing should not abort the whole batch:
                // the program is already registered upstream and other
                // components might still succeed. Surface in logs.
                Log::warning('PadronProvisioningService: component register failed', [
                    'programa_id' => $programa->id,
                    'componente_id' => $componente->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        DB::transaction(function () use ($programa, $registered) {
            $programa->update(['padron_geobase_activo' => true]);

            activity('padron-provisioning')
                ->performedOn($programa)
                ->withProperties([
                    'componentes_registrados' => $registered,
                ])
                ->log('Programa registrado en GeoBase');
        });

        return [
            'programa' => $programa->id,
            'componentes_registrados' => $registered,
        ];
    }

    /**
     * Soft-deactivates the padron in GeoBase: the program and its MIR
     * componentes are marked activo=false upstream. Snapshots already
     * generated stay archived and verifiable. Use this when a programa
     * is winding down or being moved out of GeoBase, not for hard
     * deletes (FKs in geobase point to components).
     *
     * @return array{programa: int, componentes_desactivados: int}
     */
    public function deactivate(ProgramaPresupuestario $programa): array
    {
        $componentes = $programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE)
            ->orderBy('orden')
            ->get();

        $deactivated = 0;
        foreach ($componentes as $componente) {
            try {
                $this->client->registerComponent([
                    'spp_mir_nivel_id' => $componente->id,
                    'spp_program_id' => $programa->id,
                    'clave' => sprintf('%s-MN%d', $programa->clave, $componente->id),
                    'name' => (string) str($componente->resumen_narrativo)->limit(255),
                    'description' => $componente->resumen_narrativo,
                    'activo' => false,
                ]);
                $deactivated++;
            } catch (GeoBaseException $e) {
                Log::warning('PadronProvisioningService: component deactivate failed', [
                    'programa_id' => $programa->id,
                    'componente_id' => $componente->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Programa is registered last so that if geobase rejects it we don't
        // leave components in an inconsistent state with the parent program
        // still flagged activo=true upstream.
        $this->client->registerProgram([
            'spp_program_id' => $programa->id,
            'clave' => $programa->clave,
            'name' => $programa->nombre,
            'ejercicio_fiscal' => $programa->ejercicio_fiscal,
            'activo' => false,
        ]);

        DB::transaction(function () use ($programa, $deactivated) {
            $programa->update(['padron_geobase_activo' => false]);

            activity('padron-deactivation')
                ->performedOn($programa)
                ->withProperties([
                    'componentes_desactivados' => $deactivated,
                ])
                ->log('Padrón desactivado en GeoBase');
        });

        return [
            'programa' => $programa->id,
            'componentes_desactivados' => $deactivated,
        ];
    }
}
