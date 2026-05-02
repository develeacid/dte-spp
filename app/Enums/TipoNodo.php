<?php

namespace App\Enums;

enum TipoNodo: string
{
    // Árbol de problemas
    case PROBLEMA_CENTRAL = 'problema_central';
    case CAUSA_DIRECTA = 'causa_directa';
    case CAUSA_INDIRECTA = 'causa_indirecta';
    case EFECTO_DIRECTO = 'efecto_directo';
    case EFECTO_INDIRECTO = 'efecto_indirecto';

    // Árbol de objetivos
    case OBJETIVO_CENTRAL = 'objetivo_central';
    case MEDIO_DIRECTO = 'medio_directo';
    case MEDIO_INDIRECTO = 'medio_indirecto';
    case FIN_DIRECTO = 'fin_directo';
    case FIN_INDIRECTO = 'fin_indirecto';

    public function label(): string
    {
        return match ($this) {
            self::PROBLEMA_CENTRAL => 'Problema Central',
            self::CAUSA_DIRECTA => 'Causa Directa',
            self::CAUSA_INDIRECTA => 'Causa Indirecta',
            self::EFECTO_DIRECTO => 'Efecto Directo',
            self::EFECTO_INDIRECTO => 'Efecto Indirecto',
            self::OBJETIVO_CENTRAL => 'Objetivo Central',
            self::MEDIO_DIRECTO => 'Medio Directo',
            self::MEDIO_INDIRECTO => 'Medio Indirecto',
            self::FIN_DIRECTO => 'Fin Directo',
            self::FIN_INDIRECTO => 'Fin Indirecto',
        };
    }

    public function esProblema(): bool
    {
        return in_array($this, [
            self::PROBLEMA_CENTRAL,
            self::CAUSA_DIRECTA,
            self::CAUSA_INDIRECTA,
            self::EFECTO_DIRECTO,
            self::EFECTO_INDIRECTO,
        ]);
    }

    public function esObjetivo(): bool
    {
        return ! $this->esProblema();
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::PROBLEMA_CENTRAL => 'bg-red-100 text-red-800 border-red-300',
            self::CAUSA_DIRECTA => 'bg-orange-100 text-orange-800 border-orange-300',
            self::CAUSA_INDIRECTA => 'bg-amber-100 text-amber-800 border-amber-300',
            self::EFECTO_DIRECTO => 'bg-rose-100 text-rose-800 border-rose-300',
            self::EFECTO_INDIRECTO => 'bg-pink-100 text-pink-800 border-pink-300',
            self::OBJETIVO_CENTRAL => 'bg-green-100 text-green-800 border-green-300',
            self::MEDIO_DIRECTO => 'bg-teal-100 text-teal-800 border-teal-300',
            self::MEDIO_INDIRECTO => 'bg-cyan-100 text-cyan-800 border-cyan-300',
            self::FIN_DIRECTO => 'bg-blue-100 text-blue-800 border-blue-300',
            self::FIN_INDIRECTO => 'bg-indigo-100 text-indigo-800 border-indigo-300',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
