<?php

namespace Tests\Feature\Livewire\Tracking;

use App\Livewire\Tracking\PanelSeguimiento;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PanelSeguimientoWireKeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function panel_seguimiento_mantiene_wire_key_data_table_root(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        $this->actingAs($user);

        Livewire::test(PanelSeguimiento::class)
            ->assertSeeHtml('wire:key="data-table-root"');
    }
}
