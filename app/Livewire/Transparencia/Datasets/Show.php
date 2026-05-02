<?php

namespace App\Livewire\Transparencia\Datasets;

use App\Models\Transparencia\DatasetAbierto;
use DomainException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class Show extends Component
{
    public DatasetAbierto $dataset;

    public string $motivo = '';

    public function mount(DatasetAbierto $dataset): void
    {
        $this->dataset = $dataset;
    }

    public function enviarARevision(): void
    {
        $this->authorize('enviarARevision', $this->dataset);
        try {
            $this->dataset->enviarARevision();
            session()->flash('success', 'Dataset enviado a revisión.');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
        $this->dataset->refresh();
    }

    public function aprobar(): void
    {
        $this->authorize('aprobar', $this->dataset);
        try {
            $this->dataset->aprobar(auth()->user());
            session()->flash('success', 'Dataset aprobado.');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
        $this->dataset->refresh();
    }

    public function publicar(): void
    {
        $this->authorize('publicar', $this->dataset);
        try {
            $this->dataset->publicar();
            session()->flash('success', 'Dataset publicado.');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
        $this->dataset->refresh();
    }

    public function rechazar(): void
    {
        $this->authorize('rechazar', $this->dataset);
        $this->validate(['motivo' => 'required|string|min:5|max:500']);
        try {
            $this->dataset->rechazar($this->motivo);
            $this->motivo = '';
            session()->flash('success', 'Dataset rechazado y devuelto a borrador.');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
        $this->dataset->refresh();
    }

    public function retirar(): void
    {
        $this->authorize('retirar', $this->dataset);
        $this->validate(['motivo' => 'required|string|min:5|max:500']);
        try {
            $this->dataset->retirar($this->motivo);
            $this->motivo = '';
            session()->flash('success', 'Dataset retirado de publicación.');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
        $this->dataset->refresh();
    }

    public function render()
    {
        $actividad = Activity::where('subject_type', DatasetAbierto::class)
            ->where('subject_id', $this->dataset->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('livewire.transparencia.datasets.show', compact('actividad'));
    }
}
