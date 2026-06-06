<?php

namespace Tests\Feature\Livewire\Concerns;

use App\Livewire\Concerns\HasSearchablePagedTable;
use App\Livewire\Concerns\HasTrackingFilters;
use Livewire\Component;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FakeTrackingComponent extends Component
{
    use HasSearchablePagedTable;
    use HasTrackingFilters;

    public function render()
    {
        return '<div></div>';
    }
}

class HasTrackingFiltersTest extends TestCase
{
    #[Test]
    public function defaults_correctos(): void
    {
        Livewire::test(FakeTrackingComponent::class)
            ->assertSet('alcanceTemporal', 'todo')
            ->assertSet('activeTab', 'dashboard')
            ->assertSet('filtroPrograma', null)
            ->assertSet('filtroMirNivel', null)
            ->assertSet('filtroEstado', null)
            ->assertSet('filtroEjercicio', null)
            ->assertSet('filtroFechaDesde', null)
            ->assertSet('filtroFechaHasta', null)
            ->assertSet('filtroTrimestre', null);
    }

    #[Test]
    public function cambiar_programa_limpia_nivel_mir(): void
    {
        Livewire::test(FakeTrackingComponent::class)
            ->set('filtroMirNivel', 42)
            ->set('filtroPrograma', 7)
            ->assertSet('filtroMirNivel', null)
            ->assertSet('filtroPrograma', 7);
    }

    #[Test]
    public function updated_alcance_a_todo_limpia_ejercicio_fecha_trimestre(): void
    {
        Livewire::test(FakeTrackingComponent::class)
            ->set('filtroEjercicio', 2026)
            ->set('filtroFechaDesde', '2026-01-01')
            ->set('filtroFechaHasta', '2026-12-31')
            ->set('filtroTrimestre', 2)
            ->set('alcanceTemporal', 'todo')
            ->assertSet('filtroEjercicio', null)
            ->assertSet('filtroFechaDesde', null)
            ->assertSet('filtroFechaHasta', null)
            ->assertSet('filtroTrimestre', null);
    }

    #[Test]
    public function updated_alcance_a_rango_limpia_ejercicio_y_trimestre(): void
    {
        Livewire::test(FakeTrackingComponent::class)
            ->set('filtroEjercicio', 2026)
            ->set('filtroTrimestre', 3)
            ->set('filtroFechaDesde', '2026-04-01')
            ->set('alcanceTemporal', 'rango')
            ->assertSet('filtroEjercicio', null)
            ->assertSet('filtroTrimestre', null)
            ->assertSet('filtroFechaDesde', '2026-04-01');
    }
}
