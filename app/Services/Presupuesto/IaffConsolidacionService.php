<?php

namespace App\Services\Presupuesto;

use App\Models\Evaluation\Asm;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;

/**
 * D4 · Consolida la sección 4 del IAFF: aspectos susceptibles de mejora (ASM)
 * del programa + observaciones de avances con semáforo ≠ verde del ejercicio.
 * Vista derivada, sin tabla nueva. La consume IaffSnapshotService.
 */
class IaffConsolidacionService
{
    public function consolidar(ProgramaPresupuestario $programa, int $ejercicio): array
    {
        return [
            'asms' => $this->asms($programa),
            'observaciones' => $this->observaciones($programa, $ejercicio),
        ];
    }

    private function asms(ProgramaPresupuestario $programa): array
    {
        return Asm::query()
            ->where('programa_presupuestario_id', $programa->id)
            ->orderBy('fecha_compromiso')
            ->get(['id', 'descripcion_aspecto', 'accion_mejora', 'status', 'porcentaje_avance', 'fecha_compromiso'])
            ->map(fn (Asm $a) => [
                'id' => $a->id,
                'aspecto' => $a->descripcion_aspecto,
                'accion_mejora' => $a->accion_mejora,
                'status' => $a->status?->value,
                'porcentaje_avance' => $a->porcentaje_avance,
                'fecha_compromiso' => $a->fecha_compromiso?->toDateString(),
            ])
            ->all();
    }

    private function observaciones(ProgramaPresupuestario $programa, int $ejercicio): array
    {
        return Avance::query()
            ->whereHas('metaPeriodo', function ($q) use ($ejercicio, $programa) {
                $q->where('ejercicio_fiscal', $ejercicio)
                    ->whereHas('indicador.mirNivel', fn ($q2) => $q2->where('programa_presupuestario_id', $programa->id));
            })
            ->with('metaPeriodo:id,indicador_id,periodo')
            ->get()
            ->filter(fn (Avance $a) => ($a->semaforo_ajustado ?? $a->semaforo_calculado) !== 'verde'
                && ($a->semaforo_ajustado ?? $a->semaforo_calculado) !== null)
            ->map(fn (Avance $a) => [
                'avance_id' => $a->id,
                'periodo' => $a->metaPeriodo->periodo,
                'semaforo' => $a->semaforo_ajustado ?? $a->semaforo_calculado,
                'analisis_desviacion' => $a->analisis_desviacion,
                'justificacion' => $a->justificacion_final,
            ])
            ->values()
            ->all();
    }
}
