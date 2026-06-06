<?php

namespace App\Livewire\Presupuesto;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class GestionPartidas extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filtroPrograma = '';

    public int $filtroEjercicio;

    public function mount(): void
    {
        $this->filtroEjercicio = config('presupuesto.ejercicio_default');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroPrograma(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEjercicio(): void
    {
        $this->resetPage();
    }

    public function eliminar(int $id): void
    {
        $partida = PartidaPresupuestal::paraTeam(auth()->user()->currentTeam->id)->findOrFail($id);
        $partida->delete();
        session()->flash('message', 'Partida eliminada correctamente.');
    }

    public function render(): View
    {
        $teamId = auth()->user()->currentTeam->id;

        $partidas = PartidaPresupuestal::paraTeam($teamId)
            ->paraEjercicio($this->filtroEjercicio)
            ->with(['programa', 'registrador'])
            ->when($this->filtroPrograma, fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('clave_partida', 'ilike', "%{$this->search}%")
                    ->orWhere('descripcion', 'ilike', "%{$this->search}%");
            }))
            ->orderBy('clave_partida')
            ->paginate(20);

        $programas = ProgramaPresupuestario::paraTeam($teamId)
            ->ejercicio($this->filtroEjercicio)
            ->orderBy('clave')
            ->get();

        $totales = PartidaPresupuestal::paraTeam($teamId)
            ->paraEjercicio($this->filtroEjercicio)
            ->selectRaw('COUNT(*) as total_partidas, COALESCE(SUM(monto_aprobado), 0) as total_aprobado, COALESCE(SUM(COALESCE(monto_modificado, monto_aprobado)), 0) as total_modificado')
            ->first();

        $kpis = [
            ['label' => 'Partidas', 'value' => (int) $totales->total_partidas],
            ['label' => 'Aprobado', 'value' => '$'.number_format((float) $totales->total_aprobado, 0), 'color' => 'blue'],
            ['label' => 'Modificado', 'value' => '$'.number_format((float) $totales->total_modificado, 0), 'color' => 'amber'],
            ['label' => 'Ejercicio', 'value' => $this->filtroEjercicio],
        ];

        return view('livewire.presupuesto.gestion-partidas', [
            'partidas' => $partidas,
            'programas' => $programas,
            'kpis' => $kpis,
        ]);
    }
}
