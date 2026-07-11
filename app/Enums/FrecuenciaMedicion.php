<?php

namespace App\Enums;

enum FrecuenciaMedicion: string
{
    case MENSUAL = 'mensual';
    case TRIMESTRAL = 'trimestral';
    case SEMESTRAL = 'semestral';
    case ANUAL = 'anual';
    case BIANUAL = 'bianual';
    case TRIANUAL = 'trianual';
    case SEXENAL = 'sexenal';

    public function label(): string
    {
        return match ($this) {
            self::MENSUAL => 'Mensual',
            self::TRIMESTRAL => 'Trimestral',
            self::SEMESTRAL => 'Semestral',
            self::ANUAL => 'Anual',
            self::BIANUAL => 'Bianual',
            self::TRIANUAL => 'Trianual',
            self::SEXENAL => 'Sexenal',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Orden de frecuencia: menor número = mide más seguido.
     * Sirve para comparar periodicidades (validación cruzada B7).
     */
    public function orden(): int
    {
        return match ($this) {
            self::MENSUAL => 1,
            self::TRIMESTRAL => 2,
            self::SEMESTRAL => 3,
            self::ANUAL => 4,
            self::BIANUAL => 5,
            self::TRIANUAL => 6,
            self::SEXENAL => 7,
        };
    }
}
