<?php

namespace App\Livewire\Mml;

use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CoberturaPrograma extends Component
{
    public ProgramaPresupuestario $programa;

    public string $estado = 'ok';

    public ?string $errorMessage = null;

    public array $coverage = [];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->authorize('ver_padron');
        $this->programa = $programa;

        if (! $programa->padron_geobase_activo) {
            $this->estado = 'inactivo';

            return;
        }
    }

    public function render()
    {
        return view('livewire.mml.cobertura-programa');
    }
}
