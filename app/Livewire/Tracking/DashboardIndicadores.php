<?php

namespace App\Livewire\Tracking;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard de Indicadores')]
class DashboardIndicadores extends Component
{
    public ProgramaPresupuestario $programa;

    public array $expandedNiveles = [];

    public ?int $indicadorDetalleId = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
    }

    public function toggleNivel(int $nivelId): void
    {
        if (in_array($nivelId, $this->expandedNiveles)) {
            $this->expandedNiveles = array_values(array_diff($this->expandedNiveles, [$nivelId]));
        } else {
            $this->expandedNiveles[] = $nivelId;
        }
    }

    public function verDetalle(int $indicadorId): void
    {
        $this->indicadorDetalleId = $this->indicadorDetalleId === $indicadorId ? null : $indicadorId;
    }

    public function render()
    {
        $eagerLoad = [
            'indicadores.metasPeriodo.avance',
            'indicadores.cremaaValidacion',
            'indicadores.unidadMedida',
        ];

        $fin = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::FIN->value)
            ->with($eagerLoad)
            ->first();

        $proposito = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::PROPOSITO->value)
            ->with($eagerLoad)
            ->first();

        $componentes = $this->programa->mirNiveles()
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE->value)
            ->with([
                ...$eagerLoad,
                'actividades.indicadores.metasPeriodo.avance',
                'actividades.indicadores.cremaaValidacion',
                'actividades.indicadores.unidadMedida',
                'team',
            ])
            ->orderBy('orden')
            ->get();

        return view('livewire.tracking.dashboard-indicadores', compact(
            'fin', 'proposito', 'componentes'
        ));
    }
}
