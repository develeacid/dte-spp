<?php

namespace App\Livewire\Tracking;

use App\Models\Tracking\Avance;
use App\Models\Tracking\Desbloqueo;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SolicitarDesbloqueo extends Component
{
    public Avance $avance;

    public string $motivo = '';

    public function mount(Avance $avance): void
    {
        $this->avance = $avance->load(['indicador', 'metaPeriodo', 'desbloqueos.solicitante', 'desbloqueos.resolutor']);
    }

    public function solicitar(): void
    {
        $this->validate([
            'motivo' => 'required|string|min:10',
        ], [
            'motivo.required' => 'El motivo es obligatorio.',
            'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        if (! $this->avance->estaCongelado()) {
            session()->flash('error', 'El avance no esta congelado.');

            return;
        }

        $pendiente = Desbloqueo::where('avance_id', $this->avance->id)
            ->where('estado', 'pendiente')
            ->exists();

        if ($pendiente) {
            session()->flash('error', 'Ya existe una solicitud de desbloqueo pendiente para este avance.');

            return;
        }

        Desbloqueo::create([
            'avance_id' => $this->avance->id,
            'motivo' => $this->motivo,
            'solicitado_por' => auth()->id(),
            'estado' => 'pendiente',
        ]);

        $this->reset('motivo');
        $this->avance->load(['desbloqueos.solicitante', 'desbloqueos.resolutor']);

        session()->flash('message', 'Solicitud de desbloqueo enviada correctamente.');
    }

    public function render()
    {
        return view('livewire.tracking.solicitar-desbloqueo', [
            'desbloqueos' => $this->avance->desbloqueos()->with(['solicitante', 'resolutor'])->latest()->get(),
        ]);
    }
}
