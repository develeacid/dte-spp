<?php

namespace App\Exports\Excel\Sheets;

use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\AvanceEvidencia;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EvidenciaPadronSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
        private int $trimestre,
    ) {}

    public function collection(): Collection
    {
        $rows = collect();

        $componentes = $this->programa->mirNiveles()
            ->where('tipo_nivel', 'componente')
            ->with(['indicadores'])
            ->orderBy('orden')
            ->get();

        foreach ($componentes as $componente) {
            $indicadorIds = $componente->indicadores->pluck('id')->all();
            if (empty($indicadorIds)) {
                continue;
            }

            $evidencia = AvanceEvidencia::query()
                ->whereNotNull('geobase_snapshot_id')
                ->whereHas('avance.metaPeriodo', fn ($q) => $q
                    ->where('ejercicio_fiscal', $this->ejercicioFiscal)
                    ->where('periodo', $this->trimestre))
                ->whereHas('avance', fn ($q) => $q->whereIn('indicador_id', $indicadorIds))
                ->orderByDesc('id')
                ->first();

            $rows->push([
                'componente' => 'C'.$componente->orden,
                'narrativa' => str($componente->resumen_narrativo)->limit(80),
                'snapshot_id' => $evidencia?->geobase_snapshot_id ?? '—',
                'hash' => $evidencia?->hash_archivo ?? '—',
                'fecha' => optional($evidencia?->fecha_documento)->format('Y-m-d') ?? '—',
                'origen' => $evidencia?->area_generadora ?? '—',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Componente',
            'Resumen narrativo',
            'Snapshot ID (GeoBase)',
            'Hash SHA-256',
            'Fecha de corte',
            'Origen',
        ];
    }

    public function title(): string
    {
        return 'Evidencia de Padrón';
    }
}
