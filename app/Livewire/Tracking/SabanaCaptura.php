<?php

namespace App\Livewire\Tracking;

use App\Exports\Excel\SabanaCapturaExcelExport;
use App\Exports\Pdf\SabanaCapturaPdfExport;
use App\Livewire\Concerns\HasTraceableTable;
use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class SabanaCaptura extends Component
{
    use HasTraceableTable;

    #[Url(as: 'programa')]
    public ?int $filtroPrograma = null;

    #[Url(as: 'nivel')]
    public ?int $filtroMirNivel = null;

    #[Url(as: 'trimestre')]
    public ?int $filtroTrimestre = null;

    #[Url(as: 'estado')]
    public ?string $filtroEstado = null;

    #[Url(as: 'alcance')]
    public string $alcanceTemporal = 'todo';

    #[Url(as: 'ejercicio')]
    public ?int $filtroEjercicio = null;

    #[Url(as: 'desde')]
    public ?string $filtroFechaDesde = null;

    #[Url(as: 'hasta')]
    public ?string $filtroFechaHasta = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'dashboard';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ver_sabana_captura'), 403);

        if ($this->sortBy === '') {
            $this->sortBy = 'indicador';
        }
    }

    public function updatingFiltroPrograma(): void
    {
        // Si cambia el programa, el nivel seleccionado puede no pertenecer al nuevo programa.
        $this->filtroMirNivel = null;
        $this->resetPage();
    }

    public function updatingFiltroMirNivel(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroTrimestre(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatedAlcanceTemporal(string $value): void
    {
        if ($value === 'todo') {
            $this->filtroEjercicio = null;
            $this->filtroFechaDesde = null;
            $this->filtroFechaHasta = null;
        } elseif ($value === 'anio') {
            $this->filtroFechaDesde = null;
            $this->filtroFechaHasta = null;
        } else {
            $this->filtroEjercicio = null;
            $this->filtroTrimestre = null;
        }
        $this->resetPage();
    }

    public function updatingFiltroEjercicio(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroFechaHasta(): void
    {
        $this->resetPage();
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
            ->with(['indicador.mirNivel.programa.team', 'indicador.mirNivel.componente', 'avance.capturador'])
            ->whereHas('indicador.mirNivel.programa', function ($q) use ($isAdmin, $user) {
                if (! $isAdmin) {
                    $q->where('team_id', $user->currentTeam->id);
                }
            })
            ->where('activo', true);

        if ($this->filtroPrograma) {
            $metasQuery->whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma));
        }

        if ($this->filtroMirNivel) {
            $metasQuery->whereHas('indicador', fn ($q) => $q->where('mir_nivel_id', $this->filtroMirNivel));
        }

        if ($this->filtroTrimestre) {
            $metasQuery->where('periodo', $this->filtroTrimestre);
        }

        if ($this->alcanceTemporal === 'anio' && $this->filtroEjercicio) {
            $metasQuery->whereYear('fecha_cierre', $this->filtroEjercicio);
        }

        if ($this->alcanceTemporal === 'rango') {
            if ($this->filtroFechaDesde) {
                $metasQuery->whereDate('fecha_cierre', '>=', $this->filtroFechaDesde);
            }
            if ($this->filtroFechaHasta) {
                $metasQuery->whereDate('fecha_cierre', '<=', $this->filtroFechaHasta);
            }
        }

        if ($this->search !== '') {
            $needle = '%'.$this->search.'%';
            $metasQuery->whereHas('indicador', fn ($q) => $q->where('nombre', 'ilike', $needle));
        }

        $metasQuery->orderBy('fecha_cierre');

        $metas = $metasQuery->get();

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
            $indicador = $meta->indicador;

            return [
                'programa_clave' => $indicador->mirNivel->programa->clave ?? "\u{2014}",
                'programa_nombre' => $indicador->mirNivel->programa->nombre ?? "\u{2014}",
                'indicador' => $indicador->nombre,
                'periodo' => $meta->periodo,
                'meta_periodo' => $meta->meta_periodo,
                'estado' => $estado,
                'operador' => $avance?->capturador?->name ?? "\u{2014}",
                'dias' => (int) $diasRestantes,
                'fecha_cierre' => $meta->fecha_cierre->format('d/m/Y'),
                'semaforo' => $avance?->semaforo_calculado,
                'trazabilidad' => $indicador->trazabilidad(),
            ];
        })->filter()->values();

        $filas = $this->ordenarFilas($filas);

        $rows = $this->paginarColeccion($filas);

        $columns = $this->columnas();

        $rowClass = fn ($row) => match ($row['estado'] ?? null) {
            'vencido' => 'bg-red-50 dark:bg-red-900/20',
            'aprobado' => 'bg-green-50 dark:bg-green-900/20',
            default => '',
        };

        $programasOpciones = $programas->mapWithKeys(fn ($p) => [$p->id => $p->clave.' - '.$p->nombre])->toArray();

        $nivelesOpciones = \App\Models\Mml\MirNivel::query()
            ->whereHas('indicadores.metasPeriodo')
            ->when($this->filtroPrograma, fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma))
            ->whereIn('programa_presupuestario_id', $programas->pluck('id'))
            ->with('programa', 'componente')
            ->orderBy('programa_presupuestario_id')
            ->orderBy('tipo_nivel')
            ->orderBy('orden')
            ->get()
            ->mapWithKeys(fn ($n) => [$n->id => $n->trazabilidad()->clave().' · '.$n->trazabilidad()->nivel()])
            ->toArray();

        $estadosOpciones = [
            'pendiente' => 'Pendiente',
            'en_captura' => 'En captura',
            'en_revision' => 'En revisión',
            'aprobado' => 'Aprobado',
            'observado' => 'Observado',
            'vencido' => 'Vencido',
        ];

        return view('livewire.tracking.sabana-captura', [
            'programas' => $programas,
            'programasOpciones' => $programasOpciones,
            'nivelesOpciones' => $nivelesOpciones,
            'estadosOpciones' => $estadosOpciones,
            'filas' => $filas,
            'rows' => $rows,
            'columns' => $columns,
            'rowClass' => $rowClass,
        ]);
    }

    protected function ordenarFilas(Collection $filas): Collection
    {
        if ($this->groupByPrograma) {
            return $filas->sortBy([
                ['programa_clave', 'asc'],
                ['fecha_cierre', $this->sortDir],
            ])->values();
        }

        if ($this->sortBy === 'fecha') {
            return $filas->sortBy('fecha_cierre', SORT_REGULAR, $this->sortDir === 'desc')->values();
        }

        return $filas->sortBy('indicador', SORT_NATURAL | SORT_FLAG_CASE, $this->sortDir === 'desc')->values();
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
                'key' => 'periodo',
                'label' => 'T',
                'align' => 'center',
                'render' => fn ($row) => 'T'.e($row['periodo']),
            ],
            [
                'key' => 'meta_periodo',
                'label' => 'Meta',
                'align' => 'center',
            ],
            [
                'key' => 'estado',
                'label' => 'Estado',
                'align' => 'center',
                'render' => fn ($row) => $this->badgeEstado($row['estado']),
            ],
            [
                'key' => 'operador',
                'label' => 'Operador',
            ],
            [
                'key' => 'fecha_cierre',
                'label' => 'Cierre',
                'align' => 'center',
            ],
            [
                'key' => 'dias',
                'label' => 'Días para cierre',
                'align' => 'center',
                'render' => fn ($row) => $this->celdaDias($row['dias']),
            ],
        ];
    }

    protected function badgeEstado(string $estado): string
    {
        $clases = match ($estado) {
            'pendiente' => 'bg-gray-100 text-gray-800',
            'en_captura' => 'bg-blue-100 text-blue-800',
            'en_revision' => 'bg-yellow-100 text-yellow-800',
            'aprobado' => 'bg-green-100 text-green-800',
            'observado' => 'bg-orange-100 text-orange-800',
            'vencido' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };

        $etiqueta = match ($estado) {
            'pendiente' => 'Pendiente',
            'en_captura' => 'En captura',
            'en_revision' => 'En revisión',
            'aprobado' => 'Aprobado',
            'observado' => 'Observado',
            'vencido' => 'Vencido',
            default => $estado,
        };

        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '
            .$clases.'">'.e($etiqueta).'</span>';
    }

    protected function celdaDias(int $dias): string
    {
        $clase = match (true) {
            $dias < 0 => 'text-red-600 font-bold',
            $dias <= 7 => 'text-orange-600 font-semibold',
            default => 'text-gray-700',
        };

        $tooltip = match (true) {
            $dias < 0 => 'Vencido hace '.abs($dias).' '.(abs($dias) === 1 ? 'día' : 'días'),
            $dias === 0 => 'Cierra hoy',
            $dias === 1 => 'Cierra mañana',
            $dias <= 7 => 'Quedan '.$dias.' días para cierre (urgente)',
            default => 'Quedan '.$dias.' días para cierre',
        };

        return '<span class="'.$clase.'" title="'.e($tooltip).'">'.e((string) $dias).'</span>';
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
