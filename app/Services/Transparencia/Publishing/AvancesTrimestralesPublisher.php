<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Tracking\Avance;
use App\Models\Transparencia\DatasetAbierto;

class AvancesTrimestralesPublisher extends BasePublisher
{
    public function code(): string
    {
        return 'DS-03';
    }

    protected function tabla(): string
    {
        return 'pub_avances_trimestrales';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $now = now();

        return Avance::query()
            ->with(['metaPeriodo', 'indicador.mirNivel.programa'])
            ->orderBy('id')
            ->get()
            ->filter(fn ($a) => $a->metaPeriodo && $a->indicador?->mirNivel?->programa)
            ->map(function ($a) use ($now) {
                $meta = $a->metaPeriodo;
                $nivel = $a->indicador->mirNivel;
                $programa = $nivel->programa;

                return [
                    'ejercicio_fiscal' => $meta->ejercicio_fiscal,
                    'trimestre' => $meta->periodo,
                    'programa_clave' => $programa->clave,
                    'mir_nivel' => $nivel->tipo_nivel?->value ?? (string) $nivel->tipo_nivel,
                    'indicador_nombre' => $a->indicador->nombre,
                    'meta_trimestral' => $meta->meta_periodo,
                    'resultado' => $a->resultado,
                    'semaforo' => $a->semaforo_ajustado ?? $a->semaforo_calculado,
                    'tiene_justificacion' => ! empty($a->justificacion_final),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();
    }
}
