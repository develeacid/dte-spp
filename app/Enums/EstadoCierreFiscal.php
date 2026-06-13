<?php

namespace App\Enums;

enum EstadoCierreFiscal: string
{
    case PREVALIDACION = 'prevalidacion';
    case CONSOLIDACION = 'consolidacion';
    case FIRMA = 'firma';
    case CERRADO = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::PREVALIDACION => 'Prevalidación',
            self::CONSOLIDACION => 'Consolidación',
            self::FIRMA => 'Firma',
            self::CERRADO => 'Cerrado',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::PREVALIDACION => 'bg-yellow-100 text-yellow-800',
            self::CONSOLIDACION => 'bg-blue-100 text-blue-800',
            self::FIRMA => 'bg-indigo-100 text-indigo-800',
            self::CERRADO => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Orden secuencial de la máquina de estados (forward-only).
     */
    public function orden(): int
    {
        return match ($this) {
            self::PREVALIDACION => 1,
            self::CONSOLIDACION => 2,
            self::FIRMA => 3,
            self::CERRADO => 4,
        };
    }

    /**
     * Siguiente fase, o null si ya es la terminal (CERRADO).
     */
    public function siguiente(): ?self
    {
        return match ($this) {
            self::PREVALIDACION => self::CONSOLIDACION,
            self::CONSOLIDACION => self::FIRMA,
            self::FIRMA => self::CERRADO,
            self::CERRADO => null,
        };
    }

    public function esTerminal(): bool
    {
        return $this === self::CERRADO;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
