<?php

namespace Tests\Feature\Exports;

use App\Livewire\Tracking\SabanaCaptura;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SabanaCapturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_sabana_captura_accessible_by_operador(): void
    {
        $user = User::where('email', 'ele.operador@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('tracking.sabana-captura'));

        $response->assertStatus(200);
    }

    public function test_sabana_captura_accessible_by_planeador(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('tracking.sabana-captura'));

        $response->assertStatus(200);
    }

    public function test_sabana_captura_forbidden_without_permission(): void
    {
        $user = User::factory()->create();
        $team = Team::first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $response = $this->actingAs($user)
            ->get(route('tracking.sabana-captura'));

        $response->assertStatus(403);
    }

    public function test_sabana_captura_renders_livewire_component(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SabanaCaptura::class)
            ->assertSee('Sabana de Captura');
    }

    public function test_sabana_captura_filters_by_trimestre(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SabanaCaptura::class)
            ->set('filtroTrimestre', 1)
            ->assertStatus(200);
    }

    public function test_admin_sees_all_teams_in_sabana(): void
    {
        $user = User::where('email', 'ele.admin@gmail.com')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('tracking.sabana-captura'));

        $response->assertStatus(200);
    }
}
