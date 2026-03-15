<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Services\Tracking\AvanceEstadoService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FlujosAvance extends Component
{
    public Avance $avance;

    public string $observacionTexto = '';

    public function mount(Avance $avance): void
    {
        $avance->load(['indicador.mirNivel', 'metaPeriodo', 'capturador']);
        $this->avance = $avance;
    }

    public function enviarRevision(): void
    {
        $usuario = auth()->user();

        app(AvanceEstadoService::class)->transicionar(
            $this->avance,
            EstadoAvance::EN_REVISION,
            $usuario,
        );

        $this->avance->refresh();

        session()->flash('message', 'Avance enviado a revisión.');
    }

    public function aprobar(): void
    {
        $usuario = auth()->user();
        abort_unless($usuario->can('revisar_avance'), 403);

        app(AvanceEstadoService::class)->transicionar(
            $this->avance,
            EstadoAvance::APROBADO,
            $usuario,
        );

        $this->avance->refresh();

        session()->flash('message', 'Avance aprobado correctamente.');
    }

    public function observar(): void
    {
        $this->validate([
            'observacionTexto' => 'required|string|min:10',
        ], [
            'observacionTexto.required' => 'La observación es obligatoria.',
            'observacionTexto.min' => 'La observación debe tener al menos 10 caracteres.',
        ]);

        $usuario = auth()->user();
        abort_unless($usuario->can('revisar_avance'), 403);

        app(AvanceEstadoService::class)->transicionar(
            $this->avance,
            EstadoAvance::OBSERVADO,
            $usuario,
            $this->observacionTexto,
        );

        $this->avance->refresh();

        $this->observacionTexto = '';
        session()->flash('message', 'Avance observado. Se notificó al operador.');
    }

    public function corregir(): void
    {
        $usuario = auth()->user();

        app(AvanceEstadoService::class)->transicionar(
            $this->avance,
            EstadoAvance::EN_CAPTURA,
            $usuario,
        );

        $this->avance->refresh();

        session()->flash('message', 'Avance devuelto a captura para corrección.');
    }

    public function render()
    {
        return view('livewire.tracking.flujos-avance');
    }
}
