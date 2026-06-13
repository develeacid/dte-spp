<?php

namespace App\Enums;

/**
 * Tipo de adecuación presupuestaria (V2-E5). Ampliación suma al modificado, reducción resta.
 */
enum TipoModificacionPresupuestal: string
{
    case AMPLIACION = 'ampliacion';
    case REDUCCION = 'reduccion';

    public function label(): string
    {
        return match ($this) {
            self::AMPLIACION => 'Ampliación',
            self::REDUCCION => 'Reducción',
        };
    }

    /** Signo del efecto sobre el monto modificado. */
    public function signo(): int
    {
        return match ($this) {
            self::AMPLIACION => 1,
            self::REDUCCION => -1,
        };
    }
}
