<?php

namespace App\Livewire\Evaluation;

use App\Models\Evaluation\Asm;
use App\Models\Evaluation\Recomendacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AsmForm extends Component
{
    public ?Asm $asm = null;

    public AsmFormData $form;

    public function mount(?Asm $asm = null): void
    {
        if ($asm && $asm->exists) {
            $this->asm = $asm;
            $this->form->setFromModel($asm);
        }
    }

    public function updated(string $property): void
    {
        if ($property === 'form.programa_presupuestario_id') {
            $this->form->recomendacion_id = null;
        }
    }

    private function recomendaciones(): Collection
    {
        if (! $this->form->programa_presupuestario_id) {
            return collect();
        }

        return Recomendacion::query()
            ->select('recomendaciones.id', 'recomendaciones.descripcion', 'evaluaciones_externas.id as evaluacion_externa_id')
            ->join('hallazgos', 'hallazgos.id', '=', 'recomendaciones.hallazgo_id')
            ->join('informes_evaluacion', 'informes_evaluacion.id', '=', 'hallazgos.informe_evaluacion_id')
            ->join('evaluaciones_externas', 'evaluaciones_externas.id', '=', 'informes_evaluacion.evaluacion_externa_id')
            ->where('evaluaciones_externas.programa_presupuestario_id', $this->form->programa_presupuestario_id)
            ->orderBy('recomendaciones.id')
            ->get()
            ->map(function ($r) {
                $r->etiqueta = 'EXT-'.$r->evaluacion_externa_id.': '.Str::limit($r->descripcion, 60);

                return $r;
            });
    }

    public function save()
    {
        $data = $this->form->validate();

        if ($this->asm && $this->asm->exists) {
            $this->asm->update($data);
        } else {
            Asm::create($data);
        }

        session()->flash('status', $this->asm && $this->asm->exists ? 'ASM actualizado.' : 'ASM creado.');

        return redirect()->route('evaluation.asms.index');
    }

    public function render()
    {
        return view('livewire.evaluation.asm.form', [
            'recomendaciones' => $this->recomendaciones(),
        ]);
    }
}
