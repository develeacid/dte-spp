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

        $total = $avances->count();
        $urgentes = $avances->filter(fn ($a) => $a->metaPeriodo?->fecha_cierre
            && (int) now()->diffInDays($a->metaPeriodo->fecha_cierre, false) <= 3)->count();
        $proximos = $avances->filter(function ($a) {
            $dias = $a->metaPeriodo?->fecha_cierre
                ? (int) now()->diffInDays($a->metaPeriodo->fecha_cierre, false)
                : null;

            return $dias !== null && $dias > 3 && $dias <= 7;
        })->count();
        $lejanos = $avances->filter(fn ($a) => $a->metaPeriodo?->fecha_cierre
            && (int) now()->diffInDays($a->metaPeriodo->fecha_cierre, false) > 7)->count();

        $kpis = [
            ['label' => 'Pendientes', 'value' => $total, 'color' => 'slate'],
            ['label' => 'Urgentes (≤3d)', 'value' => $urgentes, 'color' => 'red'],
            ['label' => 'Próximos (4-7d)', 'value' => $proximos, 'color' => 'yellow'],
            ['label' => 'Lejanos (>7d)', 'value' => $lejanos, 'color' => 'green'],
        ];

        return view('livewire.tracking.mis-indicadores-pendientes', [
            'avances' => $avances,
            'kpis' => $kpis,
        ]);
    }
}
