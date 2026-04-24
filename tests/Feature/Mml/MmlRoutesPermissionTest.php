<?php

namespace Tests\Feature\Mml;

use App\Enums\SystemRole;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MmlRoutesPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\PresupuestoPermissionsSeeder::class);
        $this->seed(\Database\Seeders\JuridicoPermissionsSeeder::class);
    }

    public function test_operador_recibe_403_al_abrir_lista_programas(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole(SystemRole::OPERADOR->value);

        $response = $this->actingAs($operador)->get(route('mml.programas'));

        $response->assertStatus(403);
    }

    public function test_operador_recibe_403_al_abrir_mir_editor(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole(SystemRole::OPERADOR->value);

        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $operador->currentTeam->id,
        ]);

        $response = $this->actingAs($operador)
            ->get(route('mml.mir', ['programa' => $programa->id]));

        $response->assertStatus(403);
    }

    public function test_operador_recibe_403_al_abrir_dashboard_importaciones(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole(SystemRole::OPERADOR->value);

        $response = $this->actingAs($operador)->get(route('mml.importaciones'));

        $response->assertStatus(403);
    }

    public function test_analista_financiero_recibe_403_en_rutas_mml(): void
    {
        $analista = User::factory()->withPersonalTeam()->create();
        $analista->assignRole(SystemRole::ANALISTA_FINANCIERO->value);

        $this->actingAs($analista)
            ->get(route('mml.programas'))
            ->assertStatus(403);
    }

    public function test_analista_juridico_recibe_403_en_rutas_mml(): void
    {
        $analista = User::factory()->withPersonalTeam()->create();
        $analista->assignRole(SystemRole::ANALISTA_JURIDICO->value);

        $this->actingAs($analista)
            ->get(route('mml.programas'))
            ->assertStatus(403);
    }

    public function test_planeador_accede_a_lista_programas(): void
    {
        $planeador = User::factory()->withPersonalTeam()->create();
        $planeador->assignRole(SystemRole::PLANEADOR->value);

        $response = $this->actingAs($planeador)->get(route('mml.programas'));

        $response->assertStatus(200);
    }

    public function test_planeador_accede_a_dashboard_importaciones(): void
    {
        $planeador = User::factory()->withPersonalTeam()->create();
        $planeador->assignRole(SystemRole::PLANEADOR->value);

        $response = $this->actingAs($planeador)->get(route('mml.importaciones'));

        $response->assertStatus(200);
    }

    public function test_admin_accede_a_rutas_mml(): void
    {
        $admin = User::factory()->withPersonalTeam()->create();
        $admin->assignRole(SystemRole::ADMIN->value);

        $this->actingAs($admin)
            ->get(route('mml.programas'))
            ->assertStatus(200);
    }

    public function test_usuario_no_autenticado_redirige_a_login(): void
    {
        $this->get(route('mml.programas'))
            ->assertRedirect(route('login'));
    }
}
