<?php

namespace Tests\Feature\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\SentidoIndicador;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\CatalogoUnidadMedida;
use App\Models\Mml\CremaaValidacion;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private MirNivel $nivel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $this->nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);
    }

    public function test_crear_indicador_con_todos_los_campos(): void
    {
        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Tasa de cobertura educativa',
            'formula_texto' => '(A/B)*100',
            'tipo' => TipoIndicador::ESTRATEGICO->value,
            'dimension' => DimensionIndicador::EFICACIA->value,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'linea_base' => 85.5000,
            'meta' => 90.0000,
            'orden' => 1,
        ]);

        $this->assertDatabaseHas('indicadores', [
            'nombre' => 'Tasa de cobertura educativa',
            'tipo' => 'estrategico',
        ]);
    }

    public function test_cast_enums(): void
    {
        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Test',
            'tipo' => TipoIndicador::GESTION->value,
            'dimension' => DimensionIndicador::CALIDAD->value,
            'frecuencia' => FrecuenciaMedicion::TRIMESTRAL->value,
            'sentido' => SentidoIndicador::DESCENDENTE->value,
            'orden' => 1,
        ]);

        $fresh = $indicador->fresh();
        $this->assertInstanceOf(TipoIndicador::class, $fresh->tipo);
        $this->assertInstanceOf(DimensionIndicador::class, $fresh->dimension);
        $this->assertInstanceOf(FrecuenciaMedicion::class, $fresh->frecuencia);
        $this->assertInstanceOf(SentidoIndicador::class, $fresh->sentido);
    }

    public function test_relacion_indicador_mir_nivel(): void
    {
        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Test',
            'tipo' => TipoIndicador::ESTRATEGICO->value,
            'dimension' => DimensionIndicador::EFICACIA->value,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'orden' => 1,
        ]);

        $this->assertEquals($this->nivel->id, $indicador->mirNivel->id);
        $this->assertTrue($this->nivel->indicadores->contains($indicador));
    }

    public function test_relacion_indicador_variables(): void
    {
        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Test',
            'tipo' => TipoIndicador::ESTRATEGICO->value,
            'dimension' => DimensionIndicador::EFICACIA->value,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'orden' => 1,
        ]);

        $variable = IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'A',
            'nombre' => 'Alumnos inscritos',
            'orden' => 1,
        ]);

        $this->assertEquals(1, $indicador->variables()->count());
        $this->assertEquals('A', $indicador->variables->first()->simbolo);
    }

    public function test_relacion_indicador_medios_verificacion(): void
    {
        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Test',
            'tipo' => TipoIndicador::ESTRATEGICO->value,
            'dimension' => DimensionIndicador::EFICACIA->value,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'orden' => 1,
        ]);

        MedioVerificacion::create([
            'indicador_id' => $indicador->id,
            'nombre' => 'Registro de inscripciones',
            'fuente' => 'Sistema escolar',
            'orden' => 1,
        ]);

        $this->assertEquals(1, $indicador->mediosVerificacion()->count());
    }

    public function test_relacion_indicador_cremaa_validacion(): void
    {
        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Test',
            'tipo' => TipoIndicador::ESTRATEGICO->value,
            'dimension' => DimensionIndicador::EFICACIA->value,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'orden' => 1,
        ]);

        CremaaValidacion::create([
            'indicador_id' => $indicador->id,
            'claro' => true,
            'claro_observacion' => 'El indicador es claro',
            'relevante' => true,
            'economico' => false,
            'economico_observacion' => 'Costoso de medir',
            'monitoreable' => true,
            'adecuado' => true,
            'aportante' => true,
        ]);

        $cremaa = $indicador->cremaaValidacion;
        $this->assertTrue($cremaa->claro);
        $this->assertFalse($cremaa->economico);
        $this->assertEquals('Costoso de medir', $cremaa->economico_observacion);
    }

    public function test_cascade_delete_mir_nivel_elimina_indicadores(): void
    {
        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Test',
            'tipo' => TipoIndicador::ESTRATEGICO->value,
            'dimension' => DimensionIndicador::EFICACIA->value,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'orden' => 1,
        ]);

        $this->nivel->delete();
        $this->assertDatabaseMissing('indicadores', ['id' => $indicador->id]);
    }

    public function test_catalogo_unidades_medida(): void
    {
        $unidad = CatalogoUnidadMedida::create([
            'clave' => 'PCT',
            'nombre' => 'Porcentaje',
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Test',
            'tipo' => TipoIndicador::ESTRATEGICO->value,
            'dimension' => DimensionIndicador::EFICACIA->value,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'unidad_medida_id' => $unidad->id,
            'orden' => 1,
        ]);

        $this->assertEquals('Porcentaje', $indicador->unidadMedida->nombre);
    }
}
