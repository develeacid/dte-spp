<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class IndicadoresVencidos extends Component
{
    public function render()
    {
        $user = auth()->user();

        abort_unless($user->can('revisar_avance'), 403);

        $teamId = $user->currentTeam->id;

        $avances = Avance::query()
            ->where('estado', EstadoAvance::VENCIDO->value)
            ->whereHas('indicador.mirNivel', fn ($q) => $q->where('team_id', $teamId))
            ->with(['indicador', 'metaPeriodo', 'capturador'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.tracking.indicadores-vencidos', [
            'avances' => $avances,
        ]);
    }
}
