<?php

namespace App\Livewire\Presupuesto;

use App\Enums\TipoModificacionPresupuestal;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Services\Presupuesto\ModificacionPresupuestalService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Adecuaciones presupuestales')]
class ModificacionesPartida extends Component
{
    public PartidaPresupuestal $partida;

    public string $tipo = 'ampliacion';

    public ?float $monto = null;

    public ?string $fecha = null;

    public ?string $oficio = null;

    public ?string $justificacion = null;

    public function mount(PartidaPresupuestal $partida): void
    {
        $this->authorize('gestionar_presupuesto');

        $this->partida = $partida;
    }

    public function registrar(ModificacionPresupuestalService $service): void
    {
        $this->authorize('gestionar_presupuesto');

        $datos = $this->validate([
            'tipo' => ['required', Rule::enum(TipoModificacionPresupuestal::class)],
            'monto' => ['required', 'numeric', 'gt:0'],
            'fecha' => ['required', 'date'],
            'oficio' => ['nullable', 'string', 'max:255'],
            'justificacion' => ['nullable', 'string'],
        ]);

        $service->registrar($this->partida, $datos);

        $this->reset(['monto', 'oficio', 'justificacion']);
        $this->tipo = 'ampliacion';
        session()->flash('success', 'Adecuación presupuestal registrada.');
    }

    public function render()
    {
        return view('livewire.presupuesto.modificaciones-partida', [
            'modificaciones' => $this->partida->modificaciones()->orderByDesc('fecha')->orderByDesc('id')->get(),
            'tipos' => TipoModificacionPresupuestal::cases(),
        ]);
    }
}
