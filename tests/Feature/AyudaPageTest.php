<?php

namespace Tests\Feature;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\CatalogoOrdenamientosSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AyudaPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CatalogoOrdenamientosSeeder::class);
    }

    public function test_usuario_autenticado_ve_ayuda(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/ayuda');

        $response->assertOk()
            ->assertSee('Glosario')
            ->assertSee('Marco Normativo')
            ->assertSee('Administración Pública')
            ->assertSee('Ley Federal de Presupuesto y Responsabilidad Hacendaria');
    }

    public function test_operador_sin_permisos_especiales_ve_ayuda(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        $this->actingAs($user)->get('/ayuda')->assertOk();
    }

    public function test_guest_redirige_a_login(): void
    {
        $this->get('/ayuda')->assertRedirect('/login');
    }
}
