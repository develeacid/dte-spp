<?php

namespace App\Services\Evaluation;

use App\Enums\EstadoAvance;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Services\Llm\LlmService;

class LogicaVerticalService
{
    public function __construct(private LlmService $llmService) {}

    /**
     * Analiza la logica vertical (cadena causal) de la MIR de un programa
     * usando IA para detectar rupturas entre niveles.
     */
    public function analizar(EvaluacionPrograma $evaluacion): ?string
    {
        // Check if API key is configured
        if (empty(config('llm.api_key'))) {
            return 'Analisis de logica vertical no disponible. Configure LLM_API_KEY para habilitar el analisis automatico de rupturas causales.';
        }

        $programa = $evaluacion->programa;
        $ejercicio = $evaluacion->ejercicio_fiscal;

        // Build semaforo data per level
        $nivelesSemaforos = $this->buildNivelesSemaforos($programa, $ejercicio);

        // Render prompt
        $prompt = view('prompts.evaluation.analizar-rupturas', [
            'programa' => $programa,
            'ejercicio' => $ejercicio,
            'nivelesSemaforos' => $nivelesSemaforos,
        ])->render();

        try {
            $analisis = $this->llmService->suggest($prompt);

            // Save to evaluacion
            $evaluacion->update(['analisis_ia' => $analisis]);

            return $analisis;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Construye la estructura de semaforos por nivel MIR para el prompt.
     */
    private function buildNivelesSemaforos(ProgramaPresupuestario $programa, int $ejercicio): array
    {
        $niveles = $programa->mirNiveles()
            ->with(['indicadores' => function ($q) use ($ejercicio) {
                $q->where('activo_seguimiento', true)
                    ->with(['avances' => function ($q2) use ($ejercicio) {
                        $q2->whereHas('metaPeriodo', fn ($q3) => $q3->where('ejercicio_fiscal', $ejercicio))
                            ->where('estado', EstadoAvance::APROBADO)
                            ->latest();
                    }]);
            }])
            ->orderBy('orden')
            ->get();

        $result = [];
        foreach ($niveles as $nivel) {
            $indicadores = [];
            foreach ($nivel->indicadores as $ind) {
                $avance = $ind->avances->first();
                $indicadores[] = [
                    'nombre' => $ind->nombre,
                    'resultado' => $avance?->resultado,
                    'semaforo' => $avance?->semaforo_calculado,
                ];
            }
            $result[] = [
                'tipo' => $nivel->tipo_nivel->label(),
                'supuestos' => $nivel->supuestos,
                'indicadores' => $indicadores,
            ];
        }

        return $result;
    }
}
