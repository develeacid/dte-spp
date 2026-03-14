<?php

namespace App\Notifications;

use App\Models\ProgramaPresupuestario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SustentoLegalRechazadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        private ProgramaPresupuestario $programa,
        private string $observaciones,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sustento_legal_rechazado',
            'programa_id' => $this->programa->id,
            'programa_clave' => $this->programa->clave,
            'programa_nombre' => $this->programa->nombre,
            'observaciones' => $this->observaciones,
            'message' => "El sustento legal del programa {$this->programa->clave} fue rechazado: {$this->observaciones}",
        ];
    }
}
