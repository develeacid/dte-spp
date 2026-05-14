<?php

namespace Tests\Feature\Transparencia;

use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransparenciaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Defensa-en-profundidad: confirma que el middleware permission:<x> en
 * cada ruta de transparencia.* rechaza con 403 cuando el role del user
 * no tiene el permiso correspondiente. Las Policies son la otra capa
 * (testeada en DatasetAbiertoPolicyTest); este test cubre la frontera HTTP.
 */
class RouteAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TransparenciaPermissionsSeeder::class);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function makeUserWithRole(string $role): User
    {
        $u = User::factory()->withPersonalTeam()->create();
        $u->assignRole($role);

        return $u;
    }

    public function test_operador_no_accede_a_index(): void
    {
        $operador = $this->makeUserWithRole('operador');

        $this->actingAs($operador)
            ->get(route('transparencia.datos-abiertos.index'))
            ->assertForbidden();
    }

    public function test_planeador_si_accede_a_index(): void
    {
        $planeador = $this->makeUserWithRole('planeador');

        $this->actingAs($planeador)
            ->get(route('transparencia.datos-abiertos.index'))
            ->assertOk();
    }

    public function test_operador_no_accede_a_show(): void
    {
        $operador = $this->makeUserWithRole('operador');
        $ds = DatasetAbierto::factory()->create();

        $this->actingAs($operador)
            ->get(route('transparencia.datos-abiertos.show', $ds))
            ->assertForbidden();
    }

    public function test_operador_no_accede_a_editar(): void
    {
        $operador = $this->makeUserWithRole('operador');
        $ds = DatasetAbierto::factory()->create();

        $this->actingAs($operador)
            ->get(route('transparencia.datos-abiertos.edit', $ds))
            ->assertForbidden();
    }

    public function test_operador_no_accede_a_crear_entrega(): void
    {
        $operador = $this->makeUserWithRole('operador');
        $plantilla = DatasetAbierto::factory()->create(['periodo' => null]);

        $this->actingAs($operador)
            ->get(route('transparencia.datos-abiertos.crear-entrega', $plantilla))
            ->assertForbidden();
    }

    public function test_planeador_no_accede_a_editar_plantilla(): void
    {
        // Planeador tiene gestionar_dataset_abierto pero NO aprobar_datos_abiertos
        // que es el requerido por la ruta editar-plantilla.
        $planeador = $this->makeUserWithRole('planeador');
        $plantilla = DatasetAbierto::factory()->create(['periodo' => null]);

        $this->actingAs($planeador)
            ->get(route('transparencia.datos-abiertos.editar-plantilla', $plantilla))
            ->assertForbidden();
    }

    public function test_rda_si_accede_a_editar_plantilla(): void
    {
        $rda = $this->makeUserWithRole('responsable_datos_abiertos');
        $plantilla = DatasetAbierto::factory()->create(['periodo' => null]);

        $this->actingAs($rda)
            ->get(route('transparencia.datos-abiertos.editar-plantilla', $plantilla))
            ->assertOk();
    }

    public function test_guest_redirige_a_login(): void
    {
        $this->get(route('transparencia.datos-abiertos.index'))
            ->assertRedirect(route('login'));
    }
}
