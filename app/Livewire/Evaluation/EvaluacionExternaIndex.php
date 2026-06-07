<?php

namespace App\Livewire\Evaluation;

use App\Enums\EstadoEvaluacionExterna;
use App\Enums\TipoEvaluacionExterna;
use App\Models\Evaluation\EvaluacionExterna;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class EvaluacionExternaIndex extends Component
{
    use WithPagination;

    #[Url(as: 'programa', except: null)]
    public ?int $programaId = null;

    #[Url(as: 'tipo', except: '')]
    public string $tipoFiltro = '';

    #[Url(as: 'ejercicio', except: null)]
    public ?int $ejercicioFiscal = null;

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
        $query = EvaluacionExterna::query()
            ->with(['programa:id,clave,nombre'])
            ->when($this->programaId, fn ($q, $id) => $q->where('programa_presupuestario_id', $id))
            ->when($this->tipoFiltro !== '', fn ($q) => $q->where('tipo', $this->tipoFiltro))
            ->when($this->ejercicioFiscal, fn ($q, $anio) => $q->where('ejercicio_fiscal', $anio))
            ->orderByDesc('ejercicio_fiscal')
            ->orderByDesc('id');

        $countQuery = EvaluacionExterna::query()
            ->when($this->programaId, fn ($q, $id) => $q->where('programa_presupuestario_id', $id))
            ->when($this->tipoFiltro !== '', fn ($q) => $q->where('tipo', $this->tipoFiltro))
            ->when($this->ejercicioFiscal, fn ($q, $anio) => $q->where('ejercicio_fiscal', $anio));

        $total = (clone $countQuery)->count();
        $enProceso = (clone $countQuery)->where('estado', EstadoEvaluacionExterna::EN_PROCESO->value)->count();
        $concluidas = (clone $countQuery)->where('estado', EstadoEvaluacionExterna::CONCLUIDA->value)->count();

        $kpis = [
            ['label' => 'Total', 'value' => $total, 'color' => 'slate'],
            ['label' => 'En proceso', 'value' => $enProceso, 'color' => 'blue'],
            ['label' => 'Concluidas', 'value' => $concluidas, 'color' => 'green'],
        ];

        return view('livewire.evaluation.externa.index', [
            'evaluaciones' => $query->paginate(20),
            'programas' => ProgramaPresupuestario::orderBy('clave')->get(['id', 'clave', 'nombre']),
            'tipos' => TipoEvaluacionExterna::cases(),
            'ejercicios' => EvaluacionExterna::query()
                ->select('ejercicio_fiscal')
                ->distinct()
                ->orderByDesc('ejercicio_fiscal')
                ->pluck('ejercicio_fiscal'),
            'kpis' => $kpis,
        ]);
    }
}
