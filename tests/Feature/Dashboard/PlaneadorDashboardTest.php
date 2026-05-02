<?php

namespace Tests\Feature\Dashboard;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaneadorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_avances_por_revisar_returns_only_en_revision(): void
    {
        // Requires full seeder setup — covered by integration test.
        $this->markTestSkipped('Requires full seeder setup — covered by integration test.');
    }

    public function test_planeador_sees_planeador_dashboard(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel de seguimiento');
    }
}
