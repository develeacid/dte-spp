<?php

namespace App\Enums;

enum TipoIndicador: string
{
    case ESTRATEGICO = 'estrategico';
    case GESTION = 'gestion';

    public function label(): string
    {
        return match($this) {
            self::ESTRATEGICO => 'Estratégico',
            self::GESTION => 'Gestión',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
