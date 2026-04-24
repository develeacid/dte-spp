<?php

namespace App\Services\Presupuesto;

class CogCapituloCategorizer
{
    private const LABELS = [
        '1000' => 'Servicios Personales',
        '2000' => 'Materiales y Suministros',
        '3000' => 'Servicios Generales',
        '4000' => 'Transferencias, Asignaciones, Subsidios',
        '5000' => 'Bienes Muebles, Inmuebles e Intangibles',
        '6000' => 'Inversión Pública',
        '7000' => 'Inversiones Financieras',
        '8000' => 'Participaciones y Aportaciones',
        '9000' => 'Deuda Pública',
    ];

    public static function capitulo(?string $clavePartida): ?string
    {
        if ($clavePartida === null || $clavePartida === '') {
            return null;
        }

        if (! preg_match('/^([1-9])\d{0,3}$/', $clavePartida, $m)) {
            return null;
        }

        return $m[1] . '000';
    }

    public static function label(string $capitulo): string
    {
        return self::LABELS[$capitulo] ?? 'Desconocido';
    }

    public static function allCapitulos(): array
    {
        return array_keys(self::LABELS);
    }
}
