<?php

namespace Tests\Feature;

use App\Enums\SystemPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole($roleName);

        return $user;
    }

    public function test_sidebar_renders_for_authenticated_user(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('SPP 2026');
        $response->assertSee('Inicio');
    }

    public function test_admin_sees_all_navigation_sections(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Planeación');
        $response->assertSee('Seguimiento');
        $response->assertSee('Catálogos');
        $response->assertSee('Reportes');
        $response->assertSee('Administración');
    }

    public function test_operador_sees_limited_navigation(): void
    {
        $user = $this->createUserWithRole('operador');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Inicio');
        $response->assertSee('Seguimiento');
        $response->assertDontSee('Planeación');
        $response->assertDontSee('Catálogos');
        $response->assertDontSee('Administración');
    }

    public function test_planeador_sees_planning_and_catalogs(): void
    {
        $user = $this->createUserWithRole('planeador');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Planeación');
        $response->assertSee('Catálogos');
        $response->assertSee('Reportes');
        $response->assertDontSee('Administración');
    }

    public function test_sidebar_shows_user_name(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee($user->name);
    }

    public function test_sidebar_shows_team_name(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee($user->currentTeam->name);
    }

    public function test_topbar_renders_with_user_dropdown(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Profile');
        $response->assertSee('Log Out');
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_inter_font_loaded(): void
    {
        $user = $this->createUserWithRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('fonts.googleapis.com');
        $response->assertSee('Inter');
    }
}
