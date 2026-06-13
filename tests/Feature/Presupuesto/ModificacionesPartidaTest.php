<?php

namespace Tests\Feature\Presupuesto;

use App\Livewire\Presupuesto\ModificacionesPartida;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModificacionesPartidaTest extends TestCase
{
    use RefreshDatabase;

    private PartidaPresupuestal $partida;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);

        $analista = User::factory()->withPersonalTeam()->create();
        $analista->assignRole('analista_financiero');
        $this->actingAsAnalista = $analista;

        $programa = ProgramaPresupuestario::factory()->create(['team_id' => $analista->currentTeam->id]);
        $this->partida = PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programa->id, 'clave_partida' => '4000',
            'descripcion' => 'Subsidios', 'monto_aprobado' => 1000, 'monto_modificado' => null,
            'ejercicio_fiscal' => 2026, 'team_id' => $analista->currentTeam->id,
        ]);
    }

    private User $actingAsAnalista;

    public function test_analista_accede_y_registra_una_adecuacion(): void
    {
        $this->actingAs($this->actingAsAnalista)
            ->get(route('presupuesto.partidas.modificaciones', $this->partida))
            ->assertOk();

        Livewire::actingAs($this->actingAsAnalista)
            ->test(ModificacionesPartida::class, ['partida' => $this->partida])
            ->set('tipo', 'ampliacion')
            ->set('monto', 250)
            ->set('fecha', '2026-04-01')
            ->set('oficio', 'OF-77')
            ->call('registrar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('modificaciones_presupuestales', [
            'partida_presupuestal_id' => $this->partida->id, 'tipo' => 'ampliacion', 'monto' => 250,
        ]);
        $this->assertEqualsWithDelta(1250, (float) $this->partida->fresh()->monto_modificado, 0.01);
    }

    public function test_operador_no_accede(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $this->actingAs($operador)
            ->get(route('presupuesto.partidas.modificaciones', $this->partida))
            ->assertForbidden();
    }
}
