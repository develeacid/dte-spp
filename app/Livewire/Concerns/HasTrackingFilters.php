<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

trait HasTrackingFilters
{
    #[Url(as: 'programa')]
    public ?int $filtroPrograma = null;

    #[Url(as: 'nivel')]
    public ?int $filtroMirNivel = null;

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

    #[Url(as: 'trimestre')]
    public ?int $filtroTrimestre = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'dashboard';

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

    /**
     * Ordena niveles MIR en el orden de la ficha:
     * Por programa -> Fin -> Proposito -> C1, C1.A1, C1.A2... -> C2, C2.A1... -> C3...
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
}
