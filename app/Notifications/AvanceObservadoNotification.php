<?php

namespace App\Notifications;

use App\Models\Tracking\Avance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AvanceObservadoNotification extends Notification
{
    use Queueable;

    public function __construct(private Avance $avance, private string $observacion) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'avance_observado',
            'avance_id' => $this->avance->id,
            'indicador_nombre' => $this->avance->indicador->nombre,
            'observacion' => $this->observacion,
            'message' => "El avance del indicador \"{$this->avance->indicador->nombre}\" fue observado: {$this->observacion}",
        ];
    }
}
