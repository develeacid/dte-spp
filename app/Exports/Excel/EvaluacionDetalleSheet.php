<?php

namespace App\Exports\Excel;

use App\Models\Evaluation\EvaluacionPrograma;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EvaluacionDetalleSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private EvaluacionPrograma $evaluacion,
    ) {}

    public function collection(): Collection
    {
        $this->evaluacion->load('programa.mirNiveles.indicadores');
        $programa = $this->evaluacion->programa;

        $rows = collect();

        foreach ($programa->mirNiveles as $nivel) {
            foreach ($nivel->indicadores as $indicador) {
                $rows->push([
                    'nivel' => $nivel->tipo_nivel->label(),
                    'resumen_narrativo' => $nivel->resumen_narrativo,
                    'indicador' => $indicador->nombre,
                    'meta' => $indicador->meta,
                    'tipo' => $indicador->tipo?->value,
                    'frecuencia' => $indicador->frecuencia?->value,
                    'activo_seguimiento' => $indicador->activo_seguimiento ? 'Sí' : 'No',
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Nivel', 'Resumen Narrativo', 'Indicador', 'Meta', 'Tipo', 'Frecuencia', 'Activo Seguimiento'];
    }

    public function title(): string
    {
        return 'Detalle';
    }
}
