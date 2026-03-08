<?php

namespace App\Exports\Excel;

use App\Models\Evaluation\EvaluacionPrograma;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EvaluacionResumenSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private EvaluacionPrograma $evaluacion,
    ) {}

    public function collection(): Collection
    {
        $desglose = $this->evaluacion->desglose_niveles ?? [];

        return collect(['fin', 'proposito', 'componente', 'actividad'])->map(function ($nivel) use ($desglose) {
            $data = $desglose[$nivel] ?? [];

            return [
                'nivel' => ucfirst($nivel),
                'peso' => $data['peso'] ?? 0,
                'promedio' => $data['promedio'] ?? 'N/A',
                'indicadores_evaluados' => $data['indicadores_evaluados'] ?? 0,
                'indicadores_no_evaluados' => $data['indicadores_no_evaluados'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return ['Nivel', 'Peso', 'Promedio Eficacia', 'Indicadores Evaluados', 'Indicadores No Evaluados'];
    }

    public function title(): string
    {
        return 'Resumen';
    }
}
