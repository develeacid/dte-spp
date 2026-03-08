<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReporteListoNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $tipoReporte,
        public string $filename,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo_reporte' => $this->tipoReporte,
            'filename' => $this->filename,
            'mensaje' => "Tu reporte {$this->tipoReporte} está listo para descargar.",
            'url_descarga' => route('evaluation.exportar.descargar', ['filename' => $this->filename]),
        ];
    }
}
