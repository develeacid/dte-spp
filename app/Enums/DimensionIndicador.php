<?php

namespace App\Enums;

enum DimensionIndicador: string
{
    case EFICACIA = 'eficacia';
    case EFICIENCIA = 'eficiencia';
    case CALIDAD = 'calidad';
    case ECONOMIA = 'economia';

    public function label(): string
    {
        return match ($this) {
            self::EFICACIA => 'Eficacia',
            self::EFICIENCIA => 'Eficiencia',
            self::CALIDAD => 'Calidad',
            self::ECONOMIA => 'Economía',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
