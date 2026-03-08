<?php

namespace App\Enums;

enum EstadoPrograma: string
{
    case BORRADOR = 'borrador';
    case ACTIVO = 'activo';
    case CERRADO = 'cerrado';

    public function label(): string
    {
        return match($this) {
            self::BORRADOR => 'Borrador',
            self::ACTIVO => 'Activo',
            self::CERRADO => 'Cerrado',
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::BORRADOR => 'bg-yellow-100 text-yellow-800',
            self::ACTIVO => 'bg-green-100 text-green-800',
            self::CERRADO => 'bg-gray-100 text-gray-800',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
