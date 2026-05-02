<?php

namespace App\Livewire\Transparencia\Datasets;

use App\Models\Transparencia\DatasetAbierto;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CrearEntrega extends Component
{
    public DatasetAbierto $dataset;
    public string $periodo = '';

    public function mount(DatasetAbierto $dataset): void
    {
        abort_if($dataset->periodo !== null, 404);
        $this->authorize('crearEntrega', $dataset);
        $this->dataset = $dataset;
    }

    public function save(): void
    {
        $this->authorize('crearEntrega', $this->dataset);
        $this->validate([
            'periodo' => ['required', 'string', 'regex:/^\d{4}(-Q[1-4])?$/'],
        ], [
            'periodo.regex' => 'El periodo debe tener formato YYYY o YYYY-Q[1-4] (ej. 2026 o 2026-Q1).',
        ]);

        try {
            $entrega = $this->dataset->clonarParaPeriodo($this->periodo, auth()->user());
            session()->flash('success', "Entrega {$entrega->dataset_clave} para {$entrega->periodo} creada.");
            $this->redirect(route('transparencia.datos-abiertos.show', $entrega), navigate: true);
        } catch (DomainException $e) {
            $this->addError('periodo', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.transparencia.datasets.crear-entrega');
    }
}
