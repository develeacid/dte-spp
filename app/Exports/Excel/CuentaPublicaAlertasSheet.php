<?php

namespace App\Exports\Excel;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CuentaPublicaAlertasSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private array $datos,
        private int $ejercicio,
    ) {}

    public function title(): string
    {
        return 'Alertas';
    }

    public function headings(): array
    {
        return [
            'Programa',
            'Clave',
            'Tipo Alerta',
            'Detalle',
            '% Ejercido',
            'Eficiencia',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->datos as $item) {
            $alertas = [];

            // Subejercicio
            if ($item['financiero']->pct_ejercido < 50) {
                $alertas[] = ['Subejercicio', "Solo {$item['financiero']->pct_ejercido}% ejercido"];
            }

            // Sobreejercicio
            if ($item['financiero']->pct_ejercido > 100) {
                $alertas[] = ['Sobreejercicio', "{$item['financiero']->pct_ejercido}% ejercido"];
            }

            // Eficiencia baja (se gasta pero no se entrega)
            if ($item['eficiencia'] !== null && $item['eficiencia'] < 0.6) {
                $alertas[] = ['Ineficiencia', "Índice {$item['eficiencia']} — gasto sin resultados proporcionales"];
            }

            // Semáforo combinado rojo
            if ($item['semaforo']['combinado'] === 'rojo') {
                $alertas[] = ['Semáforo Rojo', 'Semáforo combinado físico-financiero en rojo'];
            }

            foreach ($alertas as [$tipo, $detalle]) {
                $rows[] = [
                    $item['programa']->nombre,
                    $item['programa']->clave,
                    $tipo,
                    $detalle,
                    $item['financiero']->pct_ejercido,
                    $item['eficiencia'],
                ];
            }
        }

        return $rows;
    }
}
