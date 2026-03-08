<?php

namespace App\Livewire\Mml;

use App\Models\Mml\ImportacionReporte;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DashboardImportaciones extends Component
{
    public function render()
    {
        $reportes = ImportacionReporte::with(['programa', 'creador'])
            ->where('team_id', Auth::user()->currentTeam->id)
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.mml.dashboard-importaciones', [
            'reportes' => $reportes,
        ]);
    }

    public function urlRetomar(ImportacionReporte $reporte): ?string
    {
        return match ($reporte->estado) {
            'pendiente' => route('mml.importar.completar', $reporte),
            'procesado' => $reporte->programa
                ? route('mml.mir', $reporte->programa)
                : null,
            default => null,
        };
    }
}
