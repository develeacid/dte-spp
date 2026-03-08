<?php

namespace Tests\Feature;

use App\Enums\EstadoPrograma;
use App\Enums\OrigenPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramaPresupuestarioMmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_programa_has_mml_fields(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa de Prueba',
            'clave' => 'PP-TEST-001',
            'team_id' => $team->id,
            'ejercicio_fiscal' => 2026,
            'origen' => OrigenPrograma::NUEVO->value,
            'estado' => EstadoPrograma::BORRADOR->value,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('programa_presupuestarios', [
            'id' => $programa->id,
            'team_id' => $team->id,
            'ejercicio_fiscal' => 2026,
            'origen' => 'nuevo',
            'estado' => 'borrador',
        ]);
    }

    public function test_scope_para_team(): void
    {
        $user1 = User::factory()->withPersonalTeam()->create();
        $user2 = User::factory()->withPersonalTeam()->create();

        ProgramaPresupuestario::create([
            'nombre' => 'Programa Team 1',
            'clave' => 'PT1',
            'team_id' => $user1->currentTeam->id,
        ]);
        ProgramaPresupuestario::create([
            'nombre' => 'Programa Team 2',
            'clave' => 'PT2',
            'team_id' => $user2->currentTeam->id,
        ]);

        $programas = ProgramaPresupuestario::paraTeam($user1->currentTeam->id)->get();

        $this->assertCount(1, $programas);
        $this->assertEquals('PT1', $programas->first()->clave);
    }

    public function test_scope_ejercicio(): void
    {
        $programa2026 = ProgramaPresupuestario::create([
            'nombre' => 'P 2026', 'clave' => 'P26', 'ejercicio_fiscal' => 2026,
        ]);
        $programa2027 = ProgramaPresupuestario::create([
            'nombre' => 'P 2027', 'clave' => 'P27', 'ejercicio_fiscal' => 2027,
        ]);

        $result = ProgramaPresupuestario::ejercicio(2026)->get();

        $this->assertCount(1, $result);
        $this->assertEquals('P26', $result->first()->clave);
    }

    public function test_relacion_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'T1', 'team_id' => $user->currentTeam->id,
        ]);

        $this->assertInstanceOf(Team::class, $programa->team);
    }

    public function test_relacion_creador(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'T1', 'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $programa->creador);
    }

    public function test_cast_enums(): void
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'TCAST',
            'origen' => OrigenPrograma::IMPORTADO->value,
            'estado' => EstadoPrograma::ACTIVO->value,
        ]);

        $programa->refresh();

        $this->assertInstanceOf(OrigenPrograma::class, $programa->origen);
        $this->assertInstanceOf(EstadoPrograma::class, $programa->estado);
    }
}
