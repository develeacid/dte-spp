<?php

namespace App\Livewire\Evaluation;

use App\Enums\StatusAsm;
use App\Models\Evaluation\Asm;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AsmIndex extends Component
{
    use WithPagination;

    #[Url(as: 'programa', except: null)]
    public ?int $programaId = null;

    #[Url(as: 'status', except: '')]
    public string $statusFiltro = '';

    #[Url(as: 'responsable', except: null)]
    public ?int $responsableId = null;

    #[Url(as: 'q', except: '')]
    public string $busqueda = '';

    public function mount(?int $programaId = null): void
    {
        if ($programaId !== null) {
            $this->programaId = $programaId;
        }
    }

    public function updating($property): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Asm::query()
            ->with(['programa:id,clave,nombre', 'responsable:id,name'])
            ->when($this->programaId, fn ($q, $id) => $q->where('programa_presupuestario_id', $id))
            ->when($this->statusFiltro !== '', fn ($q) => $q->where('status', $this->statusFiltro))
            ->when($this->responsableId, fn ($q, $id) => $q->where('responsable_id', $id))
            ->when($this->busqueda !== '', fn ($q) => $q->where('descripcion_aspecto', 'ilike', "%{$this->busqueda}%"))
            ->orderBy('fecha_compromiso');

        $countQuery = Asm::query()
            ->when($this->programaId, fn ($q, $id) => $q->where('programa_presupuestario_id', $id))
            ->when($this->responsableId, fn ($q, $id) => $q->where('responsable_id', $id))
            ->when($this->busqueda !== '', fn ($q) => $q->where('descripcion_aspecto', 'ilike', "%{$this->busqueda}%"));

        $total = (clone $countQuery)->count();
        $pendientes = (clone $countQuery)->where('status', StatusAsm::PENDIENTE->value)->count();
        $enProceso = (clone $countQuery)->where('status', StatusAsm::EN_PROCESO->value)->count();
        $cumplidos = (clone $countQuery)->where('status', StatusAsm::CUMPLIDO->value)->count();

        $kpis = [
            ['label' => 'Total ASM', 'value' => $total, 'color' => 'slate'],
            ['label' => 'Pendientes', 'value' => $pendientes, 'color' => 'yellow'],
            ['label' => 'En proceso', 'value' => $enProceso, 'color' => 'blue'],
            ['label' => 'Cumplidos', 'value' => $cumplidos, 'color' => 'green'],
        ];

        return view('livewire.evaluation.asm.index', [
            'asms' => $query->paginate(20),
            'programas' => ProgramaPresupuestario::orderBy('clave')->get(['id', 'clave', 'nombre']),
            'statuses' => StatusAsm::cases(),
            'kpis' => $kpis,
        ]);
    }
}
