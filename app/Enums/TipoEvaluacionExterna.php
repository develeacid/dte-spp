<?php

namespace App\Enums;

enum TipoEvaluacionExterna: string
{
    case DISENO = 'diseno';
    case PROCESOS = 'procesos';
    case CONSISTENCIA_RESULTADOS = 'consistencia_resultados';
    case IMPACTO = 'impacto';
    case EED = 'eed';

    public function label(): string
    {
        return match ($this) {
            self::DISENO => 'Diseño',
            self::PROCESOS => 'Procesos',
            self::CONSISTENCIA_RESULTADOS => 'Consistencia y Resultados',
            self::IMPACTO => 'Impacto',
            self::EED => 'Específica de Desempeño',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
