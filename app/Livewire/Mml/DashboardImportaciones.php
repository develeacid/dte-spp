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

        $procesados = $reportes->where('estado', 'procesado')->count();
        $pendientes = $reportes->where('estado', 'pendiente')->count();
        $descartados = $reportes->where('estado', 'descartado')->count();

        $kpis = [
            ['label' => 'Importaciones', 'value' => $reportes->count()],
            ['label' => 'Procesadas', 'value' => $procesados, 'color' => 'green'],
            ['label' => 'Pendientes', 'value' => $pendientes, 'color' => 'amber'],
            ['label' => 'Descartadas', 'value' => $descartados],
        ];

        return view('livewire.mml.dashboard-importaciones', [
            'reportes' => $reportes,
            'kpis' => $kpis,
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
