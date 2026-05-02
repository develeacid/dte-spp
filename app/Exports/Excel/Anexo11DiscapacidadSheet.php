<?php

namespace App\Exports\Excel;

use App\Services\Evaluation\Anexo11ReportData;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class Anexo11DiscapacidadSheet implements FromArray, WithHeadings, WithTitle
{
    private const LABELS = [
        'motriz' => 'Motriz',
        'visual' => 'Visual',
        'auditiva' => 'Auditiva',
        'intelectual' => 'Intelectual',
        'psicosocial' => 'Psicosocial',
        'multiple' => 'Múltiple',
        'ninguna' => 'Ninguna',
    ];

    public function __construct(private readonly Anexo11ReportData $data) {}

    public function title(): string
    {
        return 'Discapacidad';
    }

    public function headings(): array
    {
        return ['Tipo de discapacidad', 'Beneficiarios'];
    }

    public function array(): array
    {
        $rows = [];
        foreach (self::LABELS as $key => $label) {
            $rows[] = [$label, $this->data->porTipoDiscapacidad[$key] ?? '<5'];
        }

        return $rows;
    }
}
