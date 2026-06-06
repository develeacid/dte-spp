<?php

namespace App\Livewire\Mml;

use App\Enums\EstadoPrograma;
use App\Enums\OrigenPrograma;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ListaProgramas extends Component
{
    public string $nuevoNombre = '';

    public string $nuevoClave = '';

    public bool $mostrarFormulario = false;

    public function toggleFormulario(): void
    {
        $this->mostrarFormulario = ! $this->mostrarFormulario;
        $this->reset(['nuevoNombre', 'nuevoClave']);
        $this->resetValidation();
    }

    public function crearPrograma(): void
    {
        $this->validate([
            'nuevoNombre' => 'required|min:5|max:255',
            'nuevoClave' => 'required|min:2|max:30',
        ]);

        $programa = ProgramaPresupuestario::create([
            'nombre' => $this->nuevoNombre,
            'clave' => $this->nuevoClave,
            'team_id' => auth()->user()->currentTeam->id,
            'ejercicio_fiscal' => now()->year,
            'origen' => OrigenPrograma::NUEVO,
            'estado' => EstadoPrograma::BORRADOR,
            'created_by' => auth()->id(),
        ]);

        $this->redirect(route('mml.etapa1', $programa));
    }

    public function render()
    {
        $query = auth()->user()->hasRole('admin')
            ? ProgramaPresupuestario::query()
            : ProgramaPresupuestario::paraTeam(auth()->user()->currentTeam->id);

        $programas = $query
            ->with('creador')
            ->orderByDesc('updated_at')
            ->get();

        $total = $programas->count();
        $conMir = $programas->filter(fn ($p) => $p->mirNiveles()->exists())->count();
        $activos = $programas->where('estado', EstadoPrograma::ACTIVO)->count();
        $padron = $programas->where('padron_geobase_activo', true)->count();

        $kpis = [
            ['label' => 'Total programas', 'value' => $total],
            ['label' => 'Activos', 'value' => $activos, 'color' => 'green'],
            ['label' => 'Con MIR', 'value' => $conMir, 'color' => 'blue'],
            ['label' => 'Padrón GeoBase', 'value' => $padron, 'color' => 'amber'],
        ];

        return view('livewire.mml.lista-programas', [
            'programas' => $programas,
            'kpis' => $kpis,
        ]);
    }
}
