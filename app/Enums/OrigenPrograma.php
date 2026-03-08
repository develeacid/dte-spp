<?php

namespace App\Enums;

enum OrigenPrograma: string
{
    case NUEVO = 'nuevo';
    case IMPORTADO = 'importado';

    public function label(): string
    {
        return match($this) {
            self::NUEVO => 'Nuevo',
            self::IMPORTADO => 'Importado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
