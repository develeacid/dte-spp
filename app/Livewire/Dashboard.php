<?php

namespace App\Livewire;

use App\Services\DashboardService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
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

    private function teamId(): int
    {
        return auth()->user()->currentTeam->id;
    }
}
