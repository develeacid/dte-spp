<?php

namespace App\Enums;

enum EstadoAvance: string
{
    case EN_CAPTURA = 'en_captura';
    case EN_REVISION = 'en_revision';
    case OBSERVADO = 'observado';
    case APROBADO = 'aprobado';
    case VENCIDO = 'vencido';

    public function label(): string
    {
        return match($this) {
            self::EN_CAPTURA => 'En captura',
            self::EN_REVISION => 'En revisión',
            self::OBSERVADO => 'Observado',
            self::APROBADO => 'Aprobado',
            self::VENCIDO => 'Vencido',
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::EN_CAPTURA => 'bg-blue-100 text-blue-800',
            self::EN_REVISION => 'bg-yellow-100 text-yellow-800',
            self::OBSERVADO => 'bg-orange-100 text-orange-800',
            self::APROBADO => 'bg-green-100 text-green-800',
            self::VENCIDO => 'bg-red-100 text-red-800',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::EN_CAPTURA;
    }
}
