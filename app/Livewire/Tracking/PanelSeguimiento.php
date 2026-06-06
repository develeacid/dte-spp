<?php

namespace App\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Livewire\Concerns\HasTraceableTable;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class PanelSeguimiento extends Component
{
    use HasTraceableTable;

    #[Url(as: 'programa')]
    public ?int $filtroPrograma = null;

    #[Url(as: 'nivel')]
    public ?int $filtroMirNivel = null;

    #[Url(as: 'estado')]
    public ?string $filtroEstado = null;

    #[Url(as: 'semaforo')]
    public ?string $filtroSemaforo = null;

    #[Url(as: 'trimestre')]
    public ?int $filtroTrimestre = null;

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
        abort_unless(auth()->user()->can('revisar_avance'), 403);

        if ($this->sortBy === '') {
            $this->sortBy = 'indicador';
        }
    }

    public function updatingFiltroPrograma(): void
    {
        $this->filtroMirNivel = null;
        $this->resetPage();
    }

    public function updatingFiltroMirNivel(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroSemaforo(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroTrimestre(): void
    {
        $this->resetPage();
    }

    public function updatedAlcanceTemporal(string $value): void
    {
        if ($value === 'todo') {
            $this->filtroEjercicio = null;
            $this->filtroFechaDesde = null;
            $this->filtroFechaHasta = null;
            $this->filtroTrimestre = null;
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
            'amarillo' => 'bg-yellow-50 dark:bg-yellow-900/20',
            'verde' => 'bg-green-50 dark:bg-green-900/20',
            default => '',
        };

        $programasOpciones = $programas->mapWithKeys(fn ($p) => [$p->id => $p->clave.' - '.$p->nombre])->toArray();

        $nivelesRaw = MirNivel::query()
            ->whereHas('indicadores', fn ($q) => $q->where('activo_seguimiento', true))
            ->when($this->filtroPrograma, fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma))
            ->whereIn('programa_presupuestario_id', $programas->pluck('id'))
            ->with('programa', 'componente')
            ->get();

        $nivelesOpciones = $this->ordenarJerarquicamente($nivelesRaw)
            ->mapWithKeys(fn ($n) => [$n->id => $n->trazabilidad()->clave().' · '.$n->trazabilidad()->nivel()])
            ->toArray();

        $estadosOpciones = collect(EstadoAvance::cases())
            ->mapWithKeys(fn ($e) => [$e->value => $e->label()])->toArray();
        $semaforosOpciones = [
            'verde' => 'Verde',
            'amarillo' => 'Amarillo',
            'rojo' => 'Rojo',
            'gris' => 'Sin dato',
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
        ]);
    }

    /**
     * Ordena niveles MIR en el orden de la ficha:
     * Por programa → Fin → Propósito → C1, C1.A1, C1.A2... → C2, C2.A1... → C3...
     */
    protected function ordenarJerarquicamente(Collection $niveles): Collection
    {
        $resultado = collect();

        foreach ($niveles->groupBy('programa_presupuestario_id') as $delPrograma) {
            $fin = $delPrograma->firstWhere('tipo_nivel', \App\Enums\TipoNivelMir::FIN);
            $proposito = $delPrograma->firstWhere('tipo_nivel', \App\Enums\TipoNivelMir::PROPOSITO);
            $componentes = $delPrograma->where('tipo_nivel', \App\Enums\TipoNivelMir::COMPONENTE)->sortBy('orden');

            if ($fin) {
                $resultado->push($fin);
            }
            if ($proposito) {
                $resultado->push($proposito);
            }

            foreach ($componentes as $componente) {
                $resultado->push($componente);
                $actividades = $delPrograma
                    ->where('tipo_nivel', \App\Enums\TipoNivelMir::ACTIVIDAD)
                    ->where('componente_id', $componente->id)
                    ->sortBy('orden');

                foreach ($actividades as $actividad) {
                    $resultado->push($actividad);
                }
            }
        }

        return $resultado;
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
            default => 'bg-gray-300',
        };

        return '<span class="inline-block h-4 w-4 rounded-full '.$color.'" title="'.e(ucfirst($semaforo)).'"></span>';
    }
}
