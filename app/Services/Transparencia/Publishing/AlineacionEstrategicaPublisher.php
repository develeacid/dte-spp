<?php

namespace App\Services\Transparencia\Publishing;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
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

        return ProgramaPresupuestario::query()
            ->with([
                'mirNiveles' => fn ($q) => $q->whereIn('tipo_nivel', [
                    TipoNivelMir::FIN->value,
                    TipoNivelMir::PROPOSITO->value,
                ]),
                'mirNiveles.pedObjetivoEstrategico.tema.eje',
                'mirNiveles.pedLineaAccion',
            ])
            ->orderBy('id')
            ->get()
            ->map(function ($p) use ($now) {
                $fin = $p->mirNiveles->firstWhere('tipo_nivel', TipoNivelMir::FIN);
                $proposito = $p->mirNiveles->firstWhere('tipo_nivel', TipoNivelMir::PROPOSITO);

                $nivelConPed = $p->mirNiveles->first(fn ($n) => $n->pedObjetivoEstrategico !== null);
                $pedObjetivo = $nivelConPed?->pedObjetivoEstrategico;
                $pedEje = $pedObjetivo?->tema?->eje;
                $pedLineaAccion = $nivelConPed?->pedLineaAccion;

                return [
                    'programa_clave' => $p->clave,
                    'ods_metas' => null,
                    'pnd_objetivo' => null,
                    'ped_eje' => $pedEje?->descripcion ?? $pedEje?->nombre ?? null,
                    'ped_objetivo_estrategico' => $pedObjetivo?->descripcion,
                    'ped_linea_accion' => $pedLineaAccion?->descripcion ?? $pedLineaAccion?->nombre ?? null,
                    'mir_fin_resumen' => $fin?->resumen_narrativo,
                    'mir_proposito_resumen' => $proposito?->resumen_narrativo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();
    }
}
