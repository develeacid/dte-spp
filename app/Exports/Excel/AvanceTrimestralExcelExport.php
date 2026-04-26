<?php

namespace App\Exports\Excel;

use App\Exports\Excel\Sheets\AvanceFinancieroSheet;
use App\Exports\Excel\Sheets\AvanceFisicoSheet;
use App\Exports\Excel\Sheets\EvidenciaPadronSheet;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AvanceTrimestralExcelExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
        private int $trimestre,
        private ?User $user = null,
    ) {}

    public function sheets(): array
    {
        $sheets = [
            'Avance Físico' => new AvanceFisicoSheet(
                $this->programa,
                $this->ejercicioFiscal,
                $this->trimestre,
            ),
        ];

        if ($this->user?->can('ver_datos_financieros')) {
            $sheets['Financiero'] = new AvanceFinancieroSheet(
                $this->programa,
                $this->ejercicioFiscal,
                $this->trimestre,
            );
        }

        if ($this->programa->padron_geobase_activo) {
            $sheets['Evidencia de Padrón'] = new EvidenciaPadronSheet(
                $this->programa,
                $this->ejercicioFiscal,
                $this->trimestre,
            );
        }

        return $sheets;
    }
}
