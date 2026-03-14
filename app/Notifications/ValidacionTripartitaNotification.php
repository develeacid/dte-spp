<?php

namespace App\Notifications;

use App\Models\ProgramaPresupuestario;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ValidacionTripartitaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private ProgramaPresupuestario $programa,
        private string $area,
        private string $nuevoEstado,
        private string $mensaje,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'validacion_tripartita',
            'programa_id' => $this->programa->id,
            'programa_clave' => $this->programa->clave,
            'programa_nombre' => $this->programa->nombre,
            'area' => $this->area,
            'nuevo_estado' => $this->nuevoEstado,
            'message' => $this->mensaje,
        ];
    }
}
