<?php

namespace App\Livewire\Evaluation;

use App\Models\Evaluation\AnexoTransversal;
use App\Models\Evaluation\EvaluacionPrograma;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class PanelTransversal extends Component
{
    #[Url]
    public string $tab = 'ped';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('exportar_reportes'), 403);
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['ped', 'ods', 'ur', 'anexo'])) {
            $this->tab = $tab;
        }
    }

    public function getDataPed(): array
    {
        $ejercicio = (int) config('app.ejercicio_fiscal', now()->year);

        $evaluaciones = EvaluacionPrograma::with([
            'programa.mirNiveles.pedObjetivoEstrategico.tema.eje',
        ])
            ->where('ejercicio_fiscal', $ejercicio)
            ->get();

        $grouped = [];
        $sinAlineacion = [];

        foreach ($evaluaciones as $eval) {
            $programa = $eval->programa;
            if (! $programa) {
                continue;
            }

            $ejes = $programa->mirNiveles
                ->map(fn ($n) => $n->pedObjetivoEstrategico?->tema?->eje)
                ->filter()
                ->unique('id');

            if ($ejes->isEmpty()) {
                $sinAlineacion[] = [
                    'programa' => $programa->nombre,
                    'clave' => $programa->clave,
                ];

                continue;
            }

            foreach ($ejes as $eje) {
                $key = $eje->id;
                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'eje_nombre' => $eje->nombre,
                        'eje_numero' => $eje->numero,
                        'programas' => [],
                        'indices' => [],
                        'semaforos' => ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'sin_dato' => 0],
                    ];
                }

                $grouped[$key]['programas'][] = [
                    'nombre' => $programa->nombre,
                    'clave' => $programa->clave,
                    'indice' => $eval->indice_eficacia,
                    'semaforos' => $eval->conteo_semaforos ?? [],
                ];
                $grouped[$key]['indices'][] = (float) $eval->indice_eficacia;

                $conteo = $eval->conteo_semaforos ?? [];
                foreach (['verde', 'amarillo', 'rojo', 'sin_dato'] as $color) {
                    $grouped[$key]['semaforos'][$color] += ($conteo[$color] ?? 0);
                }
            }
        }

        // Compute averages and sort by average index desc
        $result = collect($grouped)->map(function ($data) {
            $data['promedio_indice'] = count($data['indices']) > 0
                ? round(array_sum($data['indices']) / count($data['indices']), 2)
                : 0;
            unset($data['indices']);

            return $data;
        })->sortByDesc('promedio_indice')->values()->toArray();

        return [
            'ejes' => $result,
            'sin_alineacion' => $sinAlineacion,
        ];
    }

    public function getDataOds(): array
    {
        $ejercicio = (int) config('app.ejercicio_fiscal', now()->year);

        // Chain: EvaluacionPrograma → programa → mirNiveles → pedObjetivoEstrategico → pndObjetivos → odsMetas → objetivo
        $evaluaciones = EvaluacionPrograma::with([
            'programa.mirNiveles.pedObjetivoEstrategico.pndObjetivos.odsMetas.objetivo',
        ])
            ->where('ejercicio_fiscal', $ejercicio)
            ->get();

        $grouped = [];

        foreach ($evaluaciones as $eval) {
            $programa = $eval->programa;
            if (! $programa) {
                continue;
            }

            $odsObjetivos = collect();

            foreach ($programa->mirNiveles as $nivel) {
                $pedObj = $nivel->pedObjetivoEstrategico;
                if (! $pedObj) {
                    continue;
                }

                foreach ($pedObj->pndObjetivos as $pndObj) {
                    foreach ($pndObj->odsMetas as $odsMeta) {
                        $odsObj = $odsMeta->objetivo;
                        if ($odsObj) {
                            $odsObjetivos->push($odsObj);
                        }
                    }
                }
            }

            $odsObjetivos = $odsObjetivos->unique('id');

            foreach ($odsObjetivos as $odsObj) {
                $key = $odsObj->id;
                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'ods_numero' => $odsObj->numero,
                        'ods_nombre' => $odsObj->nombre,
                        'programas' => [],
                        'indices' => [],
                        'semaforos' => ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'sin_dato' => 0],
                    ];
                }

                $grouped[$key]['programas'][] = [
                    'nombre' => $programa->nombre,
                    'clave' => $programa->clave,
                    'indice' => $eval->indice_eficacia,
                ];
                $grouped[$key]['indices'][] = (float) $eval->indice_eficacia;

                $conteo = $eval->conteo_semaforos ?? [];
                foreach (['verde', 'amarillo', 'rojo', 'sin_dato'] as $color) {
                    $grouped[$key]['semaforos'][$color] += ($conteo[$color] ?? 0);
                }
            }
        }

        $result = collect($grouped)->map(function ($data) {
            $data['promedio_indice'] = count($data['indices']) > 0
                ? round(array_sum($data['indices']) / count($data['indices']), 2)
                : 0;
            unset($data['indices']);

            return $data;
        })->sortBy('ods_numero')->values()->toArray();

        return $result;
    }

    public function getDataUr(): array
    {
        $ejercicio = (int) config('app.ejercicio_fiscal', now()->year);

        $evaluaciones = EvaluacionPrograma::with(['programa.team'])
            ->where('ejercicio_fiscal', $ejercicio)
            ->get();

        $grouped = [];

        foreach ($evaluaciones as $eval) {
            $team = $eval->programa?->team;
            if (! $team) {
                continue;
            }

            $key = $team->id;
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'team_nombre' => $team->name,
                    'programas' => [],
                    'indices' => [],
                    'semaforos' => ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'sin_dato' => 0],
                ];
            }

            $grouped[$key]['programas'][] = [
                'nombre' => $eval->programa->nombre,
                'clave' => $eval->programa->clave,
                'indice' => $eval->indice_eficacia,
            ];
            $grouped[$key]['indices'][] = (float) $eval->indice_eficacia;

            $conteo = $eval->conteo_semaforos ?? [];
            foreach (['verde', 'amarillo', 'rojo', 'sin_dato'] as $color) {
                $grouped[$key]['semaforos'][$color] += ($conteo[$color] ?? 0);
            }
        }

        $result = collect($grouped)->map(function ($data) {
            $data['promedio_indice'] = count($data['indices']) > 0
                ? round(array_sum($data['indices']) / count($data['indices']), 2)
                : 0;
            unset($data['indices']);

            return $data;
        })->sortByDesc('promedio_indice')->values()->toArray();

        // Add ranking position
        foreach ($result as $i => &$item) {
            $item['ranking'] = $i + 1;
        }

        return $result;
    }

    public function getDataAnexo(): array
    {
        $ejercicio = (int) config('app.ejercicio_fiscal', now()->year);

        $anexos = AnexoTransversal::activos()
            ->with([
                'indicadores.mirNivel.programa' => function ($q) use ($ejercicio) {
                    $q->where('ejercicio_fiscal', $ejercicio);
                },
            ])
            ->get();

        $result = [];

        foreach ($anexos as $anexo) {
            $indicadores = $anexo->indicadores->filter(fn ($ind) => $ind->mirNivel?->programa !== null);

            if ($indicadores->isEmpty()) {
                continue;
            }

            // Get unique programa IDs linked to this anexo
            $programaIds = $indicadores->map(fn ($ind) => $ind->mirNivel->programa_presupuestario_id)->unique();

            // Get evaluaciones for those programs
            $evaluaciones = EvaluacionPrograma::whereIn('programa_presupuestario_id', $programaIds)
                ->where('ejercicio_fiscal', $ejercicio)
                ->get();

            $indices = $evaluaciones->pluck('indice_eficacia')->map(fn ($v) => (float) $v)->toArray();
            $semaforos = ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'sin_dato' => 0];

            foreach ($evaluaciones as $eval) {
                $conteo = $eval->conteo_semaforos ?? [];
                foreach (['verde', 'amarillo', 'rojo', 'sin_dato'] as $color) {
                    $semaforos[$color] += ($conteo[$color] ?? 0);
                }
            }

            $result[] = [
                'anexo_nombre' => $anexo->nombre,
                'anexo_clave' => $anexo->clave,
                'total_indicadores' => $indicadores->count(),
                'total_programas' => $programaIds->count(),
                'promedio_indice' => count($indices) > 0 ? round(array_sum($indices) / count($indices), 2) : 0,
                'semaforos' => $semaforos,
                'indicadores' => $indicadores->map(fn ($ind) => [
                    'nombre' => $ind->nombre,
                    'programa' => $ind->mirNivel->programa->nombre ?? '',
                    'clave_programa' => $ind->mirNivel->programa->clave ?? '',
                    'nivel' => $ind->mirNivel->tipo_nivel,
                ])->values()->toArray(),
            ];
        }

        return collect($result)->sortByDesc('promedio_indice')->values()->toArray();
    }

    public function render()
    {
        $data = match ($this->tab) {
            'ped' => $this->getDataPed(),
            'ods' => $this->getDataOds(),
            'ur' => $this->getDataUr(),
            'anexo' => $this->getDataAnexo(),
            default => $this->getDataPed(),
        };

        return view('livewire.evaluation.panel-transversal', [
            'data' => $data,
        ]);
    }
}
