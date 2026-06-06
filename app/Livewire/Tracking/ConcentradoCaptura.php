<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Exports\Excel\ConcentradoCapturaExcelExport;
use App\Exports\Pdf\ConcentradoCapturaPdfExport;
use App\Livewire\Concerns\HasTraceableTable;
use App\Livewire\Concerns\HasTrackingFilters;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Support\Tracking\TrackingOptions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class ConcentradoCaptura extends Component
{
    use HasTraceableTable;
    use HasTrackingFilters;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ver_concentrado_captura'), 403);

        if ($this->sortBy === '') {
            $this->sortBy = 'indicador';
        }
    }

    public function render()
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('admin');

        $programasQuery = $isAdmin
            ? ProgramaPresupuestario::query()
            : ProgramaPresupuestario::paraTeam($user->currentTeam->id);

        $programas = $programasQuery->orderBy('nombre')->get();

        $query = Avance::query()
            ->with(['indicador.mirNivel.programa', 'indicador.mirNivel.componente'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin, $user) {
                if (! $isAdmin) {
                    $q->where('team_id', $user->currentTeam->id);
                }
            });

        if ($this->filtroPrograma) {
            $query->whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma));
        }

        if ($this->filtroMirNivel) {
            $query->whereHas('indicador', fn ($q) => $q->where('mir_nivel_id', $this->filtroMirNivel));
        }

        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        }

        if ($this->alcanceTemporal === 'anio' && $this->filtroEjercicio) {
            $query->whereYear('updated_at', $this->filtroEjercicio);
        }

        if ($this->alcanceTemporal === 'rango') {
            if ($this->filtroFechaDesde) {
                $query->whereDate('updated_at', '>=', $this->filtroFechaDesde);
            }
            if ($this->filtroFechaHasta) {
                $query->whereDate('updated_at', '<=', $this->filtroFechaHasta);
            }
        }

        if ($this->search !== '') {
            $needle = '%'.$this->search.'%';
            $query->whereHas('indicador', fn ($q) => $q->where('nombre', 'ilike', $needle));
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
                    'trazabilidad' => $avance->indicador->trazabilidad(),
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

        $rows = $this->paginarColeccion($agrupado);

        $columns = $this->columnas();

        $kpis = [
            ['label' => 'Total', 'value' => $metricas['total'], 'color' => 'slate'],
            ['label' => 'Aprobados', 'value' => $metricas['aprobados'], 'color' => 'green'],
            ['label' => 'En revisión', 'value' => $metricas['en_revision'], 'color' => 'blue'],
            ['label' => 'En captura', 'value' => $metricas['en_captura'], 'color' => 'yellow'],
            ['label' => 'Observados', 'value' => $metricas['observados'], 'color' => 'orange'],
        ];

        $programasOpciones = TrackingOptions::programas($programas);

        $nivelesRaw = MirNivel::query()
            ->whereHas('indicadores.avances')
            ->when($this->filtroPrograma, fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma))
            ->whereIn('programa_presupuestario_id', $programas->pluck('id'))
            ->with('programa', 'componente')
            ->get();

        $nivelesOpciones = TrackingOptions::niveles($this->ordenarJerarquicamente($nivelesRaw));

        $estadosOpciones = TrackingOptions::estados();

        return view('livewire.tracking.concentrado-captura', [
            'metricas' => $metricas,
            'agrupado' => $agrupado,
            'rows' => $rows,
            'columns' => $columns,
            'kpis' => $kpis,
            'programasOpciones' => $programasOpciones,
            'nivelesOpciones' => $nivelesOpciones,
            'estadosOpciones' => $estadosOpciones,
        ]);
    }

    protected function paginarColeccion(Collection $filas): LengthAwarePaginator
    {
        $perPage = $this->effectivePerPage();
        $page = max(1, (int) $this->getPage());
        $total = $filas->count();
        $items = $filas->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    protected function columnas(): array
    {
        return [
            [
                'key' => 'indicador',
                'label' => 'Indicador',
            ],
            [
                'key' => 'total',
                'label' => 'Total',
                'align' => 'center',
                'render' => fn ($row) => '<span class="font-semibold text-slate-700 dark:text-slate-200">'.e((string) $row['total']).'</span>',
            ],
            [
                'key' => 'aprobados',
                'label' => 'Aprobados',
                'align' => 'center',
                'render' => fn ($row) => '<span class="text-green-700 dark:text-green-400">'.e((string) $row['aprobados']).'</span>',
            ],
            [
                'key' => 'en_revision',
                'label' => 'En revisión',
                'align' => 'center',
                'render' => fn ($row) => '<span class="text-blue-700 dark:text-blue-400">'.e((string) $row['en_revision']).'</span>',
            ],
            [
                'key' => 'en_captura',
                'label' => 'En captura',
                'align' => 'center',
                'render' => fn ($row) => '<span class="text-yellow-700 dark:text-yellow-400">'.e((string) $row['en_captura']).'</span>',
            ],
            [
                'key' => 'observados',
                'label' => 'Observados',
                'align' => 'center',
                'render' => fn ($row) => '<span class="text-orange-700 dark:text-orange-400">'.e((string) $row['observados']).'</span>',
            ],
        ];
    }

    public function exportarPdf(): StreamedResponse
    {
        $export = new ConcentradoCapturaPdfExport(
            auth()->user(),
            $this->filtroFechaDesde ?? '',
            $this->filtroFechaHasta ?? '',
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
            $this->filtroFechaDesde ?? '',
            $this->filtroFechaHasta ?? '',
        );

        return Excel::download($export, 'concentrado-captura-'.now()->format('Ymd-His').'.xlsx');
    }
}
