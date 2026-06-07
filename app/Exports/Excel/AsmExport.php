<?php

namespace App\Exports\Excel;

use App\Models\Evaluation\Asm;
use App\Services\Evaluation\AsmReportService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AsmExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly AsmReportService $service) {}

    public function collection()
    {
        $rows = $this->service->rows();
        $totals = $this->service->totals($rows);

        // Append totals as a pseudo-record. The map() method below detects this via __totals.
        $rows->push((object) [
            '__totals' => true,
            'totals' => $totals,
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Programa',
            'Descripción del aspecto',
            'Acción de mejora',
            'Tipo de acción',
            'Tipo de plazo',
            'Responsable',
            'Área',
            'Fecha compromiso',
            '% avance',
            'Status',
            'Semáforo',
            'Días restantes',
            'Evidencia',
            'Recomendación de origen',
        ];
    }

    public function map($row): array
    {
        if (isset($row->__totals)) {
            $t = $row->totals;

            return [
                '',
                "TOTALES — Total: {$t['total']} | Cumplidos: {$t['cumplidos']} | En proceso: {$t['en_proceso']} | Pendientes: {$t['pendientes']} | Vencidos: {$t['vencidos']}",
                '', '', '', '', '', '', '', '', '', '', '', '', '',
            ];
        }

        /** @var Asm $asm */
        $asm = $row;
        $dias = (int) now()->startOfDay()->diffInDays($asm->fecha_compromiso->copy()->startOfDay(), false);

        return [
            $asm->id,
            ($asm->programa->clave ?? '').' — '.($asm->programa->nombre ?? ''),
            $asm->descripcion_aspecto,
            $asm->accion_mejora,
            $asm->tipo_accion->label(),
            $asm->tipo_plazo->label(),
            $asm->responsable->name ?? '',
            $asm->area_responsable,
            $asm->fecha_compromiso->format('d/m/Y'),
            $asm->porcentaje_avance,
            $asm->status->label(),
            $asm->semaforo->label(),
            $dias,
            $asm->evidencia_url ?? '',
            $asm->recomendacion ? Str::limit($asm->recomendacion->descripcion, 120) : '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
