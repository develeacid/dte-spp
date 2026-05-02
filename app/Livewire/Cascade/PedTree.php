<?php

namespace App\Livewire\Cascade;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Livewire\Component;

class PedTree extends Component
{
    public ?int $selectedPlanId = null;

    public ?int $selectedEjeId = null;

    public ?int $selectedTemaId = null;

    public ?int $selectedObjetivoId = null;

    public ?int $selectedEstrategiaId = null;

    public ?int $selectedLineaId = null;

    public string $activeTab = 'plan';

    public array $expandedNodes = [];

    protected $listeners = [
        'planCreated' => '$refresh',
        'planUpdated' => '$refresh',
        'planDeleted' => '$refresh',
        'nodeCreated' => '$refresh',
        'nodeUpdated' => '$refresh',
        'nodeDeleted' => '$refresh',
    ];

    public function mount(?int $planId = null)
    {
        $this->selectedPlanId = $planId;

        $activo = PedPlan::where('activo', true)->first();
        if ($activo) {
            $this->expandedNodes["plan-{$activo->id}"] = true;
        }
    }

    public function toggleNode(string $nodeKey): void
    {
        if (isset($this->expandedNodes[$nodeKey])) {
            unset($this->expandedNodes[$nodeKey]);
        } else {
            $this->expandedNodes[$nodeKey] = true;
        }
    }

    public function selectNode(string $type, int $id): void
    {
        match ($type) {
            'plan' => $this->selectedPlanId = $id,
            'eje' => $this->selectedEjeId = $id,
            'tema' => $this->selectedTemaId = $id,
            'objetivo' => $this->selectedObjetivoId = $id,
            'estrategia' => $this->selectedEstrategiaId = $id,
            'linea' => $this->selectedLineaId = $id,
        };

        $this->activeTab = $type;
    }

    public function getDependentsCount(string $type, int $id): array
    {
        return match ($type) {
            'plan' => ['ejes' => PedPlan::find($id)?->ejes()->count() ?? 0],
            'eje' => ['temas' => PedEje::find($id)?->temas()->count() ?? 0],
            'tema' => ['objetivos' => PedTema::find($id)?->objetivosEstrategicos()->count() ?? 0],
            'objetivo' => ['estrategias' => PedObjetivoEstrategico::find($id)?->estrategias()->count() ?? 0],
            'estrategia' => ['lineas' => PedEstrategia::find($id)?->lineasAccion()->count() ?? 0],
            'linea' => [],
        };
    }

    public function render()
    {
        $planes = PedPlan::with([
            'ejes.temas.objetivosEstrategicos.estrategias.lineasAccion',
        ])
            ->orderBy('activo', 'desc')
            ->orderBy('periodo_inicio', 'desc')
            ->get();

        return view('livewire.cascade.ped-tree', [
            'planes' => $planes,
        ]);
    }
}
