<?php

namespace App\Services\Mml;

use App\Enums\FrecuenciaMedicion;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use App\Services\Tracking\CalendarioService;

class CalendarizacionService
{
    /**
     * Generate proposed period goals for all active indicators of a programa.
     *
     * @return array<int, array{indicador_id: int, nombre: string, meta: string, frecuencia: string, periodos: array}>
     */
    public function generar(ProgramaPresupuestario $programa): array
    {
        $indicadores = Indicador::query()
            ->whereIn('mir_nivel_id', $programa->mirNiveles()->pluck('id'))
            ->where('activo_seguimiento', true)
            ->whereNotNull('meta')
            ->with('mirNivel')
            ->orderBy('orden')
            ->get();

        return $indicadores->map(function (Indicador $indicador) {
            $numPeriodos = $this->numeroPeriodos($indicador->frecuencia);
            $metaAnual = (float) $indicador->meta;
            $metaPorPeriodo = $numPeriodos > 0 ? $metaAnual / $numPeriodos : $metaAnual;

            $periodos = [];
            for ($i = 1; $i <= $numPeriodos; $i++) {
                $periodos[] = [
                    'periodo' => $i,
                    'meta_periodo' => round($metaPorPeriodo, 4),
                ];
            }

            return [
                'indicador_id' => $indicador->id,
                'nombre' => $indicador->nombre,
                'meta' => $indicador->meta,
                'frecuencia' => $indicador->frecuencia->value,
                'periodos' => $periodos,
            ];
        })->values()->toArray();
    }

    /**
     * Persist adjusted period goals for all indicators of a programa.
     *
     * Además de la meta por periodo, cada MetaPeriodo se persiste con la ventana
     * normativa de captura (fecha_apertura/fecha_cierre) calculada por
     * CalendarioService según la frecuencia del indicador. La fecha de cierre
     * sigue la norma SHCP (cierre del periodo + config('tracking.dias_ventana_captura')).
     *
     * Comportamiento de re-confirmación: el updateOrCreate matchea por
     * (indicador_id, periodo, ejercicio_fiscal). Re-confirmar SOBREESCRIBE las
     * fechas de los MetaPeriodo existentes (incluidos los sembrados por seeders
     * o producción) con las fechas recalculadas. Es intencional: re-confirmar el
     * calendario alinea las ventanas a la norma vigente.
     */
    public function confirmar(ProgramaPresupuestario $programa, array $metasAjustadas, int $ejercicio): void
    {
        $calendario = new CalendarioService;

        // Cargar frecuencias de los indicadores en una sola consulta (evita N+1).
        $indicadorIds = array_column($metasAjustadas, 'indicador_id');
        $frecuencias = Indicador::whereIn('id', $indicadorIds)
            ->pluck('frecuencia', 'id');

        // Cachear fechas calculadas por frecuencia (mismas para todos los
        // indicadores con la misma frecuencia y ejercicio).
        $fechasPorFrecuencia = [];

        foreach ($metasAjustadas as $indicadorData) {
            $indicadorId = $indicadorData['indicador_id'];

            $frecuencia = $frecuencias[$indicadorId] ?? null;
            $fechasPorPeriodo = [];
            if ($frecuencia instanceof FrecuenciaMedicion) {
                $clave = $frecuencia->value;
                if (! isset($fechasPorFrecuencia[$clave])) {
                    $fechasPorFrecuencia[$clave] = collect(
                        $calendario->calcularFechas($ejercicio, $frecuencia)
                    )->keyBy('periodo');
                }
                $fechasPorPeriodo = $fechasPorFrecuencia[$clave];
            }

            foreach ($indicadorData['periodos'] as $periodoData) {
                $fechas = $fechasPorPeriodo[$periodoData['periodo']] ?? null;

                MetaPeriodo::updateOrCreate(
                    [
                        'indicador_id' => $indicadorId,
                        'periodo' => $periodoData['periodo'],
                        'ejercicio_fiscal' => $ejercicio,
                    ],
                    [
                        'meta_periodo' => $periodoData['meta_periodo'],
                        'activo' => true,
                        'fecha_apertura' => $fechas['fecha_apertura'] ?? null,
                        'fecha_cierre' => $fechas['fecha_cierre'] ?? null,
                    ]
                );
            }
        }
    }

    /**
     * Return the number of periods for a given measurement frequency.
     */
    public function numeroPeriodos(FrecuenciaMedicion $frecuencia): int
    {
        return match ($frecuencia) {
            FrecuenciaMedicion::MENSUAL => 12,
            FrecuenciaMedicion::TRIMESTRAL => 4,
            FrecuenciaMedicion::SEMESTRAL => 2,
            FrecuenciaMedicion::ANUAL => 1,
            FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::SEXENAL => 1,
        };
    }
}
