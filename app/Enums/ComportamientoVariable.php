<?php

namespace App\Enums;

enum ComportamientoVariable: string
{
    case ACUMULABLE = 'acumulable';
    case CONTINUA = 'continua';

    public function label(): string
    {
        return match($this) {
            self::ACUMULABLE => 'Acumulable',
            self::CONTINUA => 'Continua',
        };
    }
}
