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
     * @throws GeoBaseException  bubbled up if geobase fails — caller decides
     *                           whether to surface a flash or rethrow.
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
                    'clave' => sprintf('%s-C%d', $programa->clave, $componente->orden),
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
}
