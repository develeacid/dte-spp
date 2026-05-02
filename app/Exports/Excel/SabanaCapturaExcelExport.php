<?php

namespace App\Exports\Excel;

use App\Models\Mml\MetaPeriodo;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SabanaCapturaExcelExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        private User $user,
        private ?int $filtroPrograma = null,
        private ?int $filtroTrimestre = null,
        private ?string $filtroEstado = null,
    ) {}

    public function collection(): Collection
    {
        $isAdmin = $this->user->hasRole('admin');

        $metasQuery = MetaPeriodo::query()
            ->with(['indicador.mirNivel.programa', 'avance.capturador'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin) {
                if (! $isAdmin) {
                    $q->where('team_id', $this->user->currentTeam->id);
                }
            })
            ->where('activo', true);

        if ($this->filtroPrograma) {
            $metasQuery->whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma));
        }

        if ($this->filtroTrimestre) {
            $metasQuery->where('periodo', $this->filtroTrimestre);
        }

        $metas = $metasQuery->orderBy('fecha_cierre')->get();

        $rows = collect();

        foreach ($metas as $meta) {
            $avance = $meta->avance;
            $estado = match (true) {
                $avance !== null => $avance->estado->value,
                $meta->fecha_cierre < now() => 'vencido',
                default => 'pendiente',
            };

            if ($this->filtroEstado && $estado !== $this->filtroEstado) {
                continue;
            }

            $estadoLabel = match ($estado) {
                'pendiente' => 'Pendiente',
                'en_captura' => 'En captura',
                'en_revision' => 'En revision',
                'aprobado' => 'Aprobado',
                'observado' => 'Observado',
                'vencido' => 'Vencido',
                default => $estado,
            };

            $rows->push([
                'programa' => $meta->indicador->mirNivel->programa->clave ?? "\u{2014}",
                'indicador' => $meta->indicador->nombre,
                'trimestre' => 'T'.$meta->periodo,
                'meta' => $meta->meta_periodo,
                'estado' => $estadoLabel,
                'operador' => $avance?->capturador?->name ?? "\u{2014}",
                'fecha_cierre' => $meta->fecha_cierre->format('d/m/Y'),
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Programa', 'Indicador', 'Trimestre', 'Meta',
            'Estado', 'Operador', 'Fecha Cierre',
        ];
    }

    public function title(): string
    {
        return 'Sabana de Captura';
    }
}
