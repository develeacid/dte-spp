<?php

namespace Tests\Feature\Tracking;

use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\DashboardIndicadores;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardIndicadoresTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test Dashboard',
            'clave' => 'PT-DASH',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_renders_hierarchical_mir_structure(): void
    {
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'FIN test',
            'orden' => 1,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'PROPOSITO test',
            'orden' => 2,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'COMP test',
            'orden' => 3,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardIndicadores::class, ['programa' => $this->programa])
            ->assertSee('FIN test')
            ->assertSee('PROPOSITO test')
            ->assertSee('COMP test');
    }

    public function test_toggle_nivel_expands_and_collapses(): void
    {
        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'FIN test',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardIndicadores::class, ['programa' => $this->programa])
            ->assertSet('expandedNiveles', [])
            ->call('toggleNivel', $fin->id)
            ->assertSet('expandedNiveles', [$fin->id]);
    }

    public function test_toggle_nivel_collapses_when_already_expanded(): void
    {
        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'FIN test',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(DashboardIndicadores::class, ['programa' => $this->programa])
            ->call('toggleNivel', $fin->id)
            ->call('toggleNivel', $fin->id)
            ->assertSet('expandedNiveles', []);
    }

    public function test_empty_state_shown_without_levels(): void
    {
        Livewire::actingAs($this->user)
            ->test(DashboardIndicadores::class, ['programa' => $this->programa])
            ->assertSee('No hay niveles MIR configurados');
    }
}
