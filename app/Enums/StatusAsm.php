<?php

namespace App\Enums;

enum StatusAsm: string
{
    case PENDIENTE = 'pendiente';
    case EN_PROCESO = 'en_proceso';
    case CUMPLIDO = 'cumplido';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EN_PROCESO => 'En proceso',
            self::CUMPLIDO => 'Cumplido',
        };
    }
}
