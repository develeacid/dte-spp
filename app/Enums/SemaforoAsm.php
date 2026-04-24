<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum SemaforoAsm: string
{
    case VERDE = 'verde';
    case AMARILLO = 'amarillo';
    case ROJO = 'rojo';
    case VENCIDO = 'vencido';
    case CUMPLIDO = 'cumplido';

    public static function calcular(CarbonInterface $fechaCompromiso, StatusAsm $status): self
    {
        if ($status === StatusAsm::CUMPLIDO) {
            return self::CUMPLIDO;
        }

        $hoy = now()->startOfDay();
        $diasRestantes = $hoy->diffInDays($fechaCompromiso->copy()->startOfDay(), false);

        if ($diasRestantes < 0) {
            return self::VENCIDO;
        }

        if ($diasRestantes < 7) {
            return self::ROJO;
        }

        if ($diasRestantes <= 30) {
            return self::AMARILLO;
        }

        return self::VERDE;
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::VERDE => 'bg-green-100 text-green-800',
            self::AMARILLO => 'bg-yellow-100 text-yellow-800',
            self::ROJO => 'bg-red-100 text-red-800',
            self::VENCIDO => 'bg-gray-100 text-gray-800',
            self::CUMPLIDO => 'bg-blue-100 text-blue-800',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::VERDE => 'En plazo',
            self::AMARILLO => 'Próximo',
            self::ROJO => 'Urgente',
            self::VENCIDO => 'Vencido',
            self::CUMPLIDO => 'Cumplido',
        };
    }
}
