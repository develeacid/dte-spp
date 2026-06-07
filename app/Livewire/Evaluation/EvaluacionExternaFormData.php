<?php

namespace App\Livewire\Evaluation;

use App\Enums\EstadoEvaluacionExterna;
use App\Enums\TipoEvaluacionExterna;
use App\Models\Evaluation\EvaluacionExterna;
use Livewire\Form;

class EvaluacionExternaFormData extends Form
{
    public ?int $programa_presupuestario_id = null;

    public ?int $ejercicio_fiscal = null;

    public string $tipo = '';

    public string $evaluador_externo = '';

    public ?string $fecha_inicio = null;

    public ?string $fecha_fin = null;

    public string $estado = 'en_proceso';

    public ?int $evaluacion_programa_id = null;

    public function rules(): array
    {
        return [
            'programa_presupuestario_id' => ['required', 'integer', 'exists:programa_presupuestarios,id'],
            'ejercicio_fiscal' => ['required', 'integer', 'between:2020,2050'],
            'tipo' => ['required', 'in:'.implode(',', TipoEvaluacionExterna::values())],
            'evaluador_externo' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', 'in:'.implode(',', EstadoEvaluacionExterna::values())],
            'evaluacion_programa_id' => ['nullable', 'integer', 'exists:evaluaciones_programa,id'],
        ];
    }

    public function setFromModel(EvaluacionExterna $evaluacionExterna): void
    {
        $this->programa_presupuestario_id = $evaluacionExterna->programa_presupuestario_id;
        $this->ejercicio_fiscal = $evaluacionExterna->ejercicio_fiscal;
        $this->tipo = $evaluacionExterna->tipo->value;
        $this->evaluador_externo = $evaluacionExterna->evaluador_externo;
        $this->fecha_inicio = $evaluacionExterna->fecha_inicio?->toDateString();
        $this->fecha_fin = $evaluacionExterna->fecha_fin?->toDateString();
        $this->estado = $evaluacionExterna->estado->value;
        $this->evaluacion_programa_id = $evaluacionExterna->evaluacion_programa_id;
    }
}
