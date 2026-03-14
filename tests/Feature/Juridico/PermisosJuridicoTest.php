<?php

namespace Tests\Feature\Juridico;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\JuridicoTestHelpers;

class PermisosJuridicoTest extends TestCase
{
    use RefreshDatabase;
    use JuridicoTestHelpers;

    private User $juridico;
    private User $planeador;
    private User $operador;
    private int $programaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedJuridicoPermissions();

        $this->juridico = User::factory()->withPersonalTeam()->create();
        $this->juridico->assignRole(SystemRole::ANALISTA_JURIDICO->value);

        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole(SystemRole::PLANEADOR->value);

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole(SystemRole::OPERADOR->value);

        $programa = $this->crearPrograma($this->juridico->currentTeam->id);
        $this->programaId = $programa->id;
    }

    public function test_analista_juridico_puede_acceder_panel(): void
    {
        $response = $this->actingAs($this->juridico)->get('/juridico');
        $response->assertOk();
    }

    public function test_analista_juridico_puede_ver_programa(): void
    {
        $response = $this->actingAs($this->juridico)->get("/juridico/programa/{$this->programaId}");
        $response->assertOk();
    }

    public function test_analista_juridico_puede_crear_fundamento(): void
    {
        $response = $this->actingAs($this->juridico)->get("/juridico/programa/{$this->programaId}/fundamento/create");
        $response->assertOk();
    }

    public function test_analista_juridico_puede_gestionar_documentos(): void
    {
        $response = $this->actingAs($this->juridico)->get("/juridico/programa/{$this->programaId}/documentos");
        $response->assertOk();
    }

    public function test_analista_juridico_puede_validar(): void
    {
        $response = $this->actingAs($this->juridico)->get("/juridico/programa/{$this->programaId}/validacion");
        $response->assertOk();
    }

    public function test_planeador_puede_ver_panel_solo_lectura(): void
    {
        $response = $this->actingAs($this->planeador)->get('/juridico');
        $response->assertOk();
    }

    public function test_planeador_puede_ver_programa(): void
    {
        $programa = $this->crearPrograma($this->planeador->currentTeam->id, 'PJ-002');
        $response = $this->actingAs($this->planeador)->get("/juridico/programa/{$programa->id}");
        $response->assertOk();
    }

    public function test_planeador_no_puede_crear_fundamento(): void
    {
        $programa = $this->crearPrograma($this->planeador->currentTeam->id, 'PJ-003');
        $response = $this->actingAs($this->planeador)->get("/juridico/programa/{$programa->id}/fundamento/create");
        $response->assertForbidden();
    }

    public function test_planeador_no_puede_validar(): void
    {
        $programa = $this->crearPrograma($this->planeador->currentTeam->id, 'PJ-004');
        $response = $this->actingAs($this->planeador)->get("/juridico/programa/{$programa->id}/validacion");
        $response->assertForbidden();
    }

    public function test_operador_no_puede_acceder_juridico(): void
    {
        $response = $this->actingAs($this->operador)->get('/juridico');
        $response->assertForbidden();
    }

    public function test_guest_redirige_a_login(): void
    {
        $response = $this->get('/juridico');
        $response->assertRedirect('/login');
    }
}
