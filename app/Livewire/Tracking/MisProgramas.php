<?php

namespace App\Livewire\Tracking;

use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MisProgramas extends Component
{
    public function render()
    {
        $teamId = auth()->user()->currentTeam->id;

        // Programas donde el team del usuario es la UR administradora (team_id)
        // o participa como coadyuvante (pivote programa_team).
        $programas = ProgramaPresupuestario::query()
            ->where(function ($q) use ($teamId) {
                $q->where('team_id', $teamId)
                    ->orWhereHas('equipos', fn ($e) => $e->where('teams.id', $teamId));
            })
            ->withCount('mirNiveles')
            ->orderBy('clave')
            ->get();

        return view('livewire.tracking.mis-programas', [
            'programas' => $programas,
        ]);
    }
}
