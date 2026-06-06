<?php

namespace Tests\Feature\Livewire\Tracking;

use App\Livewire\Tracking\SabanaCaptura;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SabanaCapturaChartReactivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function chart_donut_estado_tiene_wire_key_dinamico_md5(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        $this->actingAs($user);

        $html = Livewire::test(SabanaCaptura::class)->html();

        $this->assertMatchesRegularExpression(
            '/wire:key="sabana-donut-estado-[a-f0-9]{32}"/',
            $html,
        );
    }

    #[Test]
    public function chart_bar_programa_tiene_wire_key_dinamico_md5(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        $this->actingAs($user);

        $html = Livewire::test(SabanaCaptura::class)->html();

        $this->assertMatchesRegularExpression(
            '/wire:key="sabana-bar-programa-[a-f0-9]{32}"/',
            $html,
        );
    }
}
