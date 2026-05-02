<?php

namespace App\Exports\Excel;

use App\Services\Evaluation\Anexo11ReportData;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class Anexo11PuebloSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly Anexo11ReportData $data) {}

    public function title(): string
    {
        return 'Pueblo';
    }

    public function headings(): array
    {
        return ['Pueblo (clave)', 'Beneficiarios'];
    }

    public function array(): array
    {
        if ($this->data->porPueblo === []) {
            return [['(sin datos)', 0]];
        }
        $rows = [];
        foreach ($this->data->porPueblo as $clave => $count) {
            $rows[] = [$clave, $count];
        }

        return $rows;
    }
}
