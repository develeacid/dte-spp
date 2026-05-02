<?php

namespace App\Enums;

enum TipoNivelMir: string
{
    case FIN = 'fin';
    case PROPOSITO = 'proposito';
    case COMPONENTE = 'componente';
    case ACTIVIDAD = 'actividad';

    public function label(): string
    {
        return match ($this) {
            self::FIN => 'Fin',
            self::PROPOSITO => 'Propósito',
            self::COMPONENTE => 'Componente',
            self::ACTIVIDAD => 'Actividad',
        };
    }

    public function orden(): int
    {
        return match ($this) {
            self::FIN => 1,
            self::PROPOSITO => 2,
            self::COMPONENTE => 3,
            self::ACTIVIDAD => 4,
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::FIN => 'bg-blue-100 text-blue-800 border-blue-300',
            self::PROPOSITO => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::COMPONENTE => 'bg-amber-100 text-amber-800 border-amber-300',
            self::ACTIVIDAD => 'bg-violet-100 text-violet-800 border-violet-300',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
