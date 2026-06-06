<?php

namespace App\Services\Presupuesto;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Services\Tracking\SemaforoService;

class SemaforoFinancieroService
{
    /**
     * Calcula semáforo financiero para una partida en un trimestre.
     *
     * @return string 'verde'|'amarillo'|'rojo'|'sin_datos'
     */
    public function calcular(PartidaPresupuestal $partida, int $trimestre): string
    {
        $partida->loadMissing(['avancesFinancieros', 'metasGasto']);

        $avance = $partida->avancesFinancieros->firstWhere('trimestre', $trimestre);
        if (! $avance) {
            return 'sin_datos';
        }

        $pagado = (float) $avance->monto_pagado;

        // Intentar comparar vs meta calendarizada
        $meta = $partida->metasGasto->firstWhere('trimestre', $trimestre);

        if ($meta && (float) $meta->monto_programado > 0) {
            $ratio = $pagado / (float) $meta->monto_programado;
        } else {
            // Fallback: ratio vs distribución lineal del monto efectivo
            $efectivo = $partida->monto_efectivo;
            if ($efectivo <= 0) {
                return 'sin_datos';
            }

            // Acumulado de pagado hasta este trimestre
            $pagadoAcum = $partida->avancesFinancieros
                ->where('trimestre', '<=', $trimestre)
                ->sum('monto_pagado');

            $esperado = $efectivo * ($trimestre / 4);
            $ratio = $esperado > 0 ? $pagadoAcum / $esperado : 0;
        }

        return $this->clasificarRatio($ratio);
    }

    /**
     * Semáforo consolidado por programa (promedio ponderado de partidas).
     */
    public function consolidadoPrograma(int $programaId, int $ejercicio, int $trimestre): string
    {
        $partidas = PartidaPresupuestal::where('programa_presupuestario_id', $programaId)
            ->paraEjercicio($ejercicio)
            ->with(['avancesFinancieros', 'metasGasto'])
            ->get();

        if ($partidas->isEmpty()) {
            return 'sin_datos';
        }

        $totalEfectivo = 0;
        $sumaPonderada = 0;

        foreach ($partidas as $partida) {
            $efectivo = $partida->monto_efectivo;
            $semaforo = $this->calcular($partida, $trimestre);

            $valor = match ($semaforo) {
                'verde' => 1,
                'amarillo' => 0.5,
                'rojo' => 0,
                default => null,
            };

            if ($valor !== null) {
                $sumaPonderada += $valor * $efectivo;
                $totalEfectivo += $efectivo;
            }
        }

        if ($totalEfectivo <= 0) {
            return 'sin_datos';
        }

        $promedio = $sumaPonderada / $totalEfectivo;

        return match (true) {
            $promedio >= 0.7 => 'verde',
            $promedio >= 0.4 => 'amarillo',
            default => 'rojo',
        };
    }

    /**
     * Semáforo combinado físico-financiero.
     *
     * @return array{fisico: string, financiero: string, combinado: string}
     */
    public function combinado(int $programaId, int $ejercicio, int $trimestre): array
    {
        $financiero = $this->consolidadoPrograma($programaId, $ejercicio, $trimestre);

        // Obtener semáforo físico del servicio de tracking
        $fisico = $this->obtenerSemaforoFisico($programaId, $ejercicio, $trimestre);

        $combinado = $this->combinarSemaforos($fisico, $financiero);

        return [
            'fisico' => $fisico,
            'financiero' => $financiero,
            'combinado' => $combinado,
        ];
    }

    private function clasificarRatio(float $ratio): string
    {
        $cfg = config('presupuesto.semaforo');

        return match (true) {
            $ratio >= $cfg['verde_min'] && $ratio <= $cfg['verde_max'] => 'verde',
            $ratio >= $cfg['amarillo_min'] && $ratio <= $cfg['amarillo_max'] => 'amarillo',
            default => 'rojo',
        };
    }

    private function obtenerSemaforoFisico(int $programaId, int $ejercicio, int $trimestre): string
    {
        // Calcular promedio de semáforos físicos de indicadores del programa
        $programa = ProgramaPresupuestario::with([
            'mirNiveles.indicadores.metasPeriodo' => fn ($q) => $q->where('periodo', $trimestre)
                ->where('ejercicio_fiscal', $ejercicio),
            'mirNiveles.indicadores.avances' => fn ($q) => $q->whereHas('metaPeriodo', fn ($mp) => $mp->where('periodo', $trimestre)->where('ejercicio_fiscal', $ejercicio)),
        ])->find($programaId);

        if (! $programa) {
            return 'sin_datos';
        }

        $semaforoService = app(SemaforoService::class);
        $colores = [];

        foreach ($programa->mirNiveles as $nivel) {
            foreach ($nivel->indicadores as $indicador) {
                $metaPeriodo = $indicador->metasPeriodo->first();
                $avance = $indicador->avances->first();

                if ($metaPeriodo && $avance && $avance->resultado !== null) {
                    $colores[] = $semaforoService->calcular(
                        (float) $avance->resultado,
                        $indicador,
                        (float) $metaPeriodo->meta_periodo
                    );
                }
            }
        }

        if (empty($colores)) {
            return 'sin_datos';
        }

        $valores = array_map(fn ($c) => match ($c) {
            'verde' => 1, 'amarillo' => 0.5, default => 0,
        }, $colores);

        $promedio = array_sum($valores) / count($valores);

        return match (true) {
            $promedio >= 0.7 => 'verde',
            $promedio >= 0.4 => 'amarillo',
            default => 'rojo',
        };
    }

    private function combinarSemaforos(string $fisico, string $financiero): string
    {
        if ($fisico === 'sin_datos' || $financiero === 'sin_datos') {
            return $fisico === 'sin_datos' ? $financiero : $fisico;
        }

        // Financiero verde + físico rojo → rojo (se gasta pero no se entrega)
        if ($financiero === 'verde' && $fisico === 'rojo') {
            return 'rojo';
        }

        // Peor de los dos
        $prioridad = ['rojo' => 0, 'amarillo' => 1, 'verde' => 2];

        return ($prioridad[$fisico] ?? 0) <= ($prioridad[$financiero] ?? 0) ? $fisico : $financiero;
    }
}
