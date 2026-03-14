<?php

namespace App\Notifications;

use App\Models\Juridico\DocumentoNormativo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentoProximoVencerNotification extends Notification
{
    use Queueable;

    public function __construct(
        private DocumentoNormativo $documento,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'documento_proximo_vencer',
            'documento_id' => $this->documento->id,
            'documento_nombre' => $this->documento->nombre,
            'programa_id' => $this->documento->programa_presupuestario_id,
            'fecha_vigencia' => $this->documento->fecha_vigencia?->toDateString(),
            'message' => "El documento '{$this->documento->nombre}' está próximo a vencer ({$this->documento->fecha_vigencia?->format('d/m/Y')}).",
        ];
    }
}
