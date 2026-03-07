<?php

namespace App\Livewire\Cascade;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MatrizAlineacionManager extends Component
{
    public string $activeTab = 'ped-pnd';

    protected $listeners = [
        'alineacionCreada' => '$refresh',
        'alineacionEliminada' => '$refresh',
    ];

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getStatsProperty(): array
    {
        return [
            'ped_pnd' => DB::table('alineacion_ped_pnd')->count(),
            'pnd_ods' => DB::table('alineacion_pnd_ods')->count(),
            'linea_programa' => DB::table('alineacion_linea_programa')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.cascade.matriz-alineacion-manager');
    }
}
