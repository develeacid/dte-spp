<?php

namespace App\Exports\Excel;

use App\Exports\Excel\Sheets\PresupuestoCapitulosSheet;
use App\Exports\Excel\Sheets\PresupuestoPartidasSheet;
use App\Services\Presupuesto\PresupuestoCapituloReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PresupuestoCapituloExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private PresupuestoCapituloReportService $service) {}

    public function sheets(): array
    {
        return [
            'Capítulos' => new PresupuestoCapitulosSheet($this->service),
            'Partidas' => new PresupuestoPartidasSheet($this->service),
        ];
    }
}
