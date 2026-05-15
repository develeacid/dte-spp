<?php

namespace Tests\Feature\Mml;

use App\Enums\SystemRole;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TransparenciaPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RouteCoberturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);
        $this->seed(TransparenciaPermissionsSeeder::class);
    }

    public function test_guest_redirige_a_login(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        $this->get(route('mml.cobertura', $programa))
            ->assertRedirect(route('login'));
    }

    public function test_rol_sin_ver_padron_recibe_403(): void
    {
        // RESPONSABLE_DATOS_ABIERTOS es el único rol que NO recibe ver_padron
        // por PadronPermissionsSeeder (planeador/operador/analistas sí lo tienen).
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::RESPONSABLE_DATOS_ABIERTOS->value);

        $this->actingAs($user)
            ->get(route('mml.cobertura', $programa))
            ->assertForbidden();
    }

    public function test_planeador_con_ver_padron_obtiene_200(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $this->actingAs($user)
            ->get(route('mml.cobertura', $programa))
            ->assertOk();
    }
}
