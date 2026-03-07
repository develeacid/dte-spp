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
        return match($this) {
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
        return match($this) {
            self::SECTORIAL => 'Programas que abordan temas sectoriales específicos del desarrollo estatal.',
            self::ESPECIAL => 'Programas diseñados para atender problemáticas específicas o emergentes.',
            self::INSTITUCIONAL => 'Programas que orientan la gestión interna de una institución.',
            self::REGIONAL => 'Programas enfocados al desarrollo de regiones geográficas específicas.',
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
