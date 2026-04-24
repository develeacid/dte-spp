<?php

namespace App\Enums;

enum TipoAccionAsm: string
{
    case NORMATIVO = 'normativo';
    case OPERATIVO = 'operativo';
    case GESTION_INFORMACION = 'gestion_informacion';

    public function label(): string
    {
        return match ($this) {
            self::NORMATIVO => 'Cambio normativo',
            self::OPERATIVO => 'Mejora operativa',
            self::GESTION_INFORMACION => 'Gestión de información',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
