<?php

namespace App\Livewire\Tracking;

use App\Models\Mml\Indicador;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DetalleIndicador extends Component
{
    public Indicador $indicador;

    public function mount(Indicador $indicador): void
    {
        abort_unless(auth()->user()->can('revisar_avance'), 403);

        // Multi-tenant: solo se accede a indicadores del team actual
        // (ya sea programa del team, o nivel UR coadyuvante del team).
        $teamId = auth()->user()->currentTeam->id;
        $nivel = $indicador->mirNivel;
        $perteneceAlTeam = ($nivel?->programa?->team_id === $teamId)
            || ($nivel?->team_id === $teamId);
        abort_unless($perteneceAlTeam, 403);

        $this->indicador = $indicador->load([
            'mirNivel.programa',
            'mirNivel.componente',
        ]);
    }

    public function render()
    {
        // Cargar avances ordenados por ejercicio y periodo desc (más reciente primero).
        $avances = $this->indicador->avances()
            ->with([
                'metaPeriodo',
                'variables.indicadorVariable',
                'evidencias',
                'capturador',
            ])
            ->get()
            ->sortByDesc(fn ($av) => sprintf('%04d-%d', $av->metaPeriodo?->ejercicio_fiscal ?? 0, $av->metaPeriodo?->periodo ?? 0))
            ->values();

        return view('livewire.tracking.detalle-indicador', [
            'avances' => $avances,
            'avanceActual' => $avances->first(),
            'trazabilidad' => $this->indicador->trazabilidad(),
        ]);
    }
}
