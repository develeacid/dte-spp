<?php

namespace App\Livewire\Evaluation;

use App\Enums\StatusAsm;
use App\Enums\TipoAccionAsm;
use App\Enums\TipoPlazoAsm;
use App\Models\Evaluation\Asm;
use Livewire\Form;

class AsmFormData extends Form
{
    public ?int $programa_presupuestario_id = null;

    public ?int $evaluacion_id = null;

    public string $descripcion_aspecto = '';

    public string $accion_mejora = '';

    public string $tipo_plazo = '';

    public string $tipo_accion = '';

    public ?int $responsable_id = null;

    public string $area_responsable = '';

    public ?string $fecha_compromiso = null;

    public ?string $fecha_cumplimiento = null;

    public int $porcentaje_avance = 0;

    public ?string $observacion_ultimo_avance = null;

    public string $status = 'pendiente';

    public ?string $evidencia_url = null;

    public function rules(): array
    {
        return [
            'programa_presupuestario_id' => ['required', 'integer', 'exists:programa_presupuestarios,id'],
            'evaluacion_id' => ['nullable', 'integer', 'exists:evaluaciones_programa,id'],
            'descripcion_aspecto' => ['required', 'string', 'min:10'],
            'accion_mejora' => ['required', 'string', 'min:10'],
            'tipo_plazo' => ['required', 'in:'.implode(',', TipoPlazoAsm::values())],
            'tipo_accion' => ['required', 'in:'.implode(',', TipoAccionAsm::values())],
            'responsable_id' => ['required', 'integer', 'exists:users,id'],
            'area_responsable' => ['required', 'string', 'max:255'],
            'fecha_compromiso' => ['required', 'date'],
            'fecha_cumplimiento' => [
                'nullable',
                'required_if:status,cumplido',
                'date',
                'after_or_equal:fecha_compromiso',
            ],
            'porcentaje_avance' => ['integer', 'min:0', 'max:100'],
            'observacion_ultimo_avance' => ['nullable', 'string'],
            'status' => ['required', 'in:'.implode(',', StatusAsm::values())],
            'evidencia_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function setFromModel(Asm $asm): void
    {
        $this->programa_presupuestario_id = $asm->programa_presupuestario_id;
        $this->evaluacion_id = $asm->evaluacion_id;
        $this->descripcion_aspecto = $asm->descripcion_aspecto;
        $this->accion_mejora = $asm->accion_mejora;
        $this->tipo_plazo = $asm->tipo_plazo->value;
        $this->tipo_accion = $asm->tipo_accion->value;
        $this->responsable_id = $asm->responsable_id;
        $this->area_responsable = $asm->area_responsable;
        $this->fecha_compromiso = $asm->fecha_compromiso?->toDateString();
        $this->fecha_cumplimiento = $asm->fecha_cumplimiento?->toDateString();
        $this->porcentaje_avance = $asm->porcentaje_avance;
        $this->observacion_ultimo_avance = $asm->observacion_ultimo_avance;
        $this->status = $asm->status->value;
        $this->evidencia_url = $asm->evidencia_url;
    }
}
