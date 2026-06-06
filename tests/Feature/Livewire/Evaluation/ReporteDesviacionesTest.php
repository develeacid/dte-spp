<?php

namespace Tests\Feature\Livewire\Evaluation;

use App\Livewire\Evaluation\ReporteDesviaciones;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReporteDesviacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function usuario_sin_permiso_no_accede(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $this->get(route('evaluation.desviaciones'))->assertForbidden();
    }

    #[Test]
    public function planeador_renderiza_sin_errores(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');
        $this->actingAs($user);

        Livewire::test(ReporteDesviaciones::class)
            ->assertOk()
            ->assertSee('Reporte de Desviaciones');
    }
}
