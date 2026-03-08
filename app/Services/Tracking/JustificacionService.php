<?php

namespace App\Services\Tracking;

use App\Models\Tracking\Avance;
use App\Services\Llm\LlmService;

class JustificacionService
{
    public function __construct(private LlmService $llmService) {}

    public function generar(Avance $avance): ?string
    {
        $indicador = $avance->indicador;
        $nivel = $indicador->mirNivel;
        $metaPeriodo = $avance->metaPeriodo;

        if (! $nivel || ! $metaPeriodo) {
            return null;
        }

        $supuestos = $nivel->supuestos ?? null;

        // Get historical avances for context
        $historial = Avance::where('indicador_id', $indicador->id)
            ->where('id', '!=', $avance->id)
            ->whereNotNull('resultado')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($a) => [
                'periodo' => $a->metaPeriodo->periodo ?? '?',
                'resultado' => $a->resultado,
                'semaforo' => $a->semaforo_calculado ?? 'sin dato',
            ])->toArray();

        // Calculate deviation
        $meta = (float) $metaPeriodo->meta_periodo;
        $desviacion = $meta > 0
            ? round((($avance->resultado - $meta) / $meta) * 100, 2)
            : 0;

        // Render prompt
        $prompt = $this->llmService->renderPrompt('prompts.tracking.justificar-avance', [
            'indicador' => $indicador,
            'nivel' => $nivel,
            'metaPeriodo' => $meta,
            'resultado' => $avance->resultado,
            'desviacion' => $desviacion,
            'semaforo' => $avance->semaforo_calculado ?? 'sin dato',
            'supuestos' => $supuestos,
            'historial' => $historial,
        ]);

        try {
            return $this->llmService->suggest($prompt);
        } catch (\Throwable) {
            return null;
        }
    }
}
