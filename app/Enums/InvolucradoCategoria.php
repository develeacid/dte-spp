<?php

namespace App\Enums;

enum InvolucradoCategoria: string
{
    case BENEFICIARIO_DIRECTO = 'beneficiario_directo';
    case BENEFICIARIO_INDIRECTO = 'beneficiario_indirecto';
    case EJECUTOR = 'ejecutor';
    case ALIADO = 'aliado';
    case NEUTRAL = 'neutral';
    case OPOSITOR = 'opositor';

    public function label(): string
    {
        return match ($this) {
            self::BENEFICIARIO_DIRECTO => 'Beneficiario directo',
            self::BENEFICIARIO_INDIRECTO => 'Beneficiario indirecto',
            self::EJECUTOR => 'Ejecutor',
            self::ALIADO => 'Aliado',
            self::NEUTRAL => 'Neutral',
            self::OPOSITOR => 'Opositor',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
