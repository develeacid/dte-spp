<?php

namespace App\Enums;

enum TipoDocumentoNormativo: string
{
    case REGLAS_OPERACION = 'reglas_operacion';
    case PERIODICO_OFICIAL = 'periodico_oficial';
    case REGLAMENTO_INTERIOR = 'reglamento_interior';
    case LEY_ORGANICA = 'ley_organica';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::REGLAS_OPERACION => 'Reglas de Operación',
            self::PERIODICO_OFICIAL => 'Periódico Oficial',
            self::REGLAMENTO_INTERIOR => 'Reglamento Interior',
            self::LEY_ORGANICA => 'Ley Orgánica',
            self::OTRO => 'Otro',
        };
    }
}
