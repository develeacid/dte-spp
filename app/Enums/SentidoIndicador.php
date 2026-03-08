<?php

namespace App\Enums;

enum SentidoIndicador: string
{
    case ASCENDENTE = 'ascendente';
    case DESCENDENTE = 'descendente';
    case REGULAR = 'regular';

    public function label(): string
    {
        return match($this) {
            self::ASCENDENTE => 'Ascendente',
            self::DESCENDENTE => 'Descendente',
            self::REGULAR => 'Regular',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
