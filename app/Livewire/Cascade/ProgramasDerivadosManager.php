<?php

namespace App\Livewire\Cascade;

use App\Enums\TipoProgramaDerivado;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use Livewire\Component;

class ProgramasDerivadosManager extends Component
{
    // Filtros
    public string $filtroTipo = 'todos';

    // Estado de modales
    public bool $showProgramaModal = false;
    public bool $showObjetivoModal = false;
    public bool $showDeleteModal = false;

    // Programa seleccionado
    public ?ProgramaDerivado $programaSeleccionado = null;
    public ?ProgramaDerivadoObjetivo $objetivoSeleccionado = null;

    // Formulario de programa
    public string $programaNombre = '';
    public string $programaDescripcion = '';
    public string $programaTipo = '';
    public string $programaMode = 'create';

    // Formulario de objetivo
    public string $objetivoClave = '';
    public string $objetivoDescripcion = '';
    public string $objetivoMode = 'create';

    // Estado de expansión
    public array $expandedProgramas = [];

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount(): void
    {
        $primerPrograma = ProgramaDerivado::first();
        if ($primerPrograma) {
            $this->expandedProgramas[$primerPrograma->id] = true;
        }
    }

    // ============================================
    // FILTROS
    // ============================================

    public function setFiltroTipo(string $tipo): void
    {
        $this->filtroTipo = $tipo;
    }

    public function getTiposFiltroProperty(): array
    {
        return [
            'todos' => 'Todos',
            TipoProgramaDerivado::SECTORIAL->value => 'Sectoriales',
            TipoProgramaDerivado::ESPECIAL->value => 'Especiales',
            TipoProgramaDerivado::INSTITUCIONAL->value => 'Institucionales',
            TipoProgramaDerivado::REGIONAL->value => 'Regionales',
        ];
    }

    // ============================================
    // EXPANDIR/CONTRAER
    // ============================================

    public function togglePrograma(int $programaId): void
    {
        if (isset($this->expandedProgramas[$programaId])) {
            unset($this->expandedProgramas[$programaId]);
        } else {
            $this->expandedProgramas[$programaId] = true;
        }
    }

    public function expandAll(): void
    {
        $this->programas->each(fn($p) => $this->expandedProgramas[$p->id] = true);
    }

    public function collapseAll(): void
    {
        $this->expandedProgramas = [];
    }

    // ============================================
    // PROGRAMAS CRUD
    // ============================================

    public function createPrograma(): void
    {
        $this->resetProgramaForm();
        $this->programaMode = 'create';
        $this->showProgramaModal = true;
    }

    public function editPrograma(int $programaId): void
    {
        $this->programaSeleccionado = ProgramaDerivado::find($programaId);
        $this->programaNombre = $this->programaSeleccionado->nombre;
        $this->programaDescripcion = $this->programaSeleccionado->descripcion ?? '';
        $this->programaTipo = $this->programaSeleccionado->tipo->value;
        $this->programaMode = 'edit';
        $this->showProgramaModal = true;
    }

    public function savePrograma(): void
    {
        $this->validate([
            'programaNombre' => ['required', 'string', 'max:255'],
            'programaDescripcion' => ['nullable', 'string', 'max:1000'],
            'programaTipo' => ['required', 'in:' . implode(',', TipoProgramaDerivado::values())],
        ]);

        $planActivo = PedPlan::where('activo', true)->first();

        if (!$planActivo && $this->programaMode === 'create') {
            session()->flash('error', 'No existe un PED activo.');
            return;
        }

        if ($this->programaMode === 'create') {
            $programa = ProgramaDerivado::create([
                'ped_plan_id' => $planActivo->id,
                'nombre' => $this->programaNombre,
                'descripcion' => $this->programaDescripcion,
                'tipo' => $this->programaTipo,
            ]);
            $this->expandedProgramas[$programa->id] = true;
            session()->flash('message', "Programa '{$programa->nombre}' creado exitosamente.");
        } else {
            $this->programaSeleccionado->update([
                'nombre' => $this->programaNombre,
                'descripcion' => $this->programaDescripcion,
                'tipo' => $this->programaTipo,
            ]);
            session()->flash('message', 'Programa actualizado exitosamente.');
        }

        $this->showProgramaModal = false;
        $this->resetProgramaForm();
    }

    public function confirmDeletePrograma(int $programaId): void
    {
        $this->programaSeleccionado = ProgramaDerivado::withCount('objetivos')->find($programaId);
        $this->showDeleteModal = true;
    }

    public function deletePrograma(): void
    {
        if ($this->programaSeleccionado) {
            $nombre = $this->programaSeleccionado->nombre;
            $id = $this->programaSeleccionado->id;
            $this->programaSeleccionado->delete();
            unset($this->expandedProgramas[$id]);
            session()->flash('message', "Programa '{$nombre}' eliminado.");
        }

        $this->showDeleteModal = false;
        $this->programaSeleccionado = null;
    }

    private function resetProgramaForm(): void
    {
        $this->programaNombre = '';
        $this->programaDescripcion = '';
        $this->programaTipo = '';
        $this->programaSeleccionado = null;
        $this->resetErrorBag(['programaNombre', 'programaDescripcion', 'programaTipo']);
    }

    // ============================================
    // OBJETIVOS CRUD
    // ============================================

    public function createObjetivo(int $programaId): void
    {
        $this->programaSeleccionado = ProgramaDerivado::find($programaId);
        $this->resetObjetivoForm();
        $this->objetivoMode = 'create';
        $this->showObjetivoModal = true;
    }

    public function editObjetivo(int $objetivoId): void
    {
        $this->objetivoSeleccionado = ProgramaDerivadoObjetivo::find($objetivoId);
        $this->programaSeleccionado = $this->objetivoSeleccionado->programa;
        $this->objetivoClave = $this->objetivoSeleccionado->clave;
        $this->objetivoDescripcion = $this->objetivoSeleccionado->descripcion;
        $this->objetivoMode = 'edit';
        $this->showObjetivoModal = true;
    }

    public function saveObjetivo(): void
    {
        $this->validate([
            'objetivoClave' => ['required', 'string', 'max:20'],
            'objetivoDescripcion' => ['required', 'string', 'max:500'],
        ]);

        if ($this->objetivoMode === 'create') {
            ProgramaDerivadoObjetivo::create([
                'programa_derivado_id' => $this->programaSeleccionado->id,
                'clave' => $this->objetivoClave,
                'descripcion' => $this->objetivoDescripcion,
            ]);
            session()->flash('message', 'Objetivo creado exitosamente.');
        } else {
            $this->objetivoSeleccionado->update([
                'clave' => $this->objetivoClave,
                'descripcion' => $this->objetivoDescripcion,
            ]);
            session()->flash('message', 'Objetivo actualizado exitosamente.');
        }

        $this->showObjetivoModal = false;
        $this->resetObjetivoForm();
    }

    public function deleteObjetivo(int $objetivoId): void
    {
        $objetivo = ProgramaDerivadoObjetivo::find($objetivoId);
        $objetivo->delete();
        session()->flash('message', 'Objetivo eliminado.');
    }

    private function resetObjetivoForm(): void
    {
        $this->objetivoClave = '';
        $this->objetivoDescripcion = '';
        $this->objetivoSeleccionado = null;
        $this->resetErrorBag(['objetivoClave', 'objetivoDescripcion']);
    }

    // ============================================
    // HELPERS
    // ============================================

    public function getPlanActivoProperty(): ?PedPlan
    {
        return PedPlan::where('activo', true)->first();
    }

    public function getProgramasProperty()
    {
        return ProgramaDerivado::with('objetivos')
            ->when($this->filtroTipo !== 'todos', fn($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->planActivo, fn($q) => $q->where('ped_plan_id', $this->planActivo->id))
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();
    }

    public function getStatsProperty(): array
    {
        $query = ProgramaDerivado::when($this->planActivo, fn($q) => $q->where('ped_plan_id', $this->planActivo->id));

        return [
            'total' => $query->count(),
            'sectoriales' => (clone $query)->where('tipo', TipoProgramaDerivado::SECTORIAL)->count(),
            'especiales' => (clone $query)->where('tipo', TipoProgramaDerivado::ESPECIAL)->count(),
            'institucionales' => (clone $query)->where('tipo', TipoProgramaDerivado::INSTITUCIONAL)->count(),
            'regionales' => (clone $query)->where('tipo', TipoProgramaDerivado::REGIONAL)->count(),
            'objetivos' => ProgramaDerivadoObjetivo::when($this->planActivo, function ($q) {
                $q->whereIn('programa_derivado_id',
                    ProgramaDerivado::where('ped_plan_id', $this->planActivo->id)->pluck('id')
                );
            })->count(),
        ];
    }

    public function render()
    {
        return view('livewire.cascade.programas-derivados-manager');
    }
}
