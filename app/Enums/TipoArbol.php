<?php

namespace App\Enums;

enum TipoArbol: string
{
    case PROBLEMA = 'problema';
    case OBJETIVOS = 'objetivos';

    public function label(): string
    {
        return match($this) {
            self::PROBLEMA => 'Árbol de Problemas',
            self::OBJETIVOS => 'Árbol de Objetivos',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
