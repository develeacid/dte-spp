<?php

namespace App\Exports\Excel;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ConcentradoCapturaExcelExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        private User $user,
        private string $fechaDesde = '',
        private string $fechaHasta = '',
    ) {}

    public function collection(): Collection
    {
        $isAdmin = $this->user->hasRole('admin');

        $query = Avance::query()
            ->with(['indicador.mirNivel.programa'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin) {
                if (! $isAdmin) {
                    $q->where('team_id', $this->user->currentTeam->id);
                }
            });

        if ($this->fechaDesde) {
            $query->whereDate('updated_at', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta) {
            $query->whereDate('updated_at', '<=', $this->fechaHasta);
        }

        $avances = $query->get();

        $agrupado = collect();

        foreach ($avances as $avance) {
            $programaClave = $avance->indicador->mirNivel->programa->clave ?? "\u{2014}";
            $indicadorNombre = $avance->indicador->nombre;
            $key = $programaClave.'|'.$indicadorNombre;

            if (! $agrupado->has($key)) {
                $agrupado[$key] = [
                    'programa' => $programaClave,
                    'indicador' => $indicadorNombre,
                    'total' => 0,
                    'aprobados' => 0,
                    'en_revision' => 0,
                    'en_captura' => 0,
                    'observados' => 0,
                ];
            }

            $item = $agrupado[$key];
            $item['total']++;

            match ($avance->estado) {
                EstadoAvance::APROBADO => $item['aprobados']++,
                EstadoAvance::EN_REVISION => $item['en_revision']++,
                EstadoAvance::EN_CAPTURA => $item['en_captura']++,
                EstadoAvance::OBSERVADO => $item['observados']++,
                default => null,
            };

            $agrupado[$key] = $item;
        }

        return $agrupado->sortBy([
            ['programa', 'asc'],
            ['indicador', 'asc'],
        ])->values();
    }

    public function headings(): array
    {
        return [
            'Programa', 'Indicador', 'Total',
            'Aprobados', 'En Revision', 'En Captura', 'Observados',
        ];
    }

    public function title(): string
    {
        return 'Concentrado';
    }
}
