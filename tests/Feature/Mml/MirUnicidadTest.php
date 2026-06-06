<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\MirPersistenciaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class MirUnicidadTest extends TestCase
{
    use RefreshDatabase;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $user->currentTeam->id,
        ]);
    }

    /**
     * Invoke the private crearNivel guard against a given tipo.
     */
    private function crearNivel(TipoNivelMir $tipo): MirNivel
    {
        $service = new MirPersistenciaService;
        $method = new ReflectionMethod($service, 'crearNivel');
        $method->setAccessible(true);

        return $method->invoke($service, $this->programa, [
            'resumen_narrativo' => 'Texto',
            'orden' => 1,
        ], $tipo, null);
    }

    public function test_no_se_puede_crear_segundo_fin_para_el_mismo_programa(): void
    {
        $this->crearNivel(TipoNivelMir::FIN);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/FIN|Fin/');

        $this->crearNivel(TipoNivelMir::FIN);
    }

    public function test_no_se_puede_crear_segundo_proposito(): void
    {
        $this->crearNivel(TipoNivelMir::PROPOSITO);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/Prop\xc3\xb3sito|Proposito/');

        $this->crearNivel(TipoNivelMir::PROPOSITO);
    }

    public function test_si_se_permiten_multiples_componentes_y_actividades(): void
    {
        $c1 = $this->crearNivel(TipoNivelMir::COMPONENTE);
        $c2 = $this->crearNivel(TipoNivelMir::COMPONENTE);
        $a1 = $this->crearNivel(TipoNivelMir::ACTIVIDAD);
        $a2 = $this->crearNivel(TipoNivelMir::ACTIVIDAD);

        $this->assertNotEquals($c1->id, $c2->id);
        $this->assertNotEquals($a1->id, $a2->id);
        $this->assertEquals(4, $this->programa->mirNiveles()->count());
    }

    public function test_el_constraint_bd_bloquea_inserts_directos_de_segundo_fin(): void
    {
        \DB::table('mir_niveles')->insert([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Primer fin',
            'orden' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        \DB::table('mir_niveles')->insert([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Segundo fin',
            'orden' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_dos_programas_distintos_pueden_tener_cada_uno_su_fin(): void
    {
        $this->crearNivel(TipoNivelMir::FIN);

        $otro = ProgramaPresupuestario::create([
            'nombre' => 'Otro', 'clave' => 'PT-002',
            'team_id' => $this->programa->team_id,
        ]);

        $finOtro = MirNivel::create([
            'programa_presupuestario_id' => $otro->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin otro programa',
            'orden' => 1,
        ]);

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $finOtro->id,
            'programa_presupuestario_id' => $otro->id,
            'tipo_nivel' => 'fin',
        ]);
    }
}
