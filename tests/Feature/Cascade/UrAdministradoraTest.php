<?php

namespace Tests\Feature\Cascade;

use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UrAdministradoraTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_programa_no_puede_tener_dos_teams_coordinadora(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $programa->equipos()->attach($teamA->id, ['rol' => 'coordinadora']);

        $this->expectException(QueryException::class);

        $programa->equipos()->attach($teamB->id, ['rol' => 'coordinadora']);
    }

    public function test_ur_administradora_devuelve_el_team_coordinador(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $coordinadora = Team::factory()->create();
        $coadyuvante = Team::factory()->create();

        $programa->equipos()->attach($coadyuvante->id, ['rol' => 'coadyuvante']);
        $programa->equipos()->attach($coordinadora->id, ['rol' => 'coordinadora']);

        $ur = $programa->urAdministradora();

        $this->assertInstanceOf(Team::class, $ur);
        $this->assertSame($coordinadora->id, $ur->id);
    }

    public function test_ur_administradora_devuelve_null_sin_coordinadora(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $coadyuvante = Team::factory()->create();

        $programa->equipos()->attach($coadyuvante->id, ['rol' => 'coadyuvante']);

        $this->assertNull($programa->urAdministradora());
    }

    public function test_multiples_coadyuvantes_permitidos_en_el_mismo_programa(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $coadA = Team::factory()->create();
        $coadB = Team::factory()->create();
        $coadC = Team::factory()->create();

        $programa->equipos()->attach($coadA->id, ['rol' => 'coadyuvante']);
        $programa->equipos()->attach($coadB->id, ['rol' => 'coadyuvante']);
        $programa->equipos()->attach($coadC->id, ['rol' => 'coadyuvante']);

        $this->assertSame(3, $programa->equipos()->wherePivot('rol', 'coadyuvante')->count());
    }

    public function test_dos_programas_pueden_tener_cada_uno_su_coordinadora_incluso_el_mismo_team(): void
    {
        $programaA = ProgramaPresupuestario::factory()->create();
        $programaB = ProgramaPresupuestario::factory()->create();
        $team = Team::factory()->create();

        $programaA->equipos()->attach($team->id, ['rol' => 'coordinadora']);
        $programaB->equipos()->attach($team->id, ['rol' => 'coordinadora']);

        $this->assertSame($team->id, $programaA->urAdministradora()->id);
        $this->assertSame($team->id, $programaB->urAdministradora()->id);
    }
}
