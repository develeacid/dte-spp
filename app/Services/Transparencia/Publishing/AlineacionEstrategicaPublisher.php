<?php

namespace App\Services\Transparencia\Publishing;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use App\Models\Reportes\VwAlineacionCompleta;
use App\Models\Transparencia\DatasetAbierto;

class AlineacionEstrategicaPublisher extends BasePublisher
{
    public function code(): string
    {
        return 'DS-05';
    }

    protected function tabla(): string
    {
        return 'pub_alineacion_estrategica';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $now = now();

        // Cadena de alineación PED→PND→ODS resuelta por la vista canónica (V2-E9),
        // indexada por programa para llenar ods_metas/pnd_objetivo que antes quedaban NULL.
        $alineacion = VwAlineacionCompleta::get()->keyBy('programa_id');

        return ProgramaPresupuestario::query()
            ->with([
                'mirNiveles' => fn ($q) => $q->whereIn('tipo_nivel', [
                    TipoNivelMir::FIN->value,
                    TipoNivelMir::PROPOSITO->value,
                ]),
            ])
            ->orderBy('id')
            ->get()
            ->map(function ($p) use ($now, $alineacion) {
                $fin = $p->mirNiveles->firstWhere('tipo_nivel', TipoNivelMir::FIN);
                $proposito = $p->mirNiveles->firstWhere('tipo_nivel', TipoNivelMir::PROPOSITO);

                $vw = $alineacion->get($p->id);
                $odsClaves = $vw?->ods_claves ?? [];

                return [
                    'programa_clave' => $p->clave,
                    'ods_metas' => $odsClaves !== [] ? json_encode($odsClaves) : null,
                    'pnd_objetivo' => $vw?->pnd_objetivos,
                    'ped_eje' => $vw?->ped_eje,
                    'ped_objetivo_estrategico' => $vw?->ped_objetivo_estrategico,
                    'ped_linea_accion' => $vw?->ped_linea_accion,
                    'mir_fin_resumen' => $fin?->resumen_narrativo,
                    'mir_proposito_resumen' => $proposito?->resumen_narrativo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();
    }
}
