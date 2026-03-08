<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MisIndicadoresPendientes extends Component
{
    public function render()
    {
        $avances = Avance::query()
            ->where('capturado_por', auth()->id())
            ->where('estado', EstadoAvance::EN_CAPTURA->value)
            ->with(['indicador', 'metaPeriodo'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.tracking.mis-indicadores-pendientes', [
            'avances' => $avances,
        ]);
    }
}
