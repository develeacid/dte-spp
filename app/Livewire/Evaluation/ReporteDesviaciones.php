<?php

namespace App\Livewire\Evaluation;

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

#[Layout('layouts.app')]
class ReporteDesviaciones extends Component
{
    use HasTraceableTable;
    use HasTrackingFilters;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('exportar_reportes'), 403);

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
            ->with([
                'indicador.mirNivel.programa',
                'indicador.mirNivel.componente',
                'metaPeriodo',
                'capturador',
            ])
            ->where(function ($q) {
                $q->whereNotNull('justificacion_final')
                    ->orWhereNotNull('justificacion_ia');
            })
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

        if ($this->filtroTrimestre) {
            $query->whereHas('metaPeriodo', fn ($q) => $q->where('periodo', $this->filtroTrimestre));
        }

        if ($this->alcanceTemporal === 'anio' && $this->filtroEjercicio) {
            $query->whereHas('metaPeriodo', fn ($q) => $q->where('ejercicio_fiscal', $this->filtroEjercicio));
        }

        if ($this->alcanceTemporal === 'rango') {
            if ($this->filtroFechaDesde) {
                $query->whereHas('metaPeriodo', fn ($q) => $q->whereDate('fecha_cierre', '>=', $this->filtroFechaDesde));
            }
            if ($this->filtroFechaHasta) {
                $query->whereHas('metaPeriodo', fn ($q) => $q->whereDate('fecha_cierre', '<=', $this->filtroFechaHasta));
            }
        }

        if ($this->search !== '') {
            $needle = '%'.$this->search.'%';
            $query->whereHas('indicador', fn ($q) => $q->where('nombre', 'ilike', $needle));
        }

        $avances = $query->get();

        $filas = $avances->map(function (Avance $avance) {
            $indicador = $avance->indicador;
            $meta = $avance->metaPeriodo;
            $justFinal = $avance->justificacion_final;
            $justIa = $avance->justificacion_ia;
            $justTexto = $justFinal ?? $justIa ?? '';
            $tipo = match (true) {
                $justFinal !== null && $justIa !== null => 'ambas',
                $justFinal !== null => 'final',
                $justIa !== null => 'ia',
                default => 'ninguna',
            };

            return [
                'programa_clave' => $indicador->mirNivel->programa->clave ?? "\u{2014}",
                'programa_nombre' => $indicador->mirNivel->programa->nombre ?? "\u{2014}",
                'indicador' => $indicador->nombre,
                'periodo' => $meta?->periodo,
                'meta_periodo' => $meta?->meta_periodo,
                'resultado' => $avance->resultado,
                'justificacion_corta' => mb_strlen($justTexto) > 100 ? mb_substr($justTexto, 0, 100).'…' : $justTexto,
                'justificacion_completa' => $justTexto,
                'tipo_justificacion' => $tipo,
                'estado' => $avance->estado?->value,
                'capturador' => $avance->capturador?->name ?? "\u{2014}",
                'fecha_captura' => $avance->updated_at?->format('d/m/Y'),
                'trazabilidad' => $indicador->trazabilidad(),
            ];
        });

        $filas = $this->ordenarFilas($filas);

        $rows = $this->paginarColeccion($filas);

        $columns = $this->columnas();

        $programasOpciones = TrackingOptions::programas($programas);

        $nivelesRaw = MirNivel::query()
            ->whereHas('indicadores.avances')
            ->when($this->filtroPrograma, fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma))
            ->whereIn('programa_presupuestario_id', $programas->pluck('id'))
            ->with('programa', 'componente')
            ->get();

        $nivelesOpciones = TrackingOptions::niveles($this->ordenarJerarquicamente($nivelesRaw));

        $estadosOpciones = TrackingOptions::estados();

        $total = $filas->count();
        $conFinal = $filas->whereIn('tipo_justificacion', ['final', 'ambas'])->count();
        $soloIa = $filas->where('tipo_justificacion', 'ia')->count();
        $pendientes = $filas->whereIn('estado', ['en_captura', 'en_revision', 'observado'])->count();

        $kpis = [
            ['label' => 'Total desviaciones', 'value' => $total, 'color' => 'slate'],
            ['label' => 'Con justificación final', 'value' => $conFinal, 'color' => 'green'],
            ['label' => 'Solo IA', 'value' => $soloIa, 'color' => 'amber'],
            ['label' => 'Pendientes validar', 'value' => $pendientes, 'color' => 'red'],
        ];

        return view('livewire.evaluation.reporte-desviaciones', [
            'programas' => $programas,
            'programasOpciones' => $programasOpciones,
            'nivelesOpciones' => $nivelesOpciones,
            'estadosOpciones' => $estadosOpciones,
            'filas' => $filas,
            'rows' => $rows,
            'columns' => $columns,
            'kpis' => $kpis,
        ]);
    }

    protected function ordenarFilas(Collection $filas): Collection
    {
        if ($this->groupByPrograma) {
            return $filas->sortBy([
                ['programa_clave', 'asc'],
                ['indicador', 'asc'],
            ])->values();
        }

        if ($this->sortBy === 'fecha') {
            return $filas->sortBy('fecha_captura', SORT_REGULAR, $this->sortDir === 'desc')->values();
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
                'render' => fn ($row) => $row['periodo'] ? 'T'.e((string) $row['periodo']) : "\u{2014}",
            ],
            [
                'key' => 'meta_resultado',
                'label' => 'Meta / Resultado',
                'align' => 'center',
                'render' => function ($row) {
                    $meta = $row['meta_periodo'] !== null ? rtrim(rtrim(number_format((float) $row['meta_periodo'], 4, '.', ''), '0'), '.') : "\u{2014}";
                    $res = $row['resultado'] !== null ? rtrim(rtrim(number_format((float) $row['resultado'], 4, '.', ''), '0'), '.') : "\u{2014}";

                    return e($meta).' / '.e($res);
                },
            ],
            [
                'key' => 'justificacion_corta',
                'label' => 'Justificación',
                'render' => function ($row) {
                    $full = $row['justificacion_completa'] ?? '';
                    $short = $row['justificacion_corta'] ?? '';
                    $tipo = $row['tipo_justificacion'] ?? 'ninguna';
                    $badge = match ($tipo) {
                        'final' => '<span class="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">final</span>',
                        'ia' => '<span class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">IA</span>',
                        'ambas' => '<span class="ml-1 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">ambas</span>',
                        default => '',
                    };

                    return '<span title="'.e($full).'">'.e($short).'</span>'.$badge;
                },
            ],
            [
                'key' => 'estado',
                'label' => 'Estado',
                'align' => 'center',
                'render' => fn ($row) => $this->badgeEstado($row['estado'] ?? ''),
            ],
            [
                'key' => 'capturador',
                'label' => 'Capturador',
            ],
            [
                'key' => 'fecha_captura',
                'label' => 'Captura',
                'align' => 'center',
            ],
        ];
    }

    protected function badgeEstado(string $estado): string
    {
        $clases = match ($estado) {
            'en_captura' => 'bg-blue-100 text-blue-800',
            'en_revision' => 'bg-yellow-100 text-yellow-800',
            'aprobado' => 'bg-green-100 text-green-800',
            'observado' => 'bg-orange-100 text-orange-800',
            'vencido' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };

        $etiqueta = match ($estado) {
            'en_captura' => 'En captura',
            'en_revision' => 'En revisión',
            'aprobado' => 'Aprobado',
            'observado' => 'Observado',
            'vencido' => 'Vencido',
            default => $estado === '' ? "\u{2014}" : $estado,
        };

        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '
            .$clases.'">'.e($etiqueta).'</span>';
    }
}
