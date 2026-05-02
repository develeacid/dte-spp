<?php

namespace App\Livewire\Tracking;

use App\Exports\Excel\SabanaCapturaExcelExport;
use App\Exports\Pdf\SabanaCapturaPdfExport;
use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class SabanaCaptura extends Component
{
    public ?int $filtroPrograma = null;

    public ?int $filtroTrimestre = null;

    public ?string $filtroEstado = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ver_sabana_captura'), 403);
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        $programasQuery = $isAdmin
            ? ProgramaPresupuestario::query()
            : ProgramaPresupuestario::paraTeam($user->currentTeam->id);

        $programas = $programasQuery->orderBy('nombre')->get();

        $metasQuery = MetaPeriodo::query()
            ->with(['indicador.mirNivel.programa.team', 'avance.capturador'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin, $user) {
                if (! $isAdmin) {
                    $q->where('team_id', $user->currentTeam->id);
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

            $diasRestantes = now()->diffInDays($meta->fecha_cierre, false);

            return [
                'programa_clave' => $meta->indicador->mirNivel->programa->clave ?? "\u{2014}",
                'programa_nombre' => $meta->indicador->mirNivel->programa->nombre ?? "\u{2014}",
                'indicador' => $meta->indicador->nombre,
                'periodo' => $meta->periodo,
                'meta_periodo' => $meta->meta_periodo,
                'estado' => $estado,
                'operador' => $avance?->capturador?->name ?? "\u{2014}",
                'dias' => (int) $diasRestantes,
                'fecha_cierre' => $meta->fecha_cierre->format('d/m/Y'),
                'semaforo' => $avance?->semaforo_calculado,
            ];
        })->filter()->values();

        return view('livewire.tracking.sabana-captura', [
            'programas' => $programas,
            'filas' => $filas,
        ]);
    }

    public function exportarPdf(): StreamedResponse
    {
        $export = new SabanaCapturaPdfExport(
            auth()->user(),
            $this->filtroPrograma,
            $this->filtroTrimestre,
            $this->filtroEstado,
        );

        $contenido = $export->generate();
        $filename = 'sabana-captura-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(fn () => print ($contenido), $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportarExcel(): BinaryFileResponse
    {
        $export = new SabanaCapturaExcelExport(
            auth()->user(),
            $this->filtroPrograma,
            $this->filtroTrimestre,
            $this->filtroEstado,
        );

        return Excel::download($export, 'sabana-captura-'.now()->format('Ymd-His').'.xlsx');
    }
}
