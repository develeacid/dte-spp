<?php

namespace App\Livewire\Mml;

use App\Enums\EstadoCierreFiscal;
use App\Models\ProgramaPresupuestario;
use App\Services\Presupuesto\CierreFiscalService;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Cierre fiscal')]
class CierreFiscalPanel extends Component
{
    public ProgramaPresupuestario $programa;

    public int $ejercicio;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
        $this->ejercicio = (int) ($programa->ejercicio_fiscal ?? now()->year);
    }

    public function avanzar(CierreFiscalService $service): void
    {
        $this->authorize('gestionar_cierre_fiscal');

        $cierre = $service->iniciar($this->programa, $this->ejercicio);

        try {
            $service->avanzar($cierre, auth()->user());
            session()->flash('success', 'Fase de cierre fiscal avanzada.');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $cierre = app(CierreFiscalService::class)->iniciar($this->programa, $this->ejercicio);

        return view('livewire.mml.cierre-fiscal-panel', [
            'cierre' => $cierre,
            'fases' => EstadoCierreFiscal::cases(),
        ]);
    }
}
