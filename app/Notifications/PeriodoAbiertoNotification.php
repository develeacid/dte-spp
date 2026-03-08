<?php

namespace App\Notifications;

use App\Models\Tracking\Avance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PeriodoAbiertoNotification extends Notification
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
            'type' => 'periodo_abierto',
            'indicador_nombre' => $this->avance->indicador->nombre,
            'periodo' => $this->avance->metaPeriodo->periodo,
            'fecha_cierre' => $this->avance->metaPeriodo->fecha_cierre->toDateString(),
            'avance_id' => $this->avance->id,
        ];
    }
}
