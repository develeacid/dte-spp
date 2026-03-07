<?php

namespace App\Livewire;

use App\Models\PedPlan;
use Livewire\Component;

class PedPlanForm extends Component
{
    public ?PedPlan $plan = null;
    public bool $showModal = false;
    public string $mode = 'create';

    public string $nombre = '';
    public string $nivel_gobierno = 'estatal';
    public int $periodo_inicio = 2025;
    public int $periodo_fin = 2030;
    public bool $activo = false;

    protected $listeners = [
        'edit-plan' => 'editById',
    ];

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'nivel_gobierno' => ['required', 'in:estatal,municipal'],
            'periodo_inicio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo_fin' => ['required', 'integer', 'min:2000', 'max:2100', 'gt:periodo_inicio'],
            'activo' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $this->reset(['nombre', 'nivel_gobierno', 'periodo_inicio', 'periodo_fin', 'activo', 'plan']);
        $this->nivel_gobierno = 'estatal';
        $this->periodo_inicio = 2025;
        $this->periodo_fin = 2030;
        $this->mode = 'create';
        $this->showModal = true;
    }

    public function edit(PedPlan $plan): void
    {
        $this->plan = $plan;
        $this->nombre = $plan->nombre;
        $this->nivel_gobierno = $plan->nivel_gobierno ?? 'estatal';
        $this->periodo_inicio = $plan->periodo_inicio;
        $this->periodo_fin = $plan->periodo_fin;
        $this->activo = (bool) $plan->activo;
        $this->mode = 'edit';
        $this->showModal = true;
    }

    public function editById(int $id): void
    {
        $plan = PedPlan::find($id);
        if ($plan) {
            $this->edit($plan);
        }
    }

    public function save(): void
    {
        $this->validate();

        if ($this->mode === 'create') {
            $plan = PedPlan::create([
                'nombre' => $this->nombre,
                'nivel_gobierno' => $this->nivel_gobierno,
                'periodo_inicio' => $this->periodo_inicio,
                'periodo_fin' => $this->periodo_fin,
                'activo' => $this->activo,
            ]);

            $this->dispatch('planCreated');
            session()->flash('message', "Plan '{$plan->nombre}' creado exitosamente.");
        } else {
            $this->plan->update([
                'nombre' => $this->nombre,
                'nivel_gobierno' => $this->nivel_gobierno,
                'periodo_inicio' => $this->periodo_inicio,
                'periodo_fin' => $this->periodo_fin,
                'activo' => $this->activo,
            ]);

            $this->dispatch('planUpdated');
            session()->flash('message', 'Plan actualizado exitosamente.');
        }

        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->plan) {
            $this->plan->delete();
            $this->dispatch('planDeleted');
            session()->flash('message', 'Plan eliminado exitosamente.');
        }

        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.ped-plan-form');
    }
}
