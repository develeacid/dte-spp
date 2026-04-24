<?php

namespace App\Exports\Excel\Sheets;

use App\Models\ProgramaPresupuestario;
use App\Services\Presupuesto\IaffFinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AvanceFinancieroSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
        private int $trimestre,
    ) {}

    public function collection(): Collection
    {
        $service = new IaffFinancialReportService($this->programa, $this->ejercicioFiscal, $this->trimestre);
        $rows = $service->rows()->map(fn (array $r) => [
            $r['clave_partida'],
            $r['descripcion'],
            $r['monto_aprobado'],
            $r['monto_modificado'] ?? $r['monto_aprobado'],
            $r['monto_comprometido'],
            $r['monto_devengado'],
            $r['monto_pagado'],
            $r['porcentaje_ejercido'],
        ]);

        $t = $service->totals($service->rows());
        $rows->push([
            'TOTAL', '', $t['aprobado'], $t['modificado'],
            $t['comprometido'], $t['devengado'], $t['pagado'],
            $t['porcentaje_ejercido'],
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Clave', 'Descripción', 'Aprobado', 'Modificado',
            'Comprometido', 'Devengado', 'Pagado', '% Ejercido',
        ];
    }

    public function title(): string
    {
        return 'Financiero';
    }
}
