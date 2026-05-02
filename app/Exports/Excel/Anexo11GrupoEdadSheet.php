<?php

namespace App\Exports\Excel;

use App\Services\Evaluation\Anexo11ReportData;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class Anexo11GrupoEdadSheet implements FromArray, WithHeadings, WithTitle
{
    private const LABELS = [
        'infantes' => 'Infantes (0-5)',
        'ninios' => 'Niños (6-12)',
        'adolescentes' => 'Adolescentes (13-17)',
        'jovenes' => 'Jóvenes (18-29)',
        'adultos' => 'Adultos (30-64)',
        'adultos_mayores' => 'Adultos mayores (65+)',
    ];

    public function __construct(private readonly Anexo11ReportData $data) {}

    public function title(): string
    {
        return 'Grupo de edad';
    }

    public function headings(): array
    {
        return ['Grupo de edad', 'Beneficiarios'];
    }

    public function array(): array
    {
        $rows = [];
        foreach (self::LABELS as $key => $label) {
            $rows[] = [$label, $this->data->porGrupoEdad[$key] ?? '<5'];
        }

        return $rows;
    }
}
