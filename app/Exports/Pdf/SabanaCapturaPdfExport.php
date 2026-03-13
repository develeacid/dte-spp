<?php

namespace App\Exports\Pdf;

use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class SabanaCapturaPdfExport
{
    public function __construct(
        private User $user,
        private ?int $filtroPrograma = null,
        private ?int $filtroTrimestre = null,
        private ?string $filtroEstado = null,
    ) {}

    public function generate(): string
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

        $filas = $metas->map(function ($meta) {
            $avance = $meta->avance;
            $estado = match (true) {
                $avance !== null => $avance->estado->value,
                $meta->fecha_cierre < now() => 'vencido',
                default => 'pendiente',
            };

            if ($this->filtroEstado && $estado !== $this->filtroEstado) {
                return null;
            }

            return [
                'programa_clave' => $meta->indicador->mirNivel->programa->clave ?? "\u{2014}",
                'indicador' => $meta->indicador->nombre,
                'periodo' => $meta->periodo,
                'meta_periodo' => $meta->meta_periodo,
                'estado' => $estado,
                'operador' => $avance?->capturador?->name ?? "\u{2014}",
                'fecha_cierre' => $meta->fecha_cierre->format('d/m/Y'),
            ];
        })->filter()->values();

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.sabana-captura', [
            'filas' => $filas,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
            'filtroTrimestre' => $this->filtroTrimestre,
        ]);

        $pdf->setPaper('letter', 'landscape');

        return $pdf->output();
    }
}
