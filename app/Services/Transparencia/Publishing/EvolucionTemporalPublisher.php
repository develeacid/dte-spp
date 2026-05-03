<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Transparencia\DatasetAbierto;
use Illuminate\Support\Facades\DB;

class EvolucionTemporalPublisher extends BasePublisher
{
    public function code(): string
    {
        return 'DS-G04';
    }

    protected function tabla(): string
    {
        return 'pub_evolucion_temporal';
    }

    /**
     * Agrega avances por (programa, ejercicio_fiscal, trimestre).
     * `total_beneficiarios` se calcula como SUM(avances.resultado) — el modelo
     * `Avance` no tiene una columna explícita de beneficiarios, así que el
     * agregado refleja la suma de resultados de indicadores del programa
     * en cada trimestre. La etiqueta semántica del portal puede ajustarse
     * en N2-04 si emerge una columna de beneficiarios real.
     */
    protected function buildRows(DatasetAbierto $dataset): array
    {
        $now = now();

        return DB::connection('pgsql')
            ->table('avances')
            ->join('metas_periodo', 'avances.meta_periodo_id', '=', 'metas_periodo.id')
            ->join('indicadores', 'avances.indicador_id', '=', 'indicadores.id')
            ->join('mir_niveles', 'indicadores.mir_nivel_id', '=', 'mir_niveles.id')
            ->join('programa_presupuestarios', 'mir_niveles.programa_presupuestario_id', '=', 'programa_presupuestarios.id')
            ->select(
                'programa_presupuestarios.nombre as programa_nombre',
                'metas_periodo.ejercicio_fiscal as ejercicio_fiscal',
                'metas_periodo.periodo as trimestre',
                DB::raw('COALESCE(SUM(avances.resultado), 0) as total_beneficiarios'),
            )
            ->groupBy('programa_presupuestarios.nombre', 'metas_periodo.ejercicio_fiscal', 'metas_periodo.periodo')
            ->orderBy('programa_presupuestarios.nombre')
            ->orderBy('metas_periodo.ejercicio_fiscal')
            ->orderBy('metas_periodo.periodo')
            ->get()
            ->map(fn ($r) => [
                'programa_nombre' => $r->programa_nombre,
                'ejercicio_fiscal' => (int) $r->ejercicio_fiscal,
                'trimestre' => (int) $r->trimestre,
                'total_beneficiarios' => (int) $r->total_beneficiarios,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();
    }
}
