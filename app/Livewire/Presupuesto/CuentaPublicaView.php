<?php

namespace App\Livewire\Presupuesto;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CuentaPublicaView extends Component
{
    public int $filtroEjercicio;

    public function mount(): void
    {
        $this->filtroEjercicio = config('presupuesto.ejercicio_default');
    }

    public function render(): \Illuminate\View\View
    {
        // TODO: Sprint C — implementar con CuentaPublicaService
        return view('livewire.presupuesto.cuenta-publica-view');
    }
}
