<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Transparencia\DatasetAbierto;

class EvaluacionesAnualesPublisher extends BasePublisher
{
    public function code(): string
    {
        return 'DS-04';
    }

    protected function tabla(): string
    {
        return 'pub_evaluaciones_anuales';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $now = now();

        return EvaluacionPrograma::query()
            ->with('programa')
            ->orderBy('id')
            ->get()
            ->filter(fn ($e) => $e->programa)
            ->map(function ($e) use ($now) {
                $semaforos = $e->conteo_semaforos ?? [];

                return [
                    'ejercicio_fiscal' => $e->ejercicio_fiscal,
                    'programa_clave' => $e->programa->clave,
                    'indice_eficacia' => $e->indice_eficacia,
                    'semaforos_verde' => (int) ($semaforos['verde'] ?? 0),
                    'semaforos_amarillo' => (int) ($semaforos['amarillo'] ?? 0),
                    'semaforos_rojo' => (int) ($semaforos['rojo'] ?? 0),
                    'indicadores_total' => (int) ($e->indicadores_evaluados ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();
    }
}
