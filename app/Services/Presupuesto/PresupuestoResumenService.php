<?php

namespace App\Services\Presupuesto;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PresupuestoResumenService
{
    private const TTL = 600;

    /**
     * Resumen financiero de un programa para un ejercicio fiscal.
     */
    public function resumenPrograma(int $programaId, int $ejercicio): object
    {
        return Cache::remember(
            "presupuesto:resumen:{$programaId}:{$ejercicio}",
            self::TTL,
            function () use ($programaId, $ejercicio) {
                $partidas = PartidaPresupuestal::where('programa_presupuestario_id', $programaId)
                    ->paraEjercicio($ejercicio)
                    ->with('avancesFinancieros')
                    ->get();

                $aprobado = $partidas->sum('monto_aprobado');
                $modificado = $partidas->sum(fn ($p) => $p->monto_modificado ?? 0);
                $efectivo = $partidas->sum(fn ($p) => $p->monto_efectivo);
                $comprometido = $partidas->sum(fn ($p) => $p->avancesFinancieros->sum('monto_comprometido'));
                $devengado = $partidas->sum(fn ($p) => $p->avancesFinancieros->sum('monto_devengado'));
                $pagado = $partidas->sum(fn ($p) => $p->avancesFinancieros->sum('monto_pagado'));

                return (object) [
                    'aprobado' => $aprobado,
                    'modificado' => $modificado,
                    'efectivo' => $efectivo,
                    'comprometido' => $comprometido,
                    'devengado' => $devengado,
                    'pagado' => $pagado,
                    'pct_ejercido' => $efectivo > 0 ? round(($pagado / $efectivo) * 100, 2) : 0,
                    'num_partidas' => $partidas->count(),
                ];
            }
        );
    }

    /**
     * Resumen por trimestre de un programa.
     */
    public function resumenTrimestral(int $programaId, int $ejercicio): Collection
    {
        $partidas = PartidaPresupuestal::where('programa_presupuestario_id', $programaId)
            ->paraEjercicio($ejercicio)
            ->with('avancesFinancieros')
            ->get();

        return collect([1, 2, 3, 4])->map(function ($trimestre) use ($partidas) {
            $comprometido = 0;
            $devengado = 0;
            $pagado = 0;

            foreach ($partidas as $partida) {
                $avance = $partida->avancesFinancieros->firstWhere('trimestre', $trimestre);
                if ($avance) {
                    $comprometido += (float) $avance->monto_comprometido;
                    $devengado += (float) $avance->monto_devengado;
                    $pagado += (float) $avance->monto_pagado;
                }
            }

            return (object) [
                'trimestre' => $trimestre,
                'comprometido' => $comprometido,
                'devengado' => $devengado,
                'pagado' => $pagado,
            ];
        });
    }

    /**
     * Índice de eficiencia = (% avance físico) / (% avance financiero).
     */
    public function indiceEficiencia(int $programaId, int $ejercicio, int $trimestre): ?float
    {
        $resumen = $this->resumenPrograma($programaId, $ejercicio);
        $pctFinanciero = $resumen->pct_ejercido;

        if ($pctFinanciero <= 0) {
            return null;
        }

        // Calcular % avance físico promedio
        $programa = ProgramaPresupuestario::with([
            'mirNiveles.indicadores.metasPeriodo' => fn ($q) => $q->where('periodo', '<=', $trimestre)
                ->where('ejercicio_fiscal', $ejercicio),
            'mirNiveles.indicadores.avances' => fn ($q) => $q->whereHas('metaPeriodo', fn ($mp) => $mp->where('periodo', '<=', $trimestre)->where('ejercicio_fiscal', $ejercicio)),
        ])->find($programaId);

        if (! $programa) {
            return null;
        }

        $totalMeta = 0;
        $totalResultado = 0;
        $count = 0;

        foreach ($programa->mirNiveles as $nivel) {
            foreach ($nivel->indicadores as $indicador) {
                $meta = $indicador->metasPeriodo->sum('meta_periodo');
                $resultado = $indicador->avances->sum('resultado');

                if ($meta > 0) {
                    $totalMeta += $meta;
                    $totalResultado += $resultado;
                    $count++;
                }
            }
        }

        if ($count === 0 || $totalMeta <= 0) {
            return null;
        }

        $pctFisico = ($totalResultado / $totalMeta) * 100;

        return round($pctFisico / $pctFinanciero, 2);
    }

    /**
     * Programas con subejercicio mayor al umbral configurable.
     */
    public function alertasSubejercicio(int $teamId, int $ejercicio, ?float $umbral = null): Collection
    {
        $umbral = $umbral ?? config('presupuesto.alerta_subejercicio_umbral');

        return Cache::remember(
            "presupuesto:alertas:{$teamId}:{$ejercicio}",
            self::TTL,
            function () use ($teamId, $ejercicio, $umbral) {
                $programas = ProgramaPresupuestario::paraTeam($teamId)
                    ->ejercicio($ejercicio)
                    ->with(['partidasPresupuestales' => fn ($q) => $q->with('avancesFinancieros')])
                    ->get();

                return $programas->filter(function ($programa) use ($umbral) {
                    $efectivo = $programa->partidasPresupuestales->sum(fn ($p) => $p->monto_efectivo);
                    $pagado = $programa->partidasPresupuestales->sum(fn ($p) => $p->avancesFinancieros->sum('monto_pagado'));

                    if ($efectivo <= 0) {
                        return false;
                    }

                    $pctNoEjercido = 1 - ($pagado / $efectivo);

                    return $pctNoEjercido >= $umbral;
                })->values();
            }
        );
    }

    /**
     * Desviación de gasto vs meta calendarizada por trimestre.
     */
    public function desviacionCalendarizada(int $programaId, int $ejercicio): Collection
    {
        $partidas = PartidaPresupuestal::where('programa_presupuestario_id', $programaId)
            ->paraEjercicio($ejercicio)
            ->with(['avancesFinancieros', 'metasGasto'])
            ->get();

        return collect([1, 2, 3, 4])->map(function ($trimestre) use ($partidas) {
            $totalProgramado = 0;
            $totalPagado = 0;

            foreach ($partidas as $partida) {
                $meta = $partida->metasGasto->firstWhere('trimestre', $trimestre);
                $avance = $partida->avancesFinancieros->firstWhere('trimestre', $trimestre);

                $totalProgramado += $meta ? (float) $meta->monto_programado : 0;
                $totalPagado += $avance ? (float) $avance->monto_pagado : 0;
            }

            $desviacion = $totalProgramado > 0
                ? round((($totalPagado - $totalProgramado) / $totalProgramado) * 100, 2)
                : null;

            return (object) [
                'trimestre' => $trimestre,
                'programado' => $totalProgramado,
                'pagado' => $totalPagado,
                'desviacion_pct' => $desviacion,
            ];
        });
    }
}
