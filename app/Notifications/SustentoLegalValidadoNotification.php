<?php

namespace App\Notifications;

use App\Models\ProgramaPresupuestario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SustentoLegalValidadoNotification extends Notification
{
    use Queueable;

    public function __construct(
        private ProgramaPresupuestario $programa,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sustento_legal_validado',
            'programa_id' => $this->programa->id,
            'programa_clave' => $this->programa->clave,
            'programa_nombre' => $this->programa->nombre,
            'message' => "El sustento legal del programa {$this->programa->clave} ha sido validado jurídicamente.",
        ];
    }
}
