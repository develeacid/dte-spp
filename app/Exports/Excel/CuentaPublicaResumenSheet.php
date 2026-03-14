<?php

namespace App\Exports\Excel;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CuentaPublicaResumenSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private array $datos,
        private int $ejercicio,
    ) {}

    public function title(): string
    {
        return 'Resumen Ejecutivo';
    }

    public function headings(): array
    {
        return [
            'Programa',
            'Clave',
            'Eje PED',
            'Objetivo PED',
            'PND',
            'ODS',
            'Aprobado',
            'Ejercido',
            '% Avance Financiero',
            'Eficiencia',
            'Semáforo Físico',
            'Semáforo Financiero',
            'Semáforo Combinado',
        ];
    }

    public function array(): array
    {
        return array_map(fn ($item) => [
            $item['programa']->nombre,
            $item['programa']->clave,
            $item['alineacion']['ped_eje'] ?? '',
            $item['alineacion']['ped_objetivo'] ?? '',
            implode(', ', $item['alineacion']['pnd_objetivos']),
            implode(', ', $item['alineacion']['ods_metas']),
            $item['financiero']->efectivo,
            $item['financiero']->pagado,
            $item['financiero']->pct_ejercido,
            $item['eficiencia'],
            $item['semaforo']['fisico'],
            $item['semaforo']['financiero'],
            $item['semaforo']['combinado'],
        ], $this->datos);
    }
}
