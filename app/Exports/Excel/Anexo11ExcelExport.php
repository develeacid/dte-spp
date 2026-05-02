<?php

namespace App\Exports\Excel;

use App\Services\Evaluation\Anexo11ReportData;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Anexo11ExcelExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly Anexo11ReportData $data) {}

    public function sheets(): array
    {
        return [
            'Sexo' => new Anexo11SexoSheet($this->data),
            'Grupo de edad' => new Anexo11GrupoEdadSheet($this->data),
            'Pueblo' => new Anexo11PuebloSheet($this->data),
            'Discapacidad' => new Anexo11DiscapacidadSheet($this->data),
        ];
    }
}
