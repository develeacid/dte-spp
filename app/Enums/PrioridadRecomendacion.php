<?php

namespace App\Enums;

enum PrioridadRecomendacion: string
{
    case BAJA = 'baja';
    case MEDIA = 'media';
    case ALTA = 'alta';

    public function label(): string
    {
        return match ($this) {
            self::BAJA => 'Baja',
            self::MEDIA => 'Media',
            self::ALTA => 'Alta',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
