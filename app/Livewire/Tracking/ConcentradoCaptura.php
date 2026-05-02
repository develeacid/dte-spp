<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Exports\Excel\ConcentradoCapturaExcelExport;
use App\Exports\Pdf\ConcentradoCapturaPdfExport;
use App\Models\Tracking\Avance;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class ConcentradoCaptura extends Component
{
    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ver_concentrado_captura'), 403);

        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        $query = Avance::query()
            ->with(['indicador.mirNivel.programa'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin, $user) {
                if (! $isAdmin) {
                    $q->where('team_id', $user->currentTeam->id);
                }
            });

        if ($this->fechaDesde) {
            $query->whereDate('updated_at', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta) {
            $query->whereDate('updated_at', '<=', $this->fechaHasta);
        }

        $avances = $query->get();

        // Compute metrics
        $metricas = [
            'total' => $avances->count(),
            'aprobados' => $avances->where('estado', EstadoAvance::APROBADO)->count(),
            'en_revision' => $avances->where('estado', EstadoAvance::EN_REVISION)->count(),
            'en_captura' => $avances->where('estado', EstadoAvance::EN_CAPTURA)->count(),
            'observados' => $avances->where('estado', EstadoAvance::OBSERVADO)->count(),
        ];

        // Group by programa clave → indicador nombre → count per estado
        $agrupado = collect();

        foreach ($avances as $avance) {
            $programaClave = $avance->indicador->mirNivel->programa->clave ?? "\u{2014}";
            $programaNombre = $avance->indicador->mirNivel->programa->nombre ?? "\u{2014}";
            $indicadorNombre = $avance->indicador->nombre;
            $key = $programaClave.'|'.$indicadorNombre;

            if (! $agrupado->has($key)) {
                $agrupado[$key] = [
                    'programa_clave' => $programaClave,
                    'programa_nombre' => $programaNombre,
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

        // Sort by programa_clave then indicador
        $agrupado = $agrupado->sortBy([
            ['programa_clave', 'asc'],
            ['indicador', 'asc'],
        ])->values();

        return view('livewire.tracking.concentrado-captura', [
            'metricas' => $metricas,
            'agrupado' => $agrupado,
        ]);
    }

    public function exportarPdf(): StreamedResponse
    {
        $export = new ConcentradoCapturaPdfExport(
            auth()->user(),
            $this->fechaDesde,
            $this->fechaHasta,
        );

        $contenido = $export->generate();
        $filename = 'concentrado-captura-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(fn () => print ($contenido), $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportarExcel(): BinaryFileResponse
    {
        $export = new ConcentradoCapturaExcelExport(
            auth()->user(),
            $this->fechaDesde,
            $this->fechaHasta,
        );

        return Excel::download($export, 'concentrado-captura-'.now()->format('Ymd-His').'.xlsx');
    }
}
