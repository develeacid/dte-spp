<?php

namespace App\Services;

use App\Models\EstadoValidacionPrograma;
use App\Models\Mml\MirNivel;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Collection;

class EstadoConsolidadoService
{
    /**
     * Recalcula el estado consolidado de un programa.
     */
    public function recalcular(int $programaId, int $ejercicio): EstadoValidacionPrograma
    {
        $estado = EstadoValidacionPrograma::firstOrCreate(
            ['programa_presupuestario_id' => $programaId, 'ejercicio_fiscal' => $ejercicio],
        );

        // --- Planeación ---
        $planeacion = $this->calcularPlaneacion($programaId);
        $estado->planeacion_estado = $planeacion['estado'];
        $estado->planeacion_detalle = $planeacion['detalle'];
        $estado->planeacion_actualizado_at = now();

        // --- Jurídico (stub Fase 1) ---
        $estado->juridico_estado = 'no_implementado';
        $estado->juridico_detalle = ['nota' => 'Módulo jurídico pendiente de implementación (Fase 3)'];

        // --- Financiero ---
        $financiero = $this->calcularFinanciero($programaId, $ejercicio);
        $estado->financiero_estado = $financiero['estado'];
        $estado->financiero_detalle = $financiero['detalle'];
        $estado->financiero_actualizado_at = now();

        // --- Consolidado ---
        $completas = 0;
        if (in_array($estado->planeacion_estado, ['mir_completa', 'mir_validada'])) {
            $completas++;
        }
        // Jurídico no cuenta en Fase 1
        if (in_array($estado->financiero_estado, ['costeado', 'calendarizado'])) {
            $completas++;
        }

        $estado->validaciones_completas = $completas;
        $estado->consolidado = match (true) {
            $completas >= 2 => 'completo',
            $completas >= 1 => 'parcial',
            default => 'critico',
        };

        $estado->save();

        return $estado;
    }

    /**
     * Estado resumido para mostrar en badges.
     */
    public function resumen(int $programaId, int $ejercicio): array
    {
        $estado = EstadoValidacionPrograma::where('programa_presupuestario_id', $programaId)
            ->where('ejercicio_fiscal', $ejercicio)
            ->first();

        if (! $estado) {
            $estado = $this->recalcular($programaId, $ejercicio);
        }

        return [
            'completas' => $estado->validaciones_completas,
            'total' => 3,
            'areas' => [
                'planeacion' => [
                    'estado' => $estado->planeacion_estado,
                    'label' => $this->labelPlaneacion($estado->planeacion_estado),
                ],
                'juridico' => [
                    'estado' => $estado->juridico_estado,
                    'label' => 'No implementado',
                ],
                'financiero' => [
                    'estado' => $estado->financiero_estado,
                    'label' => $this->labelFinanciero($estado->financiero_estado),
                ],
            ],
            'consolidado' => $estado->consolidado,
        ];
    }

    /**
     * Programas con validaciones incompletas para un team.
     */
    public function programasPendientes(int $teamId, int $ejercicio, ?string $area = null): Collection
    {
        $query = EstadoValidacionPrograma::whereHas('programa', fn ($q) => $q->where('team_id', $teamId))
            ->where('ejercicio_fiscal', $ejercicio)
            ->where('consolidado', '!=', 'completo');

        if ($area === 'planeacion') {
            $query->whereNotIn('planeacion_estado', ['mir_completa', 'mir_validada']);
        } elseif ($area === 'financiero') {
            $query->whereNotIn('financiero_estado', ['costeado', 'calendarizado']);
        }

        return $query->with('programa')->get();
    }

    /**
     * Texto de alerta según el estado.
     */
    public function alerta(int $programaId, int $ejercicio): ?array
    {
        $resumen = $this->resumen($programaId, $ejercicio);

        return match ($resumen['consolidado']) {
            'completo' => null,
            'parcial' => [
                'tipo' => 'info',
                'mensaje' => "Validaciones pendientes ({$resumen['completas']} de {$resumen['total']}).",
                'detalle' => 'Los resultados podrían estar en riesgo de observación en auditoría.',
            ],
            'critico' => [
                'tipo' => 'warning',
                'mensaje' => 'Validación incompleta — riesgo de observación ASFE.',
                'detalle' => $this->detalleAlerta($resumen['areas']),
            ],
            default => null,
        };
    }

    // --- Cálculos internos ---

    private function calcularPlaneacion(int $programaId): array
    {
        $programa = ProgramaPresupuestario::find($programaId);
        $niveles = MirNivel::where('programa_presupuestario_id', $programaId)->get();
        $tieneNiveles = $niveles->isNotEmpty();
        $tieneIndicadores = $tieneNiveles && $niveles->load('indicadores')->pluck('indicadores')->flatten()->isNotEmpty();
        $planeacionCompletada = $programa?->planeacion_completada_at !== null;

        $estado = match (true) {
            $planeacionCompletada => 'mir_completa',
            $tieneIndicadores => 'mir_borrador',
            $tieneNiveles => 'mir_borrador',
            default => 'incompleta',
        };

        return [
            'estado' => $estado,
            'detalle' => [
                'niveles' => $niveles->count(),
                'tiene_indicadores' => $tieneIndicadores,
                'planeacion_completada' => $planeacionCompletada,
            ],
        ];
    }

    private function calcularFinanciero(int $programaId, int $ejercicio): array
    {
        $partidas = PartidaPresupuestal::where('programa_presupuestario_id', $programaId)
            ->paraEjercicio($ejercicio)
            ->withCount(['avancesFinancieros', 'metasGasto'])
            ->get();

        $numPartidas = $partidas->count();
        $tieneAvances = $partidas->sum('avances_financieros_count') > 0;
        $tieneMetas = $partidas->sum('metas_gasto_count') > 0;

        $estado = match (true) {
            $numPartidas === 0 => 'sin_partidas',
            $tieneMetas => 'calendarizado',
            $tieneAvances => 'costeado',
            default => 'parcial',
        };

        return [
            'estado' => $estado,
            'detalle' => [
                'num_partidas' => $numPartidas,
                'tiene_avances' => $tieneAvances,
                'tiene_metas' => $tieneMetas,
            ],
        ];
    }

    private function labelPlaneacion(string $estado): string
    {
        return match ($estado) {
            'incompleta' => 'Incompleta',
            'mir_borrador' => 'MIR borrador',
            'mir_completa' => 'MIR completa',
            'mir_validada' => 'MIR validada',
            default => $estado,
        };
    }

    private function labelFinanciero(string $estado): string
    {
        return match ($estado) {
            'sin_partidas' => 'Sin partidas',
            'parcial' => 'Parcial',
            'costeado' => 'Costeado',
            'calendarizado' => 'Calendarizado',
            default => $estado,
        };
    }

    private function detalleAlerta(array $areas): string
    {
        $partes = [];
        foreach ($areas as $nombre => $info) {
            $partes[] = ucfirst($nombre) . ': ' . $info['label'];
        }

        return implode(', ', $partes);
    }
}
