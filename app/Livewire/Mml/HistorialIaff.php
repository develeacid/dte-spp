<?php

namespace App\Livewire\Mml;

use App\Models\Presupuesto\Iaff;
use App\Models\ProgramaPresupuestario;
use App\Services\Presupuesto\IaffSnapshotService;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Historial IAFF')]
class HistorialIaff extends Component
{
    public ProgramaPresupuestario $programa;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
    }

    public function firmar(int $iaffId, IaffSnapshotService $service): void
    {
        $this->authorize('firmar_iaff');

        $iaff = Iaff::where('programa_id', $this->programa->id)->findOrFail($iaffId);

        try {
            $service->firmar($iaff, auth()->user());
            session()->flash('success', "IAFF {$iaff->ejercicio_fiscal}-T{$iaff->trimestre} firmado.");
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $iaffs = Iaff::where('programa_id', $this->programa->id)
            ->orderByDesc('ejercicio_fiscal')
            ->orderByDesc('trimestre')
            ->get();

        return view('livewire.mml.historial-iaff', ['iaffs' => $iaffs]);
    }
}
