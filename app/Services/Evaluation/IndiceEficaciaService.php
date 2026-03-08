<?php

namespace App\Services\Evaluation;

use App\Enums\EstadoAvance;
use App\Enums\TipoNivelMir;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Mml\Indicador;
use App\Models\ProgramaPresupuestario;

class IndiceEficaciaService
{
    /**
     * Calcula el índice de eficacia para un programa en un ejercicio fiscal.
     */
    public function calcular(ProgramaPresupuestario $programa, int $ejercicio): EvaluacionPrograma
    {
        $pesos = config('evaluation.pesos');

        $niveles = $programa->mirNiveles()
            ->with(['indicadores' => fn ($q) => $q->where('activo_seguimiento', true)])
            ->get();

        $desgloseNiveles = [];
        $conteoSemaforos = ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'sin_dato' => 0];
        $totalEvaluados = 0;
        $totalNoEvaluados = 0;
        $indice = 0.0;

        foreach (TipoNivelMir::cases() as $tipoNivel) {
            $pesoKey = $tipoNivel->value;
            $peso = $pesos[$pesoKey] ?? 0;

            $indicadoresNivel = $niveles
                ->where('tipo_nivel', $tipoNivel)
                ->flatMap(fn ($nivel) => $nivel->indicadores);

            if ($indicadoresNivel->isEmpty()) {
                $desgloseNiveles[$pesoKey] = [
                    'peso' => $peso,
                    'promedio' => null,
                    'indicadores_evaluados' => 0,
                    'indicadores_no_evaluados' => 0,
                ];
                continue;
            }

            $porcentajes = [];
            $nivelNoEvaluados = 0;

            foreach ($indicadoresNivel as $indicador) {
                $avanceAprobado = $this->obtenerUltimoAvanceAprobado($indicador, $ejercicio);

                if (! $avanceAprobado) {
                    $nivelNoEvaluados++;
                    $totalNoEvaluados++;
                    $conteoSemaforos['sin_dato']++;
                    continue;
                }

                $meta = (float) $indicador->meta;

                if ($meta == 0) {
                    $nivelNoEvaluados++;
                    $totalNoEvaluados++;
                    $conteoSemaforos['sin_dato']++;
                    continue;
                }

                $resultado = (float) $avanceAprobado->resultado;
                $porcentaje = ($resultado / $meta) * 100;
                $porcentaje = max(0, min(200, $porcentaje));

                $porcentajes[] = $porcentaje;
                $totalEvaluados++;

                $semaforo = $avanceAprobado->semaforo_calculado ?? 'sin_dato';
                if (isset($conteoSemaforos[$semaforo])) {
                    $conteoSemaforos[$semaforo]++;
                } else {
                    $conteoSemaforos['sin_dato']++;
                }
            }

            $promedio = count($porcentajes) > 0
                ? array_sum($porcentajes) / count($porcentajes)
                : null;

            $desgloseNiveles[$pesoKey] = [
                'peso' => $peso,
                'promedio' => $promedio !== null ? round($promedio, 4) : null,
                'indicadores_evaluados' => count($porcentajes),
                'indicadores_no_evaluados' => $nivelNoEvaluados,
            ];

            if ($promedio !== null) {
                $indice += $peso * $promedio;
            }
        }

        return EvaluacionPrograma::updateOrCreate(
            [
                'programa_presupuestario_id' => $programa->id,
                'ejercicio_fiscal' => $ejercicio,
            ],
            [
                'indice_eficacia' => round($indice, 4),
                'desglose_niveles' => $desgloseNiveles,
                'conteo_semaforos' => $conteoSemaforos,
                'indicadores_evaluados' => $totalEvaluados,
                'indicadores_no_evaluados' => $totalNoEvaluados,
                'configuracion_calculo' => $pesos,
            ]
        );
    }

    /**
     * Obtiene el último avance aprobado para un indicador en un ejercicio fiscal.
     */
    private function obtenerUltimoAvanceAprobado(Indicador $indicador, int $ejercicio): ?\App\Models\Tracking\Avance
    {
        return $indicador->avances()
            ->whereHas('metaPeriodo', fn ($q) => $q->where('ejercicio_fiscal', $ejercicio))
            ->where('estado', EstadoAvance::APROBADO)
            ->latest('id')
            ->first();
    }
}
