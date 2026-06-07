<?php

namespace App\Livewire\Evaluation;

use App\Livewire\Concerns\HasTraceableTable;
use App\Livewire\Concerns\HasTrackingFilters;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Support\Tracking\TrackingOptions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AcumuladoAnual extends Component
{
    use HasTraceableTable;
    use HasTrackingFilters;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('exportar_reportes'), 403);

        if ($this->sortBy === '') {
            $this->sortBy = 'indicador';
        }

        // Esta vista presenta el ejercicio completo: forzar alcance=anio con ejercicio actual por defecto
        if ($this->filtroEjercicio === null) {
            $this->alcanceTemporal = 'anio';
            $this->filtroEjercicio = (int) config('app.ejercicio_fiscal', now()->year);
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

        $ejercicio = $this->filtroEjercicio ?? (int) config('app.ejercicio_fiscal', now()->year);

        $query = Indicador::query()
            ->with([
                'mirNivel.programa',
                'mirNivel.componente',
                'avances' => function ($q) use ($ejercicio) {
                    $q->whereHas('metaPeriodo', fn ($mp) => $mp->where('ejercicio_fiscal', $ejercicio))
                        ->with('metaPeriodo');
                },
                'metasPeriodo' => function ($q) use ($ejercicio) {
                    $q->where('ejercicio_fiscal', $ejercicio);
                },
            ])
            ->whereHas('mirNivel.programa', function ($q) use ($isAdmin, $user) {
                if (! $isAdmin) {
                    $q->where('team_id', $user->currentTeam->id);
                }
            });

        if ($this->filtroPrograma) {
            $query->whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma));
        }

        if ($this->filtroMirNivel) {
            $query->where('mir_nivel_id', $this->filtroMirNivel);
        }

        if ($this->search !== '') {
            $query->where('nombre', 'ilike', '%'.$this->search.'%');
        }

        $indicadores = $query->get();

        $filas = $indicadores->map(function (Indicador $ind) {
            $trimestres = [1 => null, 2 => null, 3 => null, 4 => null];
            $resultadoAcumulado = 0.0;
            $algunResultado = false;

            foreach ($ind->avances as $avance) {
                $periodo = $avance->metaPeriodo?->periodo;
                if ($periodo === null) {
                    continue;
                }
                $valor = $avance->resultado !== null ? (float) $avance->resultado : null;
                if ($valor !== null) {
                    $trimestres[$periodo] = ($trimestres[$periodo] ?? 0) + $valor;
                    $resultadoAcumulado += $valor;
                    $algunResultado = true;
                }
            }

            // Meta anual: usar el campo `meta` del indicador si existe; fallback a suma de metas de periodo
            $metaAnual = $ind->meta !== null ? (float) $ind->meta : null;
            if ($metaAnual === null) {
                $sumMetas = 0.0;
                $hayMeta = false;
                foreach ($ind->metasPeriodo as $mp) {
                    if ($mp->meta_periodo !== null) {
                        $sumMetas += (float) $mp->meta_periodo;
                        $hayMeta = true;
                    }
                }
                $metaAnual = $hayMeta ? $sumMetas : null;
            }

            $cumplimientoPct = null;
            if ($metaAnual !== null && $metaAnual > 0 && $algunResultado) {
                $cumplimientoPct = ($resultadoAcumulado / $metaAnual) * 100.0;
            }

            $semaforo = $this->semaforoPorCumplimiento($cumplimientoPct);

            return [
                'programa_clave' => $ind->mirNivel->programa->clave ?? "\u{2014}",
                'programa_nombre' => $ind->mirNivel->programa->nombre ?? "\u{2014}",
                'indicador' => $ind->nombre,
                'meta_anual' => $metaAnual,
                't1' => $trimestres[1],
                't2' => $trimestres[2],
                't3' => $trimestres[3],
                't4' => $trimestres[4],
                'acumulado' => $algunResultado ? $resultadoAcumulado : null,
                'cumplimiento' => $cumplimientoPct,
                'semaforo' => $semaforo,
                'trazabilidad' => $ind->trazabilidad(),
            ];
        });

        $filas = $this->ordenarFilas($filas);

        $rows = $this->paginarColeccion($filas);

        $columns = $this->columnas();

        $programasOpciones = TrackingOptions::programas($programas);

        $nivelesRaw = MirNivel::query()
            ->whereHas('indicadores')
            ->when($this->filtroPrograma, fn ($q) => $q->where('programa_presupuestario_id', $this->filtroPrograma))
            ->whereIn('programa_presupuestario_id', $programas->pluck('id'))
            ->with('programa', 'componente')
            ->get();

        $nivelesOpciones = TrackingOptions::niveles($this->ordenarJerarquicamente($nivelesRaw));

        $estadosOpciones = TrackingOptions::estados();

        $total = $filas->count();
        $verdes = $filas->where('semaforo', 'verde')->count();
        $amarillos = $filas->where('semaforo', 'amarillo')->count();
        $rojos = $filas->where('semaforo', 'rojo')->count();
        $rojosAltos = $filas->where('semaforo', 'rojo_alto')->count();

        $kpis = [
            ['label' => 'Indicadores', 'value' => $total, 'color' => 'slate'],
            ['label' => 'En verde', 'value' => $verdes, 'color' => 'green'],
            ['label' => 'En amarillo', 'value' => $amarillos, 'color' => 'amber'],
            ['label' => 'En rojo', 'value' => $rojos, 'color' => 'red'],
            ['label' => 'Rojo alto', 'value' => $rojosAltos, 'color' => 'purple'],
        ];

        return view('livewire.evaluation.acumulado-anual', [
            'programas' => $programas,
            'programasOpciones' => $programasOpciones,
            'nivelesOpciones' => $nivelesOpciones,
            'estadosOpciones' => $estadosOpciones,
            'filas' => $filas,
            'rows' => $rows,
            'columns' => $columns,
            'kpis' => $kpis,
            'ejercicio' => $ejercicio,
        ]);
    }

    protected function semaforoPorCumplimiento(?float $pct): string
    {
        if ($pct === null) {
            return 'gris';
        }

        $umbral = (float) config('tracking.umbral_sobrecumplimiento', 130);

        // Sobrecumplimiento más allá del umbral = mala planeación (rojo alto).
        if ($pct > $umbral) {
            return 'rojo_alto';
        }
        if ($pct >= 90.0 && $pct <= 110.0) {
            return 'verde';
        }
        if (($pct >= 70.0 && $pct < 90.0) || ($pct > 110.0 && $pct <= $umbral)) {
            return 'amarillo';
        }

        return 'rojo';
    }

    protected function ordenarFilas(Collection $filas): Collection
    {
        if ($this->groupByPrograma) {
            return $filas->sortBy([
                ['programa_clave', 'asc'],
                ['indicador', 'asc'],
            ])->values();
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
                'key' => 'meta_anual',
                'label' => 'Meta anual',
                'align' => 'right',
                'render' => fn ($row) => $this->numero($row['meta_anual']),
            ],
            [
                'key' => 't1',
                'label' => 'T1',
                'align' => 'right',
                'render' => fn ($row) => $this->numero($row['t1']),
            ],
            [
                'key' => 't2',
                'label' => 'T2',
                'align' => 'right',
                'render' => fn ($row) => $this->numero($row['t2']),
            ],
            [
                'key' => 't3',
                'label' => 'T3',
                'align' => 'right',
                'render' => fn ($row) => $this->numero($row['t3']),
            ],
            [
                'key' => 't4',
                'label' => 'T4',
                'align' => 'right',
                'render' => fn ($row) => $this->numero($row['t4']),
            ],
            [
                'key' => 'acumulado',
                'label' => 'Acumulado',
                'align' => 'right',
                'render' => fn ($row) => '<span class="font-semibold text-slate-700 dark:text-slate-200">'.$this->numero($row['acumulado']).'</span>',
            ],
            [
                'key' => 'cumplimiento',
                'label' => 'Cumplimiento %',
                'align' => 'right',
                'render' => fn ($row) => $row['cumplimiento'] !== null
                    ? e(number_format($row['cumplimiento'], 1, '.', '')).'%'
                    : "\u{2014}",
            ],
            [
                'key' => 'semaforo',
                'label' => 'Semáforo',
                'align' => 'center',
                'render' => fn ($row) => $this->badgeSemaforo($row['semaforo']),
            ],
        ];
    }

    protected function numero(?float $valor): string
    {
        if ($valor === null) {
            return "\u{2014}";
        }
        $fmt = number_format($valor, 4, '.', '');

        return e(rtrim(rtrim($fmt, '0'), '.'));
    }

    protected function badgeSemaforo(string $semaforo): string
    {
        $clases = match ($semaforo) {
            'verde' => 'bg-green-100 text-green-800',
            'amarillo' => 'bg-yellow-100 text-yellow-800',
            'rojo' => 'bg-red-100 text-red-800',
            'rojo_alto' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-700',
        };
        $etiqueta = match ($semaforo) {
            'verde' => 'Verde',
            'amarillo' => 'Amarillo',
            'rojo' => 'Rojo',
            'rojo_alto' => 'Rojo alto',
            default => 'Sin datos',
        };

        return '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '
            .$clases.'">'.e($etiqueta).'</span>';
    }
}
