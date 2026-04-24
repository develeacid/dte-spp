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

    public function color(): string
    {
        return match ($this) {
            self::VERDE => 'green',
            self::AMARILLO => 'yellow',
            self::ROJO => 'red',
            self::VENCIDO => 'gray',
            self::CUMPLIDO => 'blue',
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
