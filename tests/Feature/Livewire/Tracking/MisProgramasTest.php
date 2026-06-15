<?php

namespace Tests\Feature\Livewire\Tracking;

use App\Livewire\Tracking\MisProgramas;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MisProgramasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function operador_ve_los_programas_de_su_team_y_no_los_de_otro(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $propio = ProgramaPresupuestario::create([
            'nombre' => 'Programa de mi UR',
            'clave' => 'MIO-01',
            'team_id' => $operador->currentTeam->id,
        ]);

        $otroUsuario = User::factory()->withPersonalTeam()->create();
        $ajeno = ProgramaPresupuestario::create([
            'nombre' => 'Programa de otra UR',
            'clave' => 'AJENO-01',
            'team_id' => $otroUsuario->currentTeam->id,
        ]);

        $this->actingAs($operador);

        Livewire::test(MisProgramas::class)
            ->assertSee('MIO-01')
            ->assertDontSee('AJENO-01');
    }

    #[Test]
    public function la_ruta_programas_carga_para_el_operador(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');
        $this->actingAs($operador);

        $this->get(route('tracking.programas'))->assertOk();
    }
}
