<?php

namespace App\Enums;

enum TipoSustentoLegal: string
{
    case FACULTAD_UR = 'facultad_ur';
    case MANDATO_GASTO = 'mandato_gasto';
    case REGLA_OPERACION = 'regla_operacion';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::FACULTAD_UR => 'Facultad de la UR',
            self::MANDATO_GASTO => 'Mandato de gasto',
            self::REGLA_OPERACION => 'Regla de operación',
            self::OTRO => 'Otro fundamento',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::FACULTAD_UR => 'Artículo de la Ley Orgánica que faculta a la UR para ejecutar este programa',
            self::MANDATO_GASTO => 'Ley o norma que obliga al Estado a gastar en esta materia',
            self::REGLA_OPERACION => 'Reglas de operación publicadas en el Periódico Oficial',
            self::OTRO => 'Otro fundamento jurídico aplicable',
        };
    }

    public function esObligatorio(): bool
    {
        return match ($this) {
            self::FACULTAD_UR, self::MANDATO_GASTO => true,
            self::REGLA_OPERACION, self::OTRO => false,
        };
    }
}
