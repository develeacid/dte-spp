<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JetstreamCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_register_route_is_disabled(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(404);
    }

    public function test_register_post_is_disabled(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(404);
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Iniciar Sesión');
    }

    public function test_login_page_does_not_show_register_link(): void
    {
        $response = $this->get('/login');
        $response->assertDontSee('register');
    }

    public function test_forgot_password_renders_in_spanish(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Recuperar Contraseña');
    }

    public function test_profile_does_not_show_delete_account(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/user/profile');
        $response->assertDontSee('Delete Account');
        $response->assertDontSee('Eliminar Cuenta');
    }

    public function test_profile_shows_spanish_labels(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/user/profile');
        $response->assertSee('Información Personal');
        $response->assertSee('Seguridad');
    }

    public function test_topbar_shows_role_and_ur(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertSee('Mi Perfil');
        $response->assertSee('Cerrar Sesión');
    }

    public function test_team_settings_shows_ur_terminology(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/teams/' . $user->currentTeam->id);
        $response->assertSee('Unidad Responsable');
    }

    public function test_operador_cannot_see_create_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('operador');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertDontSee('Crear Unidad Responsable');
    }
}
