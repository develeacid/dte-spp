<?php

namespace App\Enums;

enum TipoPlazoAsm: string
{
    case CORTO = 'corto';
    case MEDIANO = 'mediano';
    case LARGO = 'largo';

    public function label(): string
    {
        return match ($this) {
            self::CORTO => 'Corto plazo (< 6 meses)',
            self::MEDIANO => 'Mediano plazo (6 meses – 2 años)',
            self::LARGO => 'Largo plazo (> 2 años)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
