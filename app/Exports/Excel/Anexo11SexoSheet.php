<?php

namespace App\Exports\Excel;

use App\Services\Evaluation\Anexo11ReportData;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class Anexo11SexoSheet implements FromArray, WithHeadings, WithTitle
{
    private const LABELS = [
        'masculino' => 'Masculino',
        'femenino' => 'Femenino',
        'otro' => 'Otro',
    ];

    public function __construct(private readonly Anexo11ReportData $data) {}

    public function title(): string
    {
        return 'Sexo';
    }

    public function headings(): array
    {
        return ['Sexo', 'Beneficiarios'];
    }

    public function array(): array
    {
        $rows = [];
        foreach (self::LABELS as $key => $label) {
            $rows[] = [$label, $this->data->porGenero[$key] ?? '<5'];
        }

        return $rows;
    }
}
