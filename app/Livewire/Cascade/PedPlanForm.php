<?php

namespace App\Livewire\Cascade;

use App\Models\PedPlan;
use Livewire\Component;

class PedPlanForm extends Component
{
    public ?PedPlan $plan = null;

    public string $nombre = '';
    public string $nivel_gobierno = 'estatal';
    public int $periodo_inicio;
    public int $periodo_fin;
    public bool $activo = false;

    public function mount(?PedPlan $plan = null): void
    {
        if ($plan && $plan->exists) {
            $this->plan = $plan;
            $this->nombre = $plan->nombre;
            $this->nivel_gobierno = $plan->nivel_gobierno;
            $this->periodo_inicio = $plan->periodo_inicio;
            $this->periodo_fin = $plan->periodo_fin;
            $this->activo = $plan->activo;
        } else {
            $this->periodo_inicio = (int) date('Y');
            $this->periodo_fin = (int) date('Y') + 6;
        }
    }

    public function save(): void
    {
        $this->validate([
            'nombre'         => ['required', 'string', 'max:255'],
            'nivel_gobierno' => ['required', 'in:estatal,municipal'],
            'periodo_inicio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo_fin'    => ['required', 'integer', 'min:2000', 'max:2100', 'gt:periodo_inicio'],
            'activo'         => ['boolean'],
        ]);

        if ($this->plan && $this->plan->exists) {
            $this->plan->update([
                'nombre'         => $this->nombre,
                'nivel_gobierno' => $this->nivel_gobierno,
                'periodo_inicio' => $this->periodo_inicio,
                'periodo_fin'    => $this->periodo_fin,
                'activo'         => $this->activo,
            ]);
            session()->flash('message', 'Plan actualizado correctamente.');
        } else {
            PedPlan::create([
                'nombre'         => $this->nombre,
                'nivel_gobierno' => $this->nivel_gobierno,
                'periodo_inicio' => $this->periodo_inicio,
                'periodo_fin'    => $this->periodo_fin,
                'activo'         => $this->activo,
            ]);
            session()->flash('message', 'Plan creado correctamente.');
        }

        $this->redirect(route('cascade.ped.index'));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.cascade.ped-plan-form');
    }
}
