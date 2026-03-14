<?php

namespace App\Exports\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CuentaPublicaEjesSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private Collection $resumenEjes,
        private int $ejercicio,
    ) {}

    public function title(): string
    {
        return 'Resumen por Eje PED';
    }

    public function headings(): array
    {
        return [
            'Eje PED',
            '# Programas',
            'Total Aprobado',
            'Total Ejercido',
            '% Ejercido',
        ];
    }

    public function array(): array
    {
        return $this->resumenEjes->map(fn ($eje) => [
            $eje->eje,
            count($eje->programas),
            $eje->total_aprobado,
            $eje->total_ejercido,
            $eje->pct_ejercido,
        ])->toArray();
    }
}
