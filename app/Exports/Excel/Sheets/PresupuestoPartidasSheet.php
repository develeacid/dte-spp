<?php

namespace App\Exports\Excel\Sheets;

use App\Services\Presupuesto\PresupuestoCapituloReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PresupuestoPartidasSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private PresupuestoCapituloReportService $service) {}

    public function collection(): Collection
    {
        return $this->service->partidas()->map(fn (array $r) => [
            $r['clave_partida'],
            $r['descripcion'],
            $r['capitulo'].' — '.$r['capitulo_label'],
            $r['monto_aprobado'],
            $r['monto_modificado'] ?? $r['monto_aprobado'],
            $r['monto_comprometido'],
            $r['monto_devengado'],
            $r['monto_pagado'],
            $r['porcentaje_ejercido'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Clave', 'Descripción', 'Capítulo', 'Aprobado', 'Modificado',
            'Comprometido', 'Devengado', 'Pagado', '% Ejercido',
        ];
    }

    public function title(): string
    {
        return 'Partidas';
    }
}
