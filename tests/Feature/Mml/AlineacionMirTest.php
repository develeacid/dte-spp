<?php

namespace Tests\Feature\Mml;

use App\DTOs\SimilarityResult;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\MirNivel;
use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Embeddings\SemanticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AlineacionMirTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private MirNivel $fin;
    private MirNivel $componente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Contribuir a mejorar la calidad educativa',
            'orden' => 1,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Población beneficiada',
            'orden' => 1,
        ]);

        $this->componente = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Becas entregadas',
            'orden' => 1,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $this->componente->id,
            'resumen_narrativo' => 'Registro de beneficiarios',
            'orden' => 1,
        ]);
    }

    private function createPedChain(): PedObjetivoEstrategico
    {
        $plan = PedPlan::create(['nombre' => 'PED 2024-2030', 'periodo_inicio' => 2024, 'periodo_fin' => 2030]);
        $eje = PedEje::create(['ped_plan_id' => $plan->id, 'numero' => 1, 'nombre' => 'Eje 1']);
        $tema = PedTema::create(['ped_eje_id' => $eje->id, 'numero' => '1', 'nombre' => 'Educación', 'descripcion' => 'Tema educación']);
        $objetivo = PedObjetivoEstrategico::create([
            'ped_tema_id' => $tema->id,
            'clave' => '1',
            'descripcion' => 'Mejorar la calidad educativa del estado',
        ]);

        return $objetivo;
    }

    private function createLineaAccion(PedObjetivoEstrategico $objetivo): PedLineaAccion
    {
        $estrategia = PedEstrategia::create([
            'ped_objetivo_estrategico_id' => $objetivo->id,
            'clave' => '1',
            'descripcion' => 'Estrategia educativa',
        ]);

        return PedLineaAccion::create([
            'ped_estrategia_id' => $estrategia->id,
            'clave' => '1',
            'descripcion' => 'Ampliar cobertura de becas',
        ]);
    }

    private function mockSemanticSearch(array $results): void
    {
        $mock = Mockery::mock(SemanticSearchService::class);
        $mock->shouldReceive('findSimilar')
            ->andReturn(collect($results));
        $this->app->instance(SemanticSearchService::class, $mock);
    }

    public function test_buscar_alineacion_retorna_sugerencias_para_fin(): void
    {
        $objetivo = $this->createPedChain();

        $this->mockSemanticSearch([
            new SimilarityResult(model: $objetivo, score: 0.92, distance: 0.08),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('buscarAlineacion', $this->fin->id);

        $sugerencias = $component->get('sugerenciasAlineacion');
        $this->assertCount(1, $sugerencias);
        $this->assertEquals('PedObjetivoEstrategico', $sugerencias[0]['tipo']);
        $this->assertEquals(92.0, $sugerencias[0]['score']);
        $this->assertEquals($objetivo->id, $sugerencias[0]['id']);
    }

    public function test_buscar_alineacion_retorna_lineas_accion_para_componente(): void
    {
        $objetivo = $this->createPedChain();
        $linea = $this->createLineaAccion($objetivo);

        $this->mockSemanticSearch([
            new SimilarityResult(model: $linea, score: 0.85, distance: 0.15),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('buscarAlineacion', $this->componente->id);

        $sugerencias = $component->get('sugerenciasAlineacion');
        $this->assertCount(1, $sugerencias);
        $this->assertEquals('PedLineaAccion', $sugerencias[0]['tipo']);
    }

    public function test_seleccionar_alineacion_guarda_fk_objetivo_estrategico(): void
    {
        $objetivo = $this->createPedChain();

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('seleccionarAlineacion', $this->fin->id, 'PedObjetivoEstrategico', $objetivo->id);

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $this->fin->id,
            'ped_objetivo_estrategico_id' => $objetivo->id,
        ]);
    }

    public function test_seleccionar_alineacion_guarda_fk_linea_accion(): void
    {
        $objetivo = $this->createPedChain();
        $linea = $this->createLineaAccion($objetivo);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('seleccionarAlineacion', $this->componente->id, 'PedLineaAccion', $linea->id);

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $this->componente->id,
            'ped_linea_accion_id' => $linea->id,
        ]);
    }

    public function test_seleccionar_alineacion_limpia_sugerencias(): void
    {
        $objetivo = $this->createPedChain();

        $this->mockSemanticSearch([
            new SimilarityResult(model: $objetivo, score: 0.92, distance: 0.08),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('buscarAlineacion', $this->fin->id)
            ->assertSet('nivelAlineacionActivo', $this->fin->id)
            ->call('seleccionarAlineacion', $this->fin->id, 'PedObjetivoEstrategico', $objetivo->id)
            ->assertSet('nivelAlineacionActivo', null)
            ->assertSet('sugerenciasAlineacion', []);
    }

    public function test_buscar_alineacion_sin_resumen_no_busca(): void
    {
        $nivelVacio = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => '',
            'orden' => 2,
        ]);

        // Should not call SemanticSearchService at all
        $mock = Mockery::mock(SemanticSearchService::class);
        $mock->shouldNotReceive('findSimilar');
        $this->app->instance(SemanticSearchService::class, $mock);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('buscarAlineacion', $nivelVacio->id)
            ->assertSet('sugerenciasAlineacion', []);
    }
}
