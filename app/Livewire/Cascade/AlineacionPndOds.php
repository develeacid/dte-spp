<?php

namespace App\Livewire\Cascade;

use App\Models\OdsMeta;
use App\Models\PndObjetivo;
use Livewire\Component;

class AlineacionPndOds extends Component
{
    public bool $showForm = false;
    public string $searchPnd = '';
    public string $searchOds = '';
    public ?int $selectedPndId = null;
    public ?int $selectedOdsId = null;

    public function toggleForm(): void
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->reset(['searchPnd', 'searchOds', 'selectedPndId', 'selectedOdsId']);
        }
    }

    public function selectPnd(int $id): void
    {
        $this->selectedPndId = $id;
        $this->searchPnd = '';
    }

    public function selectOds(int $id): void
    {
        $this->selectedOdsId = $id;
        $this->searchOds = '';
    }

    public function crearAlineacion(): void
    {
        $this->validate([
            'selectedPndId' => 'required|exists:pnd_objetivos,id',
            'selectedOdsId' => 'required|exists:ods_metas,id',
        ]);

        $pndObjetivo = PndObjetivo::find($this->selectedPndId);
        $pndObjetivo->odsMetas()->syncWithoutDetaching([$this->selectedOdsId]);

        $this->reset(['showForm', 'searchPnd', 'searchOds', 'selectedPndId', 'selectedOdsId']);
        $this->dispatch('alineacionCreada');
        session()->flash('message', 'Alineación PND ↔ ODS creada exitosamente.');
    }

    public function eliminarAlineacion(int $pndId, int $odsId): void
    {
        $pndObjetivo = PndObjetivo::find($pndId);
        $pndObjetivo->odsMetas()->detach($odsId);

        $this->dispatch('alineacionEliminada');
        session()->flash('message', 'Alineación eliminada.');
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

    public function getOdsResultadosProperty()
    {
        if (strlen($this->searchOds) < 2) {
            return collect();
        }

        return OdsMeta::with('objetivo')
            ->where('descripcion', 'ilike', "%{$this->searchOds}%")
            ->orWhere('clave', 'ilike', "%{$this->searchOds}%")
            ->limit(10)
            ->get();
    }

    public function getAlineacionesProperty()
    {
        return PndObjetivo::with(['odsMetas.objetivo', 'eje'])
            ->whereHas('odsMetas')
            ->get();
    }

    public function render()
    {
        return view('livewire.cascade.alineacion-pnd-ods');
    }
}
