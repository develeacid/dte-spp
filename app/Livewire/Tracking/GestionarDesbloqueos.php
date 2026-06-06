<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Desbloqueo;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class GestionarDesbloqueos extends Component
{
    public string $resolucionTexto = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('administrar_usuarios'), 403);
    }

    public function aprobar(int $desbloqueoId): void
    {
        abort_unless(auth()->user()->can('administrar_usuarios'), 403);

        $desbloqueo = Desbloqueo::where('estado', 'pendiente')->findOrFail($desbloqueoId);

        $desbloqueo->update([
            'estado' => 'aprobado',
            'resuelto_por' => auth()->id(),
            'resuelto_at' => now(),
        ]);

        $avance = $desbloqueo->avance;

        // Bypass state machine: exceptional unlock from APROBADO -> EN_CAPTURA
        $estadoAnterior = $avance->estado;
        $historial = $avance->historial_observaciones ?? [];
        $historial[] = [
            'fecha' => now()->toISOString(),
            'usuario_id' => auth()->id(),
            'usuario_nombre' => auth()->user()->name,
            'accion' => 'desbloqueo_aprobado',
            'estado_anterior' => $estadoAnterior->value,
            'estado_nuevo' => EstadoAvance::EN_CAPTURA->value,
            'observacion' => 'Desbloqueo excepcional aprobado. Motivo: '.$desbloqueo->motivo,
        ];

        $avance->update([
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'congelado_at' => null,
            'historial_observaciones' => $historial,
        ]);

        session()->flash('message', 'Desbloqueo aprobado. El avance ha sido descongelado.');
    }

    public function rechazar(int $desbloqueoId): void
    {
        abort_unless(auth()->user()->can('administrar_usuarios'), 403);

        $this->validate([
            'resolucionTexto' => 'required|string|min:5',
        ], [
            'resolucionTexto.required' => 'La resolucion es obligatoria al rechazar.',
            'resolucionTexto.min' => 'La resolucion debe tener al menos 5 caracteres.',
        ]);

        $desbloqueo = Desbloqueo::where('estado', 'pendiente')->findOrFail($desbloqueoId);

        $desbloqueo->update([
            'estado' => 'rechazado',
            'resolucion' => $this->resolucionTexto,
            'resuelto_por' => auth()->id(),
            'resuelto_at' => now(),
        ]);

        $this->reset('resolucionTexto');

        session()->flash('message', 'Solicitud de desbloqueo rechazada.');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('administrar_usuarios'), 403);

        $desbloqueos = Desbloqueo::where('estado', 'pendiente')
            ->with(['avance.indicador', 'solicitante'])
            ->latest()
            ->get();

        $aprobadosHoy = Desbloqueo::where('estado', 'aprobado')
            ->whereDate('resuelto_at', today())
            ->count();
        $rechazadosHoy = Desbloqueo::where('estado', 'rechazado')
            ->whereDate('resuelto_at', today())
            ->count();

        $kpis = [
            ['label' => 'Pendientes', 'value' => $desbloqueos->count(), 'color' => 'yellow'],
            ['label' => 'Aprobados hoy', 'value' => $aprobadosHoy, 'color' => 'green'],
            ['label' => 'Rechazados hoy', 'value' => $rechazadosHoy, 'color' => 'red'],
        ];

        return view('livewire.tracking.gestionar-desbloqueos', [
            'desbloqueos' => $desbloqueos,
            'kpis' => $kpis,
        ]);
    }
}
