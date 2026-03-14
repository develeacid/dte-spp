<?php

namespace App\Livewire;

use App\Enums\SystemRole;
use App\Services\DashboardService;
use App\Services\Presupuesto\PresupuestoResumenService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }

    #[Computed]
    public function dashboardRole(): string
    {
        $user = auth()->user();

        if ($user->hasRole(SystemRole::ADMIN->value)) {
            return 'admin';
        }

        if ($user->hasRole(SystemRole::PLANEADOR->value)) {
            return 'planeador';
        }

        return 'operador';
    }

    #[Computed]
    public function recentNotifications()
    {
        return auth()->user()->unreadNotifications()->limit(5)->get();
    }

    #[Computed]
    public function adminStats(): ?object
    {
        if (! auth()->user()->can('revisar_avance')) {
            return null;
        }

        return app(DashboardService::class)->getAdminStats($this->teamId());
    }

    #[Computed]
    public function operadorStats(): ?object
    {
        if (! auth()->user()->can('capturar_avance')) {
            return null;
        }

        return app(DashboardService::class)->getOperadorStats(auth()->id(), $this->teamId());
    }

    #[Computed]
    public function semaforo(): array
    {
        if (auth()->user()->can('revisar_avance')) {
            return app(DashboardService::class)->getSemaforoDistribution($this->teamId());
        }

        if (auth()->user()->can('capturar_avance')) {
            return app(DashboardService::class)->getSemaforoUsuario(auth()->id());
        }

        return ['verde' => 0, 'amarillo' => 0, 'rojo' => 0];
    }

    #[Computed]
    public function avancePorPrograma()
    {
        if (! auth()->user()->can('revisar_avance')) {
            return collect();
        }

        return app(DashboardService::class)->getAvancePorPrograma($this->teamId());
    }

    #[Computed]
    public function tendenciaCaptura()
    {
        if (! auth()->user()->can('revisar_avance')) {
            return collect();
        }

        return app(DashboardService::class)->getTendenciaCaptura($this->teamId());
    }

    #[Computed]
    public function haySemaforoData(): bool
    {
        $s = $this->semaforo;

        return ($s['verde'] + $s['amarillo'] + $s['rojo']) > 0;
    }

    #[Computed]
    public function avancesPorRevisar()
    {
        if (! auth()->user()->can('revisar_avance')) {
            return collect();
        }

        return app(DashboardService::class)->getAvancesPorRevisar($this->teamId());
    }

    #[Computed]
    public function globalAdminStats(): ?object
    {
        if (! auth()->user()->hasRole(SystemRole::ADMIN->value)) {
            return null;
        }

        return app(DashboardService::class)->getGlobalAdminStats();
    }

    #[Computed]
    public function financieroStats(): ?object
    {
        if (! auth()->user()->can('ver_datos_financieros')) {
            return null;
        }

        $teamId = $this->teamId();
        $ejercicio = config('presupuesto.ejercicio_default');
        $service = app(PresupuestoResumenService::class);

        $programas = \App\Models\ProgramaPresupuestario::paraTeam($teamId)
            ->ejercicio($ejercicio)
            ->with(['partidasPresupuestales' => fn ($q) => $q->with('avancesFinancieros')])
            ->get();

        $totalAprobado = 0;
        $totalEjercido = 0;

        foreach ($programas as $programa) {
            foreach ($programa->partidasPresupuestales as $partida) {
                $totalAprobado += $partida->monto_efectivo;
                $totalEjercido += $partida->avancesFinancieros->sum('monto_pagado');
            }
        }

        $alertas = $service->alertasSubejercicio($teamId, $ejercicio);

        return (object) [
            'total_aprobado' => $totalAprobado,
            'total_ejercido' => $totalEjercido,
            'pct_ejercido' => $totalAprobado > 0 ? round(($totalEjercido / $totalAprobado) * 100, 2) : 0,
            'alertas_subejercicio' => $alertas->count(),
        ];
    }

    private function teamId(): int
    {
        return auth()->user()->currentTeam->id;
    }
}
