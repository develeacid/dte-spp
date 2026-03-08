<?php

namespace App\Exports\Excel;

use App\Models\Evaluation\EvaluacionPrograma;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EvaluacionAnualExcelExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private EvaluacionPrograma $evaluacion,
    ) {}

    public function sheets(): array
    {
        return [
            'Resumen' => new EvaluacionResumenSheet($this->evaluacion),
            'Detalle' => new EvaluacionDetalleSheet($this->evaluacion),
        ];
    }
}
