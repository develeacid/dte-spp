<?php

namespace App\Enums;

enum NivelJerarquiaLegal: string
{
    case CONSTITUCIONAL = 'constitucional';
    case FEDERAL = 'federal';
    case ESTATAL = 'estatal';
    case REGLAMENTARIO = 'reglamentario';
    case OPERATIVO = 'operativo';

    public function label(): string
    {
        return match ($this) {
            self::CONSTITUCIONAL => 'Constitucional',
            self::FEDERAL => 'Ley Federal/General',
            self::ESTATAL => 'Ley Estatal',
            self::REGLAMENTARIO => 'Reglamento',
            self::OPERATIVO => 'Reglas de Operación / Lineamientos',
        };
    }

    public function orden(): int
    {
        return match ($this) {
            self::CONSTITUCIONAL => 1,
            self::FEDERAL => 2,
            self::ESTATAL => 3,
            self::REGLAMENTARIO => 4,
            self::OPERATIVO => 5,
        };
    }
}
