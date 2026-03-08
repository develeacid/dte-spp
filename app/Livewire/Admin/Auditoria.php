<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class Auditoria extends Component
{
    use WithPagination;

    #[Url]
    public string $subjectType = '';

    #[Url]
    public string $causerId = '';

    #[Url]
    public string $fechaDesde = '';

    #[Url]
    public string $fechaHasta = '';

    #[Url]
    public string $evento = '';

    /**
     * Mapa de tipos de modelo auditados con etiquetas legibles.
     */
    private const SUBJECT_TYPES = [
        'App\Models\User' => 'Usuario',
        'App\Models\PedPlan' => 'PED Plan',
        'App\Models\PedEje' => 'PED Eje',
        'App\Models\PedTema' => 'PED Tema',
        'App\Models\PedObjetivoEstrategico' => 'PED Objetivo Estratégico',
        'App\Models\Mml\MirNivel' => 'MIR Nivel',
        'App\Models\Mml\Indicador' => 'Indicador',
        'App\Models\Evaluation\EvaluacionPrograma' => 'Evaluación Programa',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('administrar_usuarios'), 403);

        if (empty($this->fechaDesde)) {
            $this->fechaDesde = now()->subDays(30)->toDateString();
        }
        if (empty($this->fechaHasta)) {
            $this->fechaHasta = now()->toDateString();
        }
    }

    public function updatedSubjectType(): void
    {
        $this->resetPage();
    }

    public function updatedCauserId(): void
    {
        $this->resetPage();
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatedEvento(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->subjectType = '';
        $this->causerId = '';
        $this->fechaDesde = now()->subDays(30)->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->evento = '';
        $this->resetPage();
    }

    public function getSubjectTypes(): array
    {
        return self::SUBJECT_TYPES;
    }

    public function getSubjectLabel(string $fqcn): string
    {
        return self::SUBJECT_TYPES[$fqcn] ?? class_basename($fqcn);
    }

    public function render()
    {
        $query = Activity::query()
            ->with('causer')
            ->whereBetween('created_at', [
                Carbon::parse($this->fechaDesde)->startOfDay(),
                Carbon::parse($this->fechaHasta)->endOfDay(),
            ])
            ->latest();

        if ($this->subjectType !== '') {
            $query->where('subject_type', $this->subjectType);
        }

        if ($this->causerId !== '') {
            $query->where('causer_id', (int) $this->causerId);
        }

        if ($this->evento !== '') {
            $query->where('event', $this->evento);
        }

        return view('livewire.admin.auditoria', [
            'activities' => $query->paginate(25),
            'subjectTypes' => $this->getSubjectTypes(),
            'usuarios' => \App\Models\User::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
