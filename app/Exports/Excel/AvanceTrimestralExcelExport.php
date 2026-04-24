<?php

namespace App\Exports\Excel;

use App\Exports\Excel\Sheets\AvanceFisicoSheet;
use App\Models\ProgramaPresupuestario;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AvanceTrimestralExcelExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
        private int $trimestre,
    ) {}

    public function sheets(): array
    {
        return [
            'Avance Físico' => new AvanceFisicoSheet(
                $this->programa,
                $this->ejercicioFiscal,
                $this->trimestre,
            ),
        ];
    }
}
