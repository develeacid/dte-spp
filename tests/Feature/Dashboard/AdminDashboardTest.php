<?php

namespace Tests\Feature\Dashboard;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_sees_global_kpis_and_admin_links(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::ADMIN->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Monitoreo IA')
            ->assertSee('Gestion de usuarios');
    }

    public function test_operador_does_not_see_admin_links(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Monitoreo IA')
            ->assertSee('Capturar avance');
    }
}
