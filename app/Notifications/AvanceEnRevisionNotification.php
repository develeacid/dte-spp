<?php

namespace App\Notifications;

use App\Models\Tracking\Avance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AvanceEnRevisionNotification extends Notification
{
    use Queueable;

    public function __construct(private Avance $avance, private string $operadorNombre) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'avance_en_revision',
            'avance_id' => $this->avance->id,
            'indicador_nombre' => $this->avance->indicador->nombre,
            'operador_nombre' => $this->operadorNombre,
            'message' => "El operador {$this->operadorNombre} envió a revisión el avance del indicador \"{$this->avance->indicador->nombre}\".",
        ];
    }
}
