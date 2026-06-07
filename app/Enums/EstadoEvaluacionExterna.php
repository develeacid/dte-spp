<?php

namespace App\Enums;

enum EstadoEvaluacionExterna: string
{
    case EN_PROCESO = 'en_proceso';
    case CONCLUIDA = 'concluida';

    public function label(): string
    {
        return match ($this) {
            self::EN_PROCESO => 'En proceso',
            self::CONCLUIDA => 'Concluida',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
