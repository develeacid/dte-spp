<?php

namespace Tests\Feature\Evaluation;

use App\Enums\TipoNivelMir;
use App\Models\Evaluation\AnexoTransversal;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\AnexosTransversalesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelosEvaluacionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Eval',
            'clave' => 'PE-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_crear_evaluacion_programa(): void
    {
        $evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 85.5432,
            'desglose_niveles' => ['fin' => 90.0, 'proposito' => 85.0, 'componentes' => 80.0, 'actividades' => 75.0],
            'conteo_semaforos' => ['verde' => 5, 'amarillo' => 2, 'rojo' => 1, 'sin_dato' => 0],
            'indicadores_evaluados' => 8,
            'indicadores_no_evaluados' => 2,
            'configuracion_calculo' => ['peso_fin' => 0.25, 'peso_proposito' => 0.25],
            'analisis_ia' => 'El programa presenta buen desempeño.',
            'calculado_por' => $this->user->id,
        ]);

        $this->assertDatabaseHas('evaluaciones_programa', [
            'id' => $evaluacion->id,
            'ejercicio_fiscal' => 2026,
            'indicadores_evaluados' => 8,
        ]);

        $this->assertIsArray($evaluacion->desglose_niveles);
        $this->assertIsArray($evaluacion->conteo_semaforos);
        $this->assertIsArray($evaluacion->configuracion_calculo);
        $this->assertEquals(90.0, $evaluacion->desglose_niveles['fin']);
        $this->assertEquals(5, $evaluacion->conteo_semaforos['verde']);
    }

    public function test_evaluacion_relacion_programa(): void
    {
        $evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026,
            'calculado_por' => $this->user->id,
        ]);

        $this->assertEquals($this->programa->id, $evaluacion->programa->id);
        $this->assertEquals($this->user->id, $evaluacion->calculador->id);
    }

    public function test_crear_anexo_transversal(): void
    {
        $anexo = AnexoTransversal::create([
            'nombre' => 'Igualdad de Género',
            'clave' => 'genero',
            'descripcion' => 'Enfoque de género',
            'activo' => true,
            'orden' => 1,
        ]);

        $this->assertDatabaseHas('anexos_transversales', [
            'clave' => 'genero',
            'activo' => true,
        ]);

        $this->assertTrue($anexo->activo);
        $this->assertEquals(1, $anexo->orden);
    }

    public function test_seeder_crea_4_anexos(): void
    {
        $this->seed(AnexosTransversalesSeeder::class);

        $this->assertDatabaseCount('anexos_transversales', 4);
        $this->assertDatabaseHas('anexos_transversales', ['clave' => 'genero']);
        $this->assertDatabaseHas('anexos_transversales', ['clave' => 'nna']);
        $this->assertDatabaseHas('anexos_transversales', ['clave' => 'cambio_climatico']);
        $this->assertDatabaseHas('anexos_transversales', ['clave' => 'anticorrupcion']);
    }

    public function test_indicador_anexos_transversales_m2m(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test',
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Tasa de cobertura',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $anexo1 = AnexoTransversal::create([
            'nombre' => 'Igualdad de Género', 'clave' => 'genero', 'orden' => 1,
        ]);
        $anexo2 = AnexoTransversal::create([
            'nombre' => 'Anticorrupción', 'clave' => 'anticorrupcion', 'orden' => 2,
        ]);

        $indicador->anexosTransversales()->attach([$anexo1->id, $anexo2->id]);

        $this->assertEquals(2, $indicador->anexosTransversales()->count());
        $this->assertTrue($indicador->anexosTransversales->contains($anexo1));
        $this->assertTrue($indicador->anexosTransversales->contains($anexo2));

        // Test inverse relation
        $this->assertEquals(1, $anexo1->indicadores()->count());
    }

    public function test_anexo_scope_activos(): void
    {
        AnexoTransversal::create(['nombre' => 'C', 'clave' => 'c', 'orden' => 3, 'activo' => true]);
        AnexoTransversal::create(['nombre' => 'A', 'clave' => 'a', 'orden' => 1, 'activo' => true]);
        AnexoTransversal::create(['nombre' => 'Inactivo', 'clave' => 'x', 'orden' => 2, 'activo' => false]);
        AnexoTransversal::create(['nombre' => 'B', 'clave' => 'b', 'orden' => 2, 'activo' => true]);

        $activos = AnexoTransversal::activos()->get();

        $this->assertCount(3, $activos);
        $this->assertEquals('A', $activos[0]->nombre);
        $this->assertEquals('B', $activos[1]->nombre);
        $this->assertEquals('C', $activos[2]->nombre);
    }
}
