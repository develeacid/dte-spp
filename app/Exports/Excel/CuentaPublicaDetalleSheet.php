<?php

namespace App\Exports\Excel;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CuentaPublicaDetalleSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private array $datos,
        private int $ejercicio,
    ) {}

    public function title(): string
    {
        return 'Detalle Partidas';
    }

    public function headings(): array
    {
        return [
            'Programa',
            'Clave Partida',
            'Descripción',
            'Aprobado',
            'Modificado',
            'Meta T1', 'Meta T2', 'Meta T3', 'Meta T4',
            'Pagado T1', 'Pagado T2', 'Pagado T3', 'Pagado T4',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->datos as $item) {
            foreach ($item['programa']->partidasPresupuestales as $partida) {
                $metas = [0, 0, 0, 0];
                $pagados = [0, 0, 0, 0];

                foreach ($partida->metasGasto as $meta) {
                    $metas[$meta->trimestre - 1] = (float) $meta->monto_programado;
                }

                foreach ($partida->avancesFinancieros as $avance) {
                    $pagados[$avance->trimestre - 1] = (float) $avance->monto_pagado;
                }

                $rows[] = [
                    $item['programa']->clave,
                    $partida->clave_partida,
                    $partida->descripcion,
                    (float) $partida->monto_aprobado,
                    $partida->monto_modificado ? (float) $partida->monto_modificado : '',
                    $metas[0], $metas[1], $metas[2], $metas[3],
                    $pagados[0], $pagados[1], $pagados[2], $pagados[3],
                ];
            }
        }

        return $rows;
    }
}
