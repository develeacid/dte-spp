<?php

namespace Tests\Feature\Presupuesto;

use App\Models\Presupuesto\MetaGastoTrimestral;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportePoaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);
    }

    private function programaConGasto(int $teamId): void
    {
        $programa = ProgramaPresupuestario::factory()->create(['team_id' => $teamId, 'clave' => 'POA-001']);
        $partida = PartidaPresupuestal::create([
            'programa_presupuestario_id' => $programa->id,
            'clave_partida' => '21101',
            'descripcion' => 'Materiales de oficina POA',
            'monto_aprobado' => 50000, 'monto_modificado' => 50000,
            'ejercicio_fiscal' => 2026, 'team_id' => $teamId,
        ]);
        MetaGastoTrimestral::create([
            'partida_presupuestal_id' => $partida->id, 'trimestre' => 1,
            'monto_programado' => 12500, 'justificacion' => 'x',
        ]);
    }

    public function test_planeador_ve_el_poa_con_un_concepto(): void
    {
        $planeador = User::factory()->withPersonalTeam()->create();
        $planeador->assignRole('planeador');
        $this->programaConGasto($planeador->currentTeam->id);

        $this->actingAs($planeador)
            ->get(route('presupuesto.poa'))
            ->assertOk()
            ->assertSee('Materiales de oficina POA');
    }

    public function test_operador_no_accede(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $this->actingAs($operador)
            ->get(route('presupuesto.poa'))
            ->assertForbidden();
    }
}
