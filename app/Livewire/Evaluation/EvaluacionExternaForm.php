<?php

namespace App\Livewire\Evaluation;

use App\Models\Evaluation\EvaluacionExterna;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Evaluation\InformeEvaluacion;
use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EvaluacionExternaForm extends Component
{
    public ?EvaluacionExterna $evaluacionExterna = null;

    public EvaluacionExternaFormData $form;

    public function mount(?EvaluacionExterna $evaluacionExterna = null): void
    {
        if ($evaluacionExterna && $evaluacionExterna->exists) {
            $this->evaluacionExterna = $evaluacionExterna;
            $this->form->setFromModel($evaluacionExterna);
        }
    }

    public function save()
    {
        $data = $this->form->validate();

        if ($this->evaluacionExterna && $this->evaluacionExterna->exists) {
            $this->evaluacionExterna->update($data);

            session()->flash('status', 'Evaluación externa actualizada.');
        } else {
            DB::transaction(function () use ($data) {
                $externa = EvaluacionExterna::create($data);

                InformeEvaluacion::create(['evaluacion_externa_id' => $externa->id]);
            });

            session()->flash('status', 'Evaluación externa creada.');
        }

        return redirect()->route('evaluation.externas.index');
    }

    public function render()
    {
        $evaluacionesPrograma = collect();

        if ($this->form->programa_presupuestario_id && $this->form->ejercicio_fiscal) {
            $evaluacionesPrograma = EvaluacionPrograma::query()
                ->where('programa_presupuestario_id', $this->form->programa_presupuestario_id)
                ->where('ejercicio_fiscal', $this->form->ejercicio_fiscal)
                ->orderBy('id')
                ->get();
        }

        return view('livewire.evaluation.externa.form', [
            'programas' => ProgramaPresupuestario::orderBy('clave')->get(),
            'evaluacionesPrograma' => $evaluacionesPrograma,
        ]);
    }
}
