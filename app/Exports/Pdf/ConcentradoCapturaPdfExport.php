<?php

namespace App\Exports\Pdf;

use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class ConcentradoCapturaPdfExport
{
    public function __construct(
        private User $user,
        private string $fechaDesde = '',
        private string $fechaHasta = '',
    ) {}

    public function generate(): string
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
            $key = $programaClave . '|' . $indicadorNombre;

            if (! $agrupado->has($key)) {
                $agrupado[$key] = [
                    'programa_clave' => $programaClave,
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

        $agrupado = $agrupado->sortBy([
            ['programa_clave', 'asc'],
            ['indicador', 'asc'],
        ])->values();

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.concentrado-captura', [
            'agrupado' => $agrupado,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
            'fechaDesde' => $this->fechaDesde,
            'fechaHasta' => $this->fechaHasta,
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->output();
    }
}
