<?php

namespace Tests\Feature\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\CremaaValidacion;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ValidacionCremaaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    private MirNivel $nivel;

    private Indicador $indicador;

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
            'resumen_narrativo' => 'Contribuir a la mejora educativa',
            'orden' => 1,
        ]);
        $this->indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Tasa de cobertura',
            'formula_texto' => '(A/B)*100',
            'tipo' => TipoIndicador::ESTRATEGICO->value,
            'dimension' => DimensionIndicador::EFICACIA->value,
            'frecuencia' => FrecuenciaMedicion::ANUAL->value,
            'orden' => 1,
        ]);
    }

    private function mockLlmCremaa(array $overrides = []): void
    {
        $defaults = [
            'claro' => true, 'claro_observacion' => '',
            'relevante' => true, 'relevante_observacion' => '',
            'economico' => false, 'economico_observacion' => 'Costoso de medir',
            'monitoreable' => true, 'monitoreable_observacion' => '',
            'adecuado' => true, 'adecuado_observacion' => '',
            'aportante' => true, 'aportante_observacion' => '',
        ];

        $data = array_merge($defaults, $overrides);

        $mock = Mockery::mock(LlmServiceInterface::class);
        $mock->shouldReceive('suggest')
            ->once()
            ->andReturn(json_encode($data));
        $this->app->instance(LlmServiceInterface::class, $mock);
    }

    public function test_validar_cremaa_crea_registro(): void
    {
        $this->mockLlmCremaa();

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarCremaa', $this->indicador->id);

        $this->assertDatabaseHas('cremaa_validaciones', [
            'indicador_id' => $this->indicador->id,
        ]);
    }

    public function test_resultado_tiene_6_campos_boolean(): void
    {
        $this->mockLlmCremaa();

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarCremaa', $this->indicador->id);

        $cremaa = CremaaValidacion::where('indicador_id', $this->indicador->id)->first();
        $this->assertTrue($cremaa->claro);
        $this->assertTrue($cremaa->relevante);
        $this->assertFalse($cremaa->economico);
        $this->assertTrue($cremaa->monitoreable);
        $this->assertTrue($cremaa->adecuado);
        $this->assertTrue($cremaa->aportante);
    }

    public function test_observaciones_se_guardan(): void
    {
        $this->mockLlmCremaa(['economico' => false, 'economico_observacion' => 'Requiere encuesta costosa']);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarCremaa', $this->indicador->id);

        $cremaa = CremaaValidacion::where('indicador_id', $this->indicador->id)->first();
        $this->assertEquals('Requiere encuesta costosa', $cremaa->economico_observacion);
    }

    public function test_no_bloquea_guardado_de_indicador(): void
    {
        // Create failed CREMAA
        CremaaValidacion::create([
            'indicador_id' => $this->indicador->id,
            'claro' => false,
            'relevante' => false,
            'economico' => false,
            'monitoreable' => false,
            'adecuado' => false,
            'aportante' => false,
        ]);

        // Can still edit indicator
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarIndicador', $this->indicador->id, [
                'nombre' => 'Nombre editado',
                'tipo' => 'estrategico',
                'dimension' => 'eficacia',
                'frecuencia' => 'anual',
            ]);

        $this->assertDatabaseHas('indicadores', [
            'id' => $this->indicador->id,
            'nombre' => 'Nombre editado',
        ]);
    }

    public function test_actualiza_validacion_existente(): void
    {
        CremaaValidacion::create([
            'indicador_id' => $this->indicador->id,
            'claro' => false,
            'relevante' => false,
            'economico' => false,
            'monitoreable' => false,
            'adecuado' => false,
            'aportante' => false,
        ]);

        $this->mockLlmCremaa([
            'claro' => true, 'relevante' => true, 'economico' => true,
            'monitoreable' => true, 'adecuado' => true, 'aportante' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarCremaa', $this->indicador->id);

        // Should still have only 1 record (upsert)
        $this->assertEquals(1, CremaaValidacion::where('indicador_id', $this->indicador->id)->count());

        $cremaa = CremaaValidacion::where('indicador_id', $this->indicador->id)->first();
        $this->assertTrue($cremaa->claro);
        $this->assertTrue($cremaa->relevante);
    }
}
