<?php

namespace App\Livewire\Presupuesto;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ImportarPresupuesto extends Component
{
    public function render(): \Illuminate\View\View
    {
        // TODO: Sprint C — implementar flujo CSV upload + preview + confirm
        return view('livewire.presupuesto.importar-presupuesto');
    }
}
