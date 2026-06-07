<?php

namespace App\Livewire\Tracking;

use App\Livewire\Concerns\HasTraceableTable;
use App\Livewire\Concerns\HasTrackingFilters;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Support\Tracking\TrackingOptions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class PanelSeguimiento extends Component
{
    use HasTraceableTable;
    use HasTrackingFilters;

    #[Url(as: 'semaforo')]
    public ?string $filtroSemaforo = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('revisar_avance'), 403);

        if ($this->sortBy === '') {
            $this->sortBy = 'indicador';
        }
    }

    public function updatingFiltroSemaforo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $teamId = auth()->user()->currentTeam->id;

        $programas = ProgramaPresupuestario::paraTeam($teamId)
            ->orderBy('nombre')
            ->get();

        $metasPeriodoConstraint = function ($q) {
            $q->orderByDesc('periodo');

            if ($this->filtroTrimestre) {
                $q->where('periodo', $this->filtroTrimestre);
            }
            if ($this->alcanceTemporal === 'anio' && $this->filtroEjercicio) {
                $q->whereYear('fecha_cierre', $this->filtroEjercicio);
            }
            if ($this->alcanceTemporal === 'rango') {
                if ($this->filtroFechaDesde) {
                    $q->whereDate('fecha_cierre', '>=', $this->filtroFechaDesde);
                }
                if ($this->filtroFechaHasta) {
                    $q->whereDate('fecha_cierre', '<=', $this->filtroFechaHasta);
                }
            }
        };

        $nivelesQuery = MirNivel::query()
            ->where(function ($q) use ($teamId) {
                $q->whereHas('programa', fn ($p) => $p->where('team_id', $teamId))
                    ->orWhere('team_id', $teamId);
            })
            ->with([
                'programa:id,nombre,clave',
                'componente:id,orden',
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.metasPeriodo' => $metasPeriodoConstraint,
                'indicadores.metasPeriodo.avance.variables.indicadorVariable',
                'indicadores.metasPeriodo.avance.evidencias',
                'indicadores.variables',
                'indicadores.anexosTransversales',
            ]);

        if ($this->filtroPrograma) {
            $nivelesQuery->where('programa_presupuestario_id', $this->filtroPrograma);
        }

        if ($this->filtroMirNivel) {
            $nivelesQuery->where('id', $this->filtroMirNivel);
        }

        $niveles = $nivelesQuery->orderBy('tipo_nivel')->orderBy('orden')->get();

        $filas = collect();

        foreach ($niveles as $nivel) {
            foreach ($nivel->indicadores as $indicador) {
                $ultimaMetaConAvance = $indicador->metasPeriodo
                    ->first(fn ($mp) => $mp->avance !== null);

                $avance = $ultimaMetaConAvance?->avance;
                $metaPeriodo = $ultimaMetaConAvance;

                $semaforo = $avance?->semaforo_calculado ?? 'gris';
                $estado = $avance?->estado;

                if ($this->filtroEstado && ($estado?->value ?? null) !== $this->filtroEstado) {
                    continue;
                }
                if ($this->filtroSemaforo && $semaforo !== $this->filtroSemaforo) {
                    continue;
                }

                if ($this->search !== '' && stripos($indicador->nombre, $this->search) === false) {
                    continue;
                }

                $filas->push([
                    'programa_clave' => $nivel->programa?->clave ?? "\u{2014}",
                    'programa_nombre' => $nivel->programa?->nombre ?? "\u{2014}",
                    'nivel_tipo' => $nivel->tipo_nivel,
                    'indicador_id' => $indicador->id,
                    'indicador_nombre' => $indicador->nombre,
                    'meta' => $metaPeriodo?->meta_periodo,
                    'resultado' => $avance?->resultado,
                    'semaforo' => $semaforo,
                    'estado' => $estado,
                    'avance' => $avance,
                    'indicador' => $indicador,
                    'trazabilidad' => $indicador->trazabilidad(),
                ]);
            }
        }

        $filas = $this->ordenarFilas($filas);

        $rows = $this->paginarColeccion($filas);

        $columns = $this->columnas();

        $rowClass = fn ($row) => match ($row['semaforo'] ?? null) {
            'rojo' => 'bg-red-50 dark:bg-red-900/20',
            'rojo_alto' => 'bg-purple-50 dark:bg-purple-900/20',
            'amarillo' => 'bg-yellow-50 dark:bg-yellow-900/20',
            'verde' => 'bg-green-50 dark:bg-green-900/20',
            default => '',
        };

        $programasOpciones = TrackingOptions::programas($programas);

        $nivelesRaw = MirNivel::query()
            ->whereHas('indicadores', fn ($q) => $q->where('activo_seguimiento', true))
            ->when($this->filtroPrograma, fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma))
            ->whereIn('programa_presupuestario_id', $programas->pluck('id'))
            ->with('programa', 'componente')
            ->get();

        $nivelesOpciones = TrackingOptions::niveles($this->ordenarJerarquicamente($nivelesRaw));

        $estadosOpciones = TrackingOptions::estados();
        $semaforosOpciones = TrackingOptions::semaforos();

        $total = $filas->count();
        $verdes = $filas->where('semaforo', 'verde')->count();
        $amarillos = $filas->where('semaforo', 'amarillo')->count();
        $rojos = $filas->where('semaforo', 'rojo')->count();
        $rojosAltos = $filas->where('semaforo', 'rojo_alto')->count();

        $kpis = [
            ['label' => 'Total', 'value' => $total, 'color' => 'slate'],
            ['label' => 'En verde', 'value' => $verdes, 'color' => 'green'],
            ['label' => 'En amarillo', 'value' => $amarillos, 'color' => 'yellow'],
            ['label' => 'En rojo', 'value' => $rojos, 'color' => 'red'],
            ['label' => 'Rojo alto', 'value' => $rojosAltos, 'color' => 'purple'],
        ];

        return view('livewire.tracking.panel-seguimiento', [
            'programas' => $programas,
            'programasOpciones' => $programasOpciones,
            'nivelesOpciones' => $nivelesOpciones,
            'estadosOpciones' => $estadosOpciones,
            'semaforosOpciones' => $semaforosOpciones,
            'filas' => $filas,
            'rows' => $rows,
            'columns' => $columns,
            'rowClass' => $rowClass,
            'kpis' => $kpis,
        ]);
    }

    protected function ordenarFilas(Collection $filas): Collection
    {
        if ($this->groupByPrograma) {
            return $filas->sortBy([
                ['programa_clave', 'asc'],
                ['indicador_nombre', $this->sortDir],
            ])->values();
        }

        return $filas->sortBy('indicador_nombre', SORT_NATURAL | SORT_FLAG_CASE, $this->sortDir === 'desc')->values();
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
                'key' => 'indicador_nombre',
                'label' => 'Indicador',
                'render' => fn ($row) => $this->celdaIndicador($row),
            ],
            [
                'key' => 'nivel_tipo',
                'label' => 'Nivel',
                'render' => fn ($row) => '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.e($row['nivel_tipo']->colorClass()).'">'.e($row['nivel_tipo']->label()).'</span>',
            ],
            [
                'key' => 'meta',
                'label' => 'Meta',
                'align' => 'center',
                'render' => fn ($row) => $row['meta'] !== null ? number_format((float) $row['meta'], 2) : "\u{2014}",
            ],
            [
                'key' => 'resultado',
                'label' => 'Avance',
                'align' => 'center',
                'render' => fn ($row) => $row['resultado'] !== null ? number_format((float) $row['resultado'], 2) : "\u{2014}",
            ],
            [
                'key' => 'semaforo',
                'label' => 'Semáforo',
                'align' => 'center',
                'render' => fn ($row) => $this->celdaSemaforo($row['semaforo']),
            ],
            [
                'key' => 'estado',
                'label' => 'Estado',
                'align' => 'center',
                'render' => fn ($row) => $row['estado']
                    ? '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.e($row['estado']->colorClass()).'">'.e($row['estado']->label()).'</span>'
                    : '<span class="text-xs text-gray-400">Sin avance</span>',
            ],
        ];
    }

    protected function celdaIndicador(array $row): string
    {
        $url = route('tracking.indicador.detalle', $row['indicador_id']);
        $html = '<a href="'.e($url).'" class="text-indigo-600 hover:underline">'.e($row['indicador_nombre']).'</a>';

        $anexos = $row['indicador']->anexosTransversales ?? collect();
        if ($anexos->count() > 0) {
            $pills = $anexos->map(fn ($a) => '<span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">'.e($a->nombre).'</span>')->implode(' ');
            $html .= '<div class="mt-1 flex flex-wrap gap-1">'.$pills.'</div>';
        }

        return $html;
    }

    protected function celdaSemaforo(string $semaforo): string
    {
        $color = match ($semaforo) {
            'verde' => 'bg-green-500',
            'amarillo' => 'bg-yellow-400',
            'rojo' => 'bg-red-500',
            'rojo_alto' => 'bg-purple-500',
            default => 'bg-gray-300',
        };

        $titulo = $semaforo === 'rojo_alto' ? 'Rojo alto' : ucfirst($semaforo);

        return '<span class="inline-block h-4 w-4 rounded-full '.$color.'" title="'.e($titulo).'"></span>';
    }
}
