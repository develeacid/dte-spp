<?php

namespace App\Exports\Excel;

use App\Models\ProgramaPresupuestario;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MirExcelExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
    ) {}

    public function sheets(): array
    {
        return [
            'MIR' => new MirSheet($this->programa, $this->ejercicioFiscal),
        ];
    }
}
