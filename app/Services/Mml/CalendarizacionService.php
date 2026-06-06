<?php

namespace App\Services\Mml;

use App\Enums\FrecuenciaMedicion;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\RevisionMeta;
use App\Models\ProgramaPresupuestario;
use App\Services\Tracking\CalendarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     *
     * Audit trail (V2-E7): si la re-confirmación CAMBIA el valor de alguna meta
     * ya calendarizada, se exige una justificación; por cada meta modificada se
     * persiste un RevisionMeta (valor_anterior, valor_nuevo, justificacion, user).
     * La primera calendarización (sin metas previas) y la re-confirmación con los
     * mismos valores NO requieren justificación ni generan revisiones.
     */
    public function confirmar(
        ProgramaPresupuestario $programa,
        array $metasAjustadas,
        int $ejercicio,
        ?string $justificacion = null,
        ?int $userId = null,
    ): void {
        $calendario = new CalendarioService;

        // Cargar frecuencias de los indicadores en una sola consulta (evita N+1).
        $indicadorIds = array_column($metasAjustadas, 'indicador_id');
        $frecuencias = Indicador::whereIn('id', $indicadorIds)
            ->pluck('frecuencia', 'id');

        // Cargar las metas existentes (indicador+periodo) para detectar cambios.
        $existentes = MetaPeriodo::whereIn('indicador_id', $indicadorIds)
            ->where('ejercicio_fiscal', $ejercicio)
            ->get()
            ->keyBy(fn (MetaPeriodo $m) => $m->indicador_id.'-'.$m->periodo);

        // Detectar diffs ANTES de escribir nada: lista de metas existentes cuyo
        // valor cambia. Si hay alguna y no viene justificación → abortar.
        $cambios = [];
        foreach ($metasAjustadas as $indicadorData) {
            foreach ($indicadorData['periodos'] as $periodoData) {
                $clave = $indicadorData['indicador_id'].'-'.$periodoData['periodo'];
                $existente = $existentes[$clave] ?? null;
                if ($existente === null) {
                    continue; // Meta nueva: no es un cambio, no exige justificación.
                }
                if ($this->valorCambio($existente->meta_periodo, $periodoData['meta_periodo'])) {
                    $cambios[] = [
                        'meta_periodo' => $existente,
                        'valor_anterior' => $existente->meta_periodo,
                        'valor_nuevo' => $periodoData['meta_periodo'],
                    ];
                }
            }
        }

        if ($cambios !== [] && ($justificacion === null || trim($justificacion) === '')) {
            throw new \DomainException('Modificar metas ya calendarizadas requiere justificación.');
        }

        // Cachear fechas calculadas por frecuencia (mismas para todos los
        // indicadores con la misma frecuencia y ejercicio).
        $fechasPorFrecuencia = [];

        DB::transaction(function () use (
            $metasAjustadas, $frecuencias, $calendario, $ejercicio,
            &$fechasPorFrecuencia, $cambios, $justificacion, $userId
        ): void {
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

                    if ($fechas === null) {
                        // Path defensivo near-unreachable hoy (frecuencia no mapeable o
                        // periodo fuera de rango). El MetaPeriodo se persiste igual con
                        // fechas null, pero sin ventana de captura NUNCA será abierto por
                        // mir:abrir-periodos ni cerrado por mir:cerrar-vencidos. Lo dejamos
                        // observable en logs en vez de fallar silenciosamente.
                        Log::warning('MetaPeriodo sin ventana de captura: no abrirá vía mir:abrir-periodos', [
                            'indicador_id' => $indicadorId,
                            'periodo' => $periodoData['periodo'],
                            'ejercicio' => $ejercicio,
                            'frecuencia' => $frecuencia?->value,
                        ]);
                    }

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

            // Audit trail: una RevisionMeta por meta cambiada.
            foreach ($cambios as $cambio) {
                RevisionMeta::create([
                    'meta_periodo_id' => $cambio['meta_periodo']->id,
                    'valor_anterior' => $cambio['valor_anterior'],
                    'valor_nuevo' => $cambio['valor_nuevo'],
                    'justificacion' => $justificacion,
                    'user_id' => $userId,
                ]);
            }
        });
    }

    /**
     * Compara dos valores de meta con la precisión decimal de BD (4 decimales)
     * para evitar falsos positivos por representación float.
     */
    private function valorCambio(mixed $anterior, mixed $nuevo): bool
    {
        return number_format((float) $anterior, 4, '.', '')
            !== number_format((float) $nuevo, 4, '.', '');
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
