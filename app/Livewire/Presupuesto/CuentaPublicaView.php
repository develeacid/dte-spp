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

        return view('livewire.presupuesto.cuenta-publica-view', [
            'datos' => $datos,
            'resumenEjes' => $resumenEjes,
        ]);
    }
}
