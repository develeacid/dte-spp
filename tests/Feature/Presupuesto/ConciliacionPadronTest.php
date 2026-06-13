<?php

namespace Tests\Feature\Presupuesto;

use App\Livewire\Presupuesto\ConciliacionPadron;
use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ConciliacionPadronTest extends TestCase
{
    use RefreshDatabase;

    private User $analista;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);

        $this->analista = User::factory()->withPersonalTeam()->create();
        $this->analista->assignRole('analista_financiero');

        $this->programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->analista->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);

        // Tesorería local: una partida con un avance pagado de 1,200.
        $partida = PartidaPresupuestal::create([
            'programa_presupuestario_id' => $this->programa->id, 'clave_partida' => '4000',
            'descripcion' => 'Subsidios', 'monto_aprobado' => 2000, 'monto_modificado' => 2000,
            'ejercicio_fiscal' => 2026, 'team_id' => $this->analista->currentTeam->id,
        ]);
        AvanceFinanciero::create([
            'partida_presupuestal_id' => $partida->id, 'trimestre' => 1,
            'monto_comprometido' => 1200, 'monto_devengado' => 1200, 'monto_pagado' => 1200,
            'registrado_por' => $this->analista->id,
        ]);
    }

    private function fakeMontos(string $total): void
    {
        Http::fake([
            '*/montos-entregados*' => Http::response([
                'spp_program_id' => $this->programa->id,
                'ejercicio' => 2026,
                'monto_entregado_total' => $total,
                'por_componente' => [],
            ], 200),
        ]);
        Http::preventStrayRequests();
    }

    public function test_cruza_tesoreria_local_con_montos_entregados_del_padron(): void
    {
        $this->fakeMontos('1000.00');

        Livewire::actingAs($this->analista)
            ->test(ConciliacionPadron::class, ['programa' => $this->programa])
            ->assertSee('1,200.00')   // pagado tesorería
            ->assertSee('1,000.00')   // entregado padrón (geobase)
            ->assertSee('200.00');    // diferencia
    }

    public function test_muestra_error_si_geobase_no_responde(): void
    {
        Http::fake(['*/montos-entregados*' => Http::response([], 500)]);
        Http::preventStrayRequests();

        Livewire::actingAs($this->analista)
            ->test(ConciliacionPadron::class, ['programa' => $this->programa])
            ->assertOk()
            ->assertSee('1,200.00')  // la parte local sigue visible
            ->assertSee('padrón');   // mensaje de error de consulta al padrón
    }

    public function test_operador_no_accede(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $this->actingAs($operador)
            ->get(route('presupuesto.conciliacion', $this->programa))
            ->assertForbidden();
    }
}
