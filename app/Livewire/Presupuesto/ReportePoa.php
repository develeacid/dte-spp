<?php

namespace App\Livewire\Presupuesto;

use App\Models\Reportes\VwPoa;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Programa Operativo Anual')]
class ReportePoa extends Component
{
    public ?int $ejercicio = null;

    public function mount(): void
    {
        $this->authorize('ver_datos_financieros');

        $this->ejercicio = (int) (config('presupuesto.ejercicio_default') ?? now()->year);
    }

    #[Computed]
    public function ejerciciosDisponibles()
    {
        return VwPoa::query()->select('ejercicio_fiscal')->distinct()
            ->orderByDesc('ejercicio_fiscal')->pluck('ejercicio_fiscal');
    }

    public function render()
    {
        $programas = VwPoa::where('ejercicio_fiscal', $this->ejercicio)
            ->orderBy('programa_clave')
            ->orderByDesc('tipo') // fisico antes que financiero
            ->orderBy('concepto')
            ->get()
            ->groupBy('programa_clave');

        return view('livewire.presupuesto.reporte-poa', ['programas' => $programas]);
    }
}
