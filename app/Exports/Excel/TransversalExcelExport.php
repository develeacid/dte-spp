<?php

namespace App\Exports\Excel;

use App\Models\Evaluation\AnexoTransversal;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TransversalExcelExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        private string $tipo,
        private int $ejercicioFiscal,
    ) {}

    public function collection(): Collection
    {
        $anexos = AnexoTransversal::activos()
            ->with([
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.mirNivel.programa',
            ])
            ->get();

        $rows = collect();

        foreach ($anexos as $anexo) {
            foreach ($anexo->indicadores as $indicador) {
                $rows->push([
                    'anexo' => $anexo->nombre,
                    'clave_anexo' => $anexo->clave,
                    'programa' => $indicador->mirNivel?->programa?->clave,
                    'nivel' => $indicador->mirNivel?->tipo_nivel?->label(),
                    'indicador' => $indicador->nombre,
                    'meta' => $indicador->meta,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Anexo Transversal', 'Clave', 'Programa', 'Nivel', 'Indicador', 'Meta'];
    }

    public function title(): string
    {
        return 'Transversal';
    }
}
