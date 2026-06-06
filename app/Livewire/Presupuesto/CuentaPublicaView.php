<?php

namespace App\Livewire\Presupuesto;

use App\Services\Presupuesto\CuentaPublicaService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CuentaPublicaView extends Component
{
    public int $filtroEjercicio;

    public bool $vistaEje = false;

    public function mount(): void
    {
        $this->filtroEjercicio = config('presupuesto.ejercicio_default');
    }

    public function toggleVistaEje(): void
    {
        $this->vistaEje = ! $this->vistaEje;
    }

    public function render(): View
    {
        $service = app(CuentaPublicaService::class);
        $teamId = auth()->user()->currentTeam->id;

        $datos = $service->generarDatos($this->filtroEjercicio, $teamId);
        $resumenEjes = $this->vistaEje
            ? $service->resumenPorEjePed($this->filtroEjercicio)
            : collect();

        $totalAprobado = 0.0;
        $totalEjercido = 0.0;
        $verdes = 0;
        foreach ($datos as $item) {
            $totalAprobado += (float) $item['financiero']->efectivo;
            $totalEjercido += (float) $item['financiero']->pagado;
            if (($item['semaforo']['combinado'] ?? null) === 'verde') {
                $verdes++;
            }
        }
        $pctEjercido = $totalAprobado > 0 ? round(($totalEjercido / $totalAprobado) * 100, 1) : 0;

        $kpis = [
            ['label' => 'Programas', 'value' => count($datos)],
            ['label' => 'Aprobado', 'value' => '$'.number_format($totalAprobado, 0), 'color' => 'blue'],
            ['label' => 'Ejercido', 'value' => '$'.number_format($totalEjercido, 0), 'color' => 'amber'],
            ['label' => 'Semáforo verde', 'value' => $verdes, 'color' => 'green'],
        ];

        return view('livewire.presupuesto.cuenta-publica-view', [
            'datos' => $datos,
            'resumenEjes' => $resumenEjes,
            'kpis' => $kpis,
        ]);
    }
}
