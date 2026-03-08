<?php

namespace App\Exports\Excel;

use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MirSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
    ) {}

    public function collection(): Collection
    {
        $niveles = $this->programa->mirNiveles()
            ->with(['indicadores.variables', 'indicadores.mediosVerificacion'])
            ->orderByRaw("CASE tipo_nivel WHEN 'fin' THEN 1 WHEN 'proposito' THEN 2 WHEN 'componente' THEN 3 WHEN 'actividad' THEN 4 END")
            ->orderBy('orden')
            ->get();

        $rows = collect();

        foreach ($niveles as $nivel) {
            foreach ($nivel->indicadores as $indicador) {
                $rows->push([
                    'nivel' => $nivel->tipo_nivel->label(),
                    'resumen_narrativo' => $nivel->resumen_narrativo,
                    'indicador' => $indicador->nombre,
                    'formula' => $indicador->formula_texto,
                    'medios_verificacion' => $indicador->mediosVerificacion->pluck('descripcion')->implode('; '),
                    'supuestos' => $nivel->supuestos,
                    'meta' => $indicador->meta,
                    'linea_base' => $indicador->linea_base,
                    'tipo' => $indicador->tipo?->value,
                    'dimension' => $indicador->dimension?->value,
                    'frecuencia' => $indicador->frecuencia?->value,
                ]);
            }

            if ($nivel->indicadores->isEmpty()) {
                $rows->push([
                    'nivel' => $nivel->tipo_nivel->label(),
                    'resumen_narrativo' => $nivel->resumen_narrativo,
                    'indicador' => '',
                    'formula' => '',
                    'medios_verificacion' => '',
                    'supuestos' => $nivel->supuestos,
                    'meta' => '',
                    'linea_base' => '',
                    'tipo' => '',
                    'dimension' => '',
                    'frecuencia' => '',
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Nivel', 'Resumen Narrativo', 'Indicador', 'Fórmula',
            'Medios de Verificación', 'Supuestos', 'Meta', 'Línea Base',
            'Tipo', 'Dimensión', 'Frecuencia',
        ];
    }

    public function title(): string
    {
        return 'MIR';
    }
}
