<?php

namespace App\Enums;

enum TipoProgramaDerivado: string
{
    case SECTORIAL = 'sectorial';
    case ESPECIAL = 'especial';
    case INSTITUCIONAL = 'institucional';
    case REGIONAL = 'regional';

    /**
     * Retorna etiqueta legible para mostrar en UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::SECTORIAL => 'Programa Sectorial',
            self::ESPECIAL => 'Programa Especial',
            self::INSTITUCIONAL => 'Programa Institucional',
            self::REGIONAL => 'Programa Regional',
        };
    }

    /**
     * Retorna descripción del tipo.
     */
    public function descripcion(): string
    {
        return match ($this) {
            self::SECTORIAL => 'Programas que abordan temas sectoriales específicos del desarrollo estatal.',
            self::ESPECIAL => 'Programas diseñados para atender problemáticas específicas o emergentes.',
            self::INSTITUCIONAL => 'Programas que orientan la gestión interna de una institución.',
            self::REGIONAL => 'Programas enfocados al desarrollo de regiones geográficas específicas.',
        };
    }

    /**
     * Retorna el prefijo de clave del tipo (OS, OE, OI, OR).
     */
    public function prefijo(): string
    {
        return match ($this) {
            self::SECTORIAL => 'OS',
            self::ESPECIAL => 'OE',
            self::INSTITUCIONAL => 'OI',
            self::REGIONAL => 'OR',
        };
    }

    /**
     * Retorna clases CSS de Tailwind para badge de color según tipo.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::SECTORIAL => 'bg-blue-100 text-blue-800',
            self::ESPECIAL => 'bg-green-100 text-green-800',
            self::INSTITUCIONAL => 'bg-purple-100 text-purple-800',
            self::REGIONAL => 'bg-orange-100 text-orange-800',
        };
    }

    /**
     * Retorna todos los valores para validaciones.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
