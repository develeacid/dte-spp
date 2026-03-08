<?php

namespace Tests\Feature\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ExtraccionVariablesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private Indicador $indicador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin',
            'orden' => 1,
        ]);

        $proposito = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Propósito',
            'orden' => 1,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $proposito->id,
            'nombre' => 'Tasa de cobertura',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'formula_texto' => '(Alumnos inscritos / Egresados secundaria) x 100',
            'orden' => 1,
        ]);
    }

    private function mockLlmForExtraction(array $variables): void
    {
        $mock = Mockery::mock(LlmServiceInterface::class);
        $mock->shouldReceive('suggest')
            ->andReturn(json_encode($variables));
        $this->app->instance(LlmServiceInterface::class, $mock);
    }

    public function test_extraer_variables_crea_registros(): void
    {
        $this->mockLlmForExtraction([
            ['simbolo' => 'A', 'nombre' => 'Alumnos inscritos', 'descripcion' => 'Total de alumnos'],
            ['simbolo' => 'B', 'nombre' => 'Egresados secundaria', 'descripcion' => 'Total de egresados'],
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('extraerVariables', $this->indicador->id);

        $this->assertDatabaseHas('indicador_variables', [
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'A',
            'nombre' => 'Alumnos inscritos',
        ]);
        $this->assertDatabaseHas('indicador_variables', [
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'B',
            'nombre' => 'Egresados secundaria',
        ]);
    }

    public function test_variables_tienen_simbolo_y_nombre(): void
    {
        $this->mockLlmForExtraction([
            ['simbolo' => 'A', 'nombre' => 'Variable A', 'descripcion' => 'Desc A'],
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('extraerVariables', $this->indicador->id);

        $variable = IndicadorVariable::where('indicador_id', $this->indicador->id)->first();
        $this->assertNotNull($variable);
        $this->assertEquals('A', $variable->simbolo);
        $this->assertEquals('Variable A', $variable->nombre);
    }

    public function test_agregar_variable_manual(): void
    {
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('agregarVariable', $this->indicador->id);

        $this->assertDatabaseHas('indicador_variables', [
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'A',
            'orden' => 1,
        ]);
    }

    public function test_guardar_variable(): void
    {
        $variable = IndicadorVariable::create([
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'A',
            'nombre' => 'Original',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarVariable', $variable->id, [
                'simbolo' => 'X',
                'nombre' => 'Editado',
            ]);

        $this->assertDatabaseHas('indicador_variables', [
            'id' => $variable->id,
            'simbolo' => 'X',
            'nombre' => 'Editado',
        ]);
    }

    public function test_eliminar_variable(): void
    {
        $variable = IndicadorVariable::create([
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'A',
            'nombre' => 'Para eliminar',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('eliminarVariable', $variable->id);

        $this->assertDatabaseMissing('indicador_variables', ['id' => $variable->id]);
    }

    public function test_reextraccion_limpia_variables_anteriores(): void
    {
        IndicadorVariable::create([
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'OLD',
            'nombre' => 'Vieja',
            'orden' => 1,
        ]);

        $this->mockLlmForExtraction([
            ['simbolo' => 'A', 'nombre' => 'Nueva', 'descripcion' => ''],
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('extraerVariables', $this->indicador->id);

        $this->assertDatabaseMissing('indicador_variables', ['simbolo' => 'OLD']);
        $this->assertDatabaseHas('indicador_variables', ['simbolo' => 'A', 'nombre' => 'Nueva']);
    }

    public function test_no_extrae_sin_formula(): void
    {
        $this->indicador->update(['formula_texto' => '']);

        $mock = Mockery::mock(LlmServiceInterface::class);
        $mock->shouldNotReceive('suggest');
        $this->app->instance(LlmServiceInterface::class, $mock);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('extraerVariables', $this->indicador->id);

        $this->assertEquals(0, IndicadorVariable::where('indicador_id', $this->indicador->id)->count());
    }
}
