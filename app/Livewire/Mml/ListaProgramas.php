<?php

namespace App\Livewire\Mml;

use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ListaProgramas extends Component
{
    public function render()
    {
        $programas = ProgramaPresupuestario::paraTeam(auth()->user()->currentTeam->id)
            ->with('creador')
            ->orderByDesc('updated_at')
            ->get();

        return view('livewire.mml.lista-programas', [
            'programas' => $programas,
        ]);
    }
}
