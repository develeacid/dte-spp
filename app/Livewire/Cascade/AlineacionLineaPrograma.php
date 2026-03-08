<?php

namespace App\Livewire\Cascade;

use App\Models\PedLineaAccion;
use App\Models\ProgramaDerivadoObjetivo;
use Livewire\Component;

class AlineacionLineaPrograma extends Component
{
    public bool $showForm = false;
    public string $searchLinea = '';
    public string $searchPrograma = '';
    public ?int $selectedLineaId = null;
    public ?int $selectedProgramaId = null;

    public function toggleForm(): void
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->reset(['searchLinea', 'searchPrograma', 'selectedLineaId', 'selectedProgramaId']);
        }
    }

    public function selectLinea(int $id): void
    {
        $this->selectedLineaId = $id;
        $this->searchLinea = '';
    }

    public function selectPrograma(int $id): void
    {
        $this->selectedProgramaId = $id;
        $this->searchPrograma = '';
    }

    public function crearAlineacion(): void
    {
        $this->validate([
            'selectedLineaId' => 'required|exists:ped_lineas_accion,id',
            'selectedProgramaId' => 'required|exists:programas_derivados_objetivos,id',
        ]);

        $linea = PedLineaAccion::find($this->selectedLineaId);
        $linea->programasDerivadosObjetivos()->syncWithoutDetaching([$this->selectedProgramaId]);

        $this->reset(['showForm', 'searchLinea', 'searchPrograma', 'selectedLineaId', 'selectedProgramaId']);
        $this->dispatch('alineacionCreada');
        session()->flash('message', 'Alineación Línea ↔ Programa creada exitosamente.');
    }

    public function eliminarAlineacion(int $lineaId, int $programaId): void
    {
        $linea = PedLineaAccion::find($lineaId);
        $linea->programasDerivadosObjetivos()->detach($programaId);

        $this->dispatch('alineacionEliminada');
        session()->flash('message', 'Alineación eliminada.');
    }

    public function getLineaResultadosProperty()
    {
        if (strlen($this->searchLinea) < 2) {
            return collect();
        }

        return PedLineaAccion::with('estrategia.objetivoEstrategico.tema.eje.plan')
            ->where('descripcion', 'ilike', "%{$this->searchLinea}%")
            ->limit(10)
            ->get();
    }

    public function getProgramaResultadosProperty()
    {
        if (strlen($this->searchPrograma) < 2) {
            return collect();
        }

        return ProgramaDerivadoObjetivo::with('programa')
            ->where('descripcion', 'ilike', "%{$this->searchPrograma}%")
            ->limit(10)
            ->get();
    }

    public function getAlineacionesProperty()
    {
        return PedLineaAccion::with([
            'programasDerivadosObjetivos.programa',
            'estrategia.objetivoEstrategico.tema.eje.plan'
        ])
            ->whereHas('programasDerivadosObjetivos')
            ->get();
    }

    public function render()
    {
        return view('livewire.cascade.alineacion-linea-programa');
    }
}
