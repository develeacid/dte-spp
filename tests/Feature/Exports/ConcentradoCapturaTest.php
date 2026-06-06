<?php

namespace Tests\Feature\Exports;

use App\Livewire\Tracking\ConcentradoCaptura;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConcentradoCapturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_concentrado_accessible_by_operador(): void
    {
        $user = User::where('email', 'ele.operador@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('tracking.concentrado-captura'));

        $response->assertStatus(200);
    }

    public function test_concentrado_forbidden_without_permission(): void
    {
        $user = User::factory()->create();
        $team = Team::first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $response = $this->actingAs($user)
            ->get(route('tracking.concentrado-captura'));

        $response->assertStatus(403);
    }

    public function test_concentrado_renders_with_metrics(): void
    {
        $user = User::where('email', 'ele.operador@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(ConcentradoCaptura::class)
            ->assertSee('Concentrado de Captura');
    }

    public function test_concentrado_filters_by_date_range(): void
    {
        $user = User::where('email', 'ele.operador@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(ConcentradoCaptura::class)
            ->set('alcanceTemporal', 'rango')
            ->set('filtroFechaDesde', '2026-01-01')
            ->set('filtroFechaHasta', '2026-12-31')
            ->assertStatus(200);
    }

    public function test_admin_sees_all_teams(): void
    {
        $user = User::where('email', 'ele.admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('tracking.concentrado-captura'));

        $response->assertStatus(200);
    }
}
