<?php

namespace App\Enums;

/**
 * Clasificación de la fuente de un Medio de Verificación (C-074).
 *
 * FIN y PROPÓSITO requieren fuentes externas e independientes (INEGI,
 * CONEVAL, Estadística 911, etc.); COMPONENTES y ACTIVIDADES admiten
 * registros administrativos propios en sistemas oficiales (regla B9, C-073).
 */
enum TipoFuenteMv: string
{
    case EXTERNA = 'externa';
    case ADMINISTRATIVA_PROPIA = 'administrativa_propia';
    case EVALUACION_EXTERNA = 'evaluacion_externa';

    public function label(): string
    {
        return match ($this) {
            self::EXTERNA => 'Fuente externa (INEGI, CONEVAL, etc.)',
            self::ADMINISTRATIVA_PROPIA => 'Registro administrativo propio',
            self::EVALUACION_EXTERNA => 'Evaluación externa',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
