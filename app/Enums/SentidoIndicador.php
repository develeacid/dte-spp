<?php

namespace App\Enums;

enum SentidoIndicador: string
{
    case ASCENDENTE = 'ascendente';
    case DESCENDENTE = 'descendente';

    public function label(): string
    {
        return match ($this) {
            self::ASCENDENTE => 'Ascendente',
            self::DESCENDENTE => 'Descendente',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
