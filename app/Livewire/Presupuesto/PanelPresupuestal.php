<?php

namespace App\Livewire\Presupuesto;

use App\Models\ProgramaPresupuestario;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PanelPresupuestal extends Component
{
    public int $filtroEjercicio;

    public function mount(): void
    {
        $this->filtroEjercicio = config('presupuesto.ejercicio_default');
    }

    public function render(): View
    {
        $teamId = auth()->user()->currentTeam->id;

        $programas = ProgramaPresupuestario::paraTeam($teamId)
            ->ejercicio($this->filtroEjercicio)
            ->with(['partidasPresupuestales' => fn ($q) => $q->with('avancesFinancieros')])
            ->orderBy('clave')
            ->get();

        $totalAprobado = 0;
        $totalEjercido = 0;

        foreach ($programas as $programa) {
            foreach ($programa->partidasPresupuestales as $partida) {
                $totalAprobado += $partida->monto_efectivo;
                $totalEjercido += $partida->avancesFinancieros->sum('monto_pagado');
            }
        }

        $pctEjercido = $totalAprobado > 0 ? round(($totalEjercido / $totalAprobado) * 100, 2) : 0;

        $kpis = [
            ['label' => 'Programas', 'value' => $programas->count()],
            ['label' => 'Aprobado', 'value' => '$'.number_format($totalAprobado, 0), 'color' => 'blue'],
            ['label' => 'Ejercido', 'value' => '$'.number_format($totalEjercido, 0), 'color' => 'amber'],
            ['label' => '% Ejercido', 'value' => $pctEjercido.'%', 'color' => $pctEjercido >= 75 ? 'green' : ($pctEjercido >= 40 ? 'amber' : 'red')],
        ];

        return view('livewire.presupuesto.panel-presupuestal', [
            'programas' => $programas,
            'totalAprobado' => $totalAprobado,
            'totalEjercido' => $totalEjercido,
            'pctEjercido' => $pctEjercido,
            'kpis' => $kpis,
        ]);
    }
}
