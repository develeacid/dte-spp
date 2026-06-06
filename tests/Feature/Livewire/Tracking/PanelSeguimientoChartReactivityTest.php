<?php

namespace Tests\Feature\Livewire\Tracking;

use App\Livewire\Tracking\PanelSeguimiento;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PanelSeguimientoChartReactivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function chart_donut_semaforo_tiene_wire_key_dinamico_md5(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        $this->actingAs($user);

        $html = Livewire::test(PanelSeguimiento::class)->html();

        $this->assertMatchesRegularExpression(
            '/wire:key="panel-donut-semaforo-[a-f0-9]{32}"/',
            $html,
        );
    }

    #[Test]
    public function chart_bar_programa_tiene_wire_key_dinamico_md5(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        $this->actingAs($user);

        $html = Livewire::test(PanelSeguimiento::class)->html();

        $this->assertMatchesRegularExpression(
            '/wire:key="panel-bar-programa-[a-f0-9]{32}"/',
            $html,
        );
    }
}
