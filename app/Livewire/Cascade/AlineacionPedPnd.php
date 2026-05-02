<?php

namespace App\Livewire\Cascade;

use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use Livewire\Component;

class AlineacionPedPnd extends Component
{
    public bool $showForm = false;

    public string $searchPed = '';

    public string $searchPnd = '';

    public ?int $selectedPedId = null;

    public ?int $selectedPndId = null;

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
        if (! $this->showForm) {
            $this->reset(['searchPed', 'searchPnd', 'selectedPedId', 'selectedPndId']);
        }
    }

    public function selectPed(int $id): void
    {
        $this->selectedPedId = $id;
        $this->searchPed = '';
    }

    public function selectPnd(int $id): void
    {
        $this->selectedPndId = $id;
        $this->searchPnd = '';
    }

    public function crearAlineacion(): void
    {
        $this->validate([
            'selectedPedId' => 'required|exists:ped_objetivos_estrategicos,id',
            'selectedPndId' => 'required|exists:pnd_objetivos,id',
        ]);

        $pedObjetivo = PedObjetivoEstrategico::find($this->selectedPedId);
        $pedObjetivo->pndObjetivos()->syncWithoutDetaching([$this->selectedPndId]);

        $this->reset(['showForm', 'searchPed', 'searchPnd', 'selectedPedId', 'selectedPndId']);
        $this->dispatch('alineacionCreada');
        session()->flash('message', 'Alineación PED ↔ PND creada exitosamente.');
    }

    public function eliminarAlineacion(int $pedId, int $pndId): void
    {
        $pedObjetivo = PedObjetivoEstrategico::find($pedId);
        $pedObjetivo->pndObjetivos()->detach($pndId);

        $this->dispatch('alineacionEliminada');
        session()->flash('message', 'Alineación eliminada.');
    }

    public function getPedResultadosProperty()
    {
        if (strlen($this->searchPed) < 2) {
            return collect();
        }

        return PedObjetivoEstrategico::with('tema.eje.plan')
            ->where('descripcion', 'ilike', "%{$this->searchPed}%")
            ->limit(10)
            ->get();
    }

    public function getPndResultadosProperty()
    {
        if (strlen($this->searchPnd) < 2) {
            return collect();
        }

        return PndObjetivo::with('eje')
            ->where('descripcion', 'ilike', "%{$this->searchPnd}%")
            ->orWhere('clave', 'ilike', "%{$this->searchPnd}%")
            ->limit(10)
            ->get();
    }

    public function getAlineacionesProperty()
    {
        return PedObjetivoEstrategico::with(['pndObjetivos.eje', 'tema.eje.plan'])
            ->whereHas('pndObjetivos')
            ->get();
    }

    public function render()
    {
        return view('livewire.cascade.alineacion-ped-pnd');
    }
}
