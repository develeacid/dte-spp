<?php

namespace App\Notifications;

use App\Models\ProgramaPresupuestario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RopVerificadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private ProgramaPresupuestario $programa,
        private string $nombreDocumento,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rop_verificada',
            'programa_id' => $this->programa->id,
            'programa_clave' => $this->programa->clave,
            'programa_nombre' => $this->programa->nombre,
            'documento' => $this->nombreDocumento,
            'message' => "Las Reglas de Operación del programa {$this->programa->clave} han sido verificadas ({$this->nombreDocumento}).",
        ];
    }
}
