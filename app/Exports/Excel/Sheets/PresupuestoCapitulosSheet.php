<?php

namespace App\Exports\Excel\Sheets;

use App\Services\Presupuesto\PresupuestoCapituloReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PresupuestoCapitulosSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private PresupuestoCapituloReportService $service) {}

    public function collection(): Collection
    {
        return $this->service->capitulos()->map(fn (array $c) => [
            $c['capitulo'],
            $c['label'],
            $c['aprobado'],
            $c['modificado'],
            $c['comprometido'],
            $c['devengado'],
            $c['pagado'],
            $c['porcentaje_ejercido'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Capítulo', 'Descripción', 'Aprobado', 'Modificado',
            'Comprometido', 'Devengado', 'Pagado', '% Ejercido',
        ];
    }

    public function title(): string
    {
        return 'Capítulos';
    }
}
