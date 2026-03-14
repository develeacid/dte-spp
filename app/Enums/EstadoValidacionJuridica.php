<?php

namespace App\Enums;

enum EstadoValidacionJuridica: string
{
    case PENDIENTE = 'pendiente';
    case EN_REVISION = 'en_revision';
    case VALIDADO = 'validado';
    case RECHAZADO = 'rechazado';
    case VENCIDO = 'vencido';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EN_REVISION => 'En revisión',
            self::VALIDADO => 'Validado',
            self::RECHAZADO => 'Rechazado',
            self::VENCIDO => 'Vencido',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::PENDIENTE => 'bg-gray-100 text-gray-800',
            self::EN_REVISION => 'bg-yellow-100 text-yellow-800',
            self::VALIDADO => 'bg-green-100 text-green-800',
            self::RECHAZADO => 'bg-red-100 text-red-800',
            self::VENCIDO => 'bg-orange-100 text-orange-800',
        };
    }

    public function permiteEdicion(): bool
    {
        return in_array($this, [self::PENDIENTE, self::EN_REVISION, self::RECHAZADO]);
    }
}
