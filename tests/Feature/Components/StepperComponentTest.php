<?php

namespace Tests\Feature\Components;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StepperComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_stepper_renders_six_steps(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $user->currentTeam->id,
        ]);

        $view = $this->blade(
            '<x-mml.stepper :programa="$programa" :paso-actual="1" />',
            ['programa' => $programa]
        );

        $view->assertSee('Problema');
        $view->assertSee('Poblaciones');
    }

    public function test_stepper_marks_current_step_as_active(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $user->currentTeam->id,
        ]);

        $view = $this->blade(
            '<x-mml.stepper :programa="$programa" :paso-actual="3" />',
            ['programa' => $programa]
        );

        $view->assertSee('bg-blue-600');
    }
}
