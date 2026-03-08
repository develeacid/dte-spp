<?php

namespace App\Exports\Excel;

use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AvanceTrimestralExcelExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
        private int $trimestre,
    ) {}

    public function collection(): Collection
    {
        $niveles = $this->programa->mirNiveles()
            ->with([
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.metasPeriodo' => fn ($q) => $q->where('ejercicio_fiscal', $this->ejercicioFiscal)
                    ->where('periodo', $this->trimestre),
                'indicadores.avances' => fn ($q) => $q->whereHas('metaPeriodo', fn ($mp) => $mp->where('ejercicio_fiscal', $this->ejercicioFiscal)
                    ->where('periodo', $this->trimestre)),
            ])
            ->orderByRaw("CASE tipo_nivel WHEN 'fin' THEN 1 WHEN 'proposito' THEN 2 WHEN 'componente' THEN 3 WHEN 'actividad' THEN 4 END")
            ->orderBy('orden')
            ->get();

        $rows = collect();

        foreach ($niveles as $nivel) {
            foreach ($nivel->indicadores as $indicador) {
                $meta = $indicador->metasPeriodo->first();
                $avance = $indicador->avances->first();

                $rows->push([
                    'nivel' => $nivel->tipo_nivel->label(),
                    'indicador' => $indicador->nombre,
                    'meta_anual' => $indicador->meta,
                    'meta_trimestral' => $meta?->meta_periodo,
                    'resultado' => $avance?->resultado,
                    'semaforo' => $avance?->semaforo_calculado ?? 'sin dato',
                    'avance_porcentaje' => $meta?->meta_periodo > 0
                        ? round(($avance?->resultado / $meta->meta_periodo) * 100, 2)
                        : null,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Nivel', 'Indicador', 'Meta Anual', 'Meta Trimestral',
            'Resultado', 'Semáforo', 'Avance %',
        ];
    }

    public function title(): string
    {
        return "T{$this->trimestre} - {$this->programa->clave}";
    }
}
