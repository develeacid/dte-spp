<?php

namespace App\Notifications;

use App\Models\Tracking\Avance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AvanceVencidoNotification extends Notification
{
    use Queueable;

    public function __construct(private Avance $avance) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'avance_vencido',
            'indicador_nombre' => $this->avance->indicador->nombre,
            'periodo' => $this->avance->metaPeriodo->periodo,
            'message' => "El periodo {$this->avance->metaPeriodo->periodo} del indicador \"{$this->avance->indicador->nombre}\" ha vencido sin ser aprobado.",
        ];
    }
}
