<?php

namespace Tests\Feature\Presupuesto;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\PresupuestoTestHelpers;

class PermisosPresupuestoTest extends TestCase
{
    use PresupuestoTestHelpers;
    use RefreshDatabase;

    private User $financiero;

    private User $planeador;

    private User $operador;

    private int $programaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->financiero = User::factory()->withPersonalTeam()->create();
        $this->financiero->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole(SystemRole::PLANEADOR->value);

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole(SystemRole::OPERADOR->value);

        $programa = $this->crearPrograma(
            $this->financiero->currentTeam->id,
        );
        $this->programaId = $programa->id;
    }

    public function test_analista_financiero_puede_acceder_panel(): void
    {
        $response = $this->actingAs($this->financiero)->get('/presupuesto');

        $response->assertOk();
    }

    public function test_analista_financiero_puede_acceder_partidas(): void
    {
        $response = $this->actingAs($this->financiero)->get('/presupuesto/partidas');

        $response->assertOk();
    }

    public function test_analista_financiero_puede_crear_partida(): void
    {
        $response = $this->actingAs($this->financiero)->get('/presupuesto/partidas/create');

        $response->assertOk();
    }

    public function test_analista_financiero_puede_capturar_avance(): void
    {
        $response = $this->actingAs($this->financiero)
            ->get("/presupuesto/captura/{$this->programaId}");

        $response->assertOk();
    }

    public function test_planeador_puede_ver_panel_solo_lectura(): void
    {
        $response = $this->actingAs($this->planeador)->get('/presupuesto');

        $response->assertOk();
    }

    public function test_planeador_no_puede_crear_partida(): void
    {
        $response = $this->actingAs($this->planeador)->get('/presupuesto/partidas/create');

        $response->assertForbidden();
    }

    public function test_planeador_no_puede_capturar_avance(): void
    {
        $response = $this->actingAs($this->planeador)
            ->get("/presupuesto/captura/{$this->programaId}");

        $response->assertForbidden();
    }

    public function test_operador_no_puede_acceder_presupuesto(): void
    {
        $response = $this->actingAs($this->operador)->get('/presupuesto');

        $response->assertForbidden();
    }

    public function test_guest_redirige_a_login(): void
    {
        $response = $this->get('/presupuesto');

        $response->assertRedirect('/login');
    }
}
