<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Livewire\Mml\AlineacionEstrategica;
use App\Models\Mml\MirNivel;
use App\Models\PedEje;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlineacionEstrategicaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    private function createPedChain(): PedObjetivoEstrategico
    {
        $plan = PedPlan::create(['nombre' => 'PED 2024-2030', 'periodo_inicio' => 2024, 'periodo_fin' => 2030]);
        $eje = PedEje::create(['ped_plan_id' => $plan->id, 'numero' => 1, 'nombre' => 'Eje 1']);
        $tema = PedTema::create(['ped_eje_id' => $eje->id, 'numero' => '1', 'nombre' => 'Educación', 'descripcion' => 'Tema educación']);
        return PedObjetivoEstrategico::create([
            'ped_tema_id' => $tema->id,
            'clave' => '1',
            'descripcion' => 'Mejorar la calidad educativa del estado',
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 6');
    }

    public function test_puede_guardar_alineacion_con_ped(): void
    {
        $objetivo = $this->createPedChain();

        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->set('ejeId', $objetivo->tema->eje->id)
            ->set('temaId', $objetivo->tema->id)
            ->set('objetivoEstrategicoId', $objetivo->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $fin = $this->programa->mirNiveles()->where('tipo_nivel', 'fin')->first();
        $this->assertNotNull($fin);
        $this->assertEquals($objetivo->id, $fin->ped_objetivo_estrategico_id);
    }

    public function test_no_permite_guardar_sin_objetivo(): void
    {
        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->call('guardar')
            ->assertHasErrors('objetivoEstrategicoId');
    }

    public function test_selects_dependientes_se_limpian_al_cambiar_eje(): void
    {
        $objetivo = $this->createPedChain();

        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->set('ejeId', $objetivo->tema->eje->id)
            ->set('temaId', $objetivo->tema->id)
            ->set('objetivoEstrategicoId', $objetivo->id)
            ->set('ejeId', null)
            ->assertSet('temaId', null)
            ->assertSet('objetivoEstrategicoId', null);
    }

    public function test_finalizar_planeacion_creates_mir_and_redirects(): void
    {
        $objetivo = $this->createPedChain();

        // Setup: poblacion
        $this->programa->poblacion()->create([
            'referencia_cantidad' => 10000,
            'potencial_cantidad' => 5000,
            'objetivo_cantidad' => 2000,
            'unidad_medida' => 'personas',
            'anio_ejercicio' => 2026,
        ]);

        // Create objectives tree with required nodes
        $arbolObj = \App\Models\Mml\Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => 'objetivos',
        ]);
        $objetivoCentral = \App\Models\Mml\ArbolNodo::create([
            'arbol_id' => $arbolObj->id,
            'tipo_nodo' => 'objetivo_central',
            'descripcion' => 'Objetivo central de prueba',
            'orden' => 1,
        ]);
        $medioDirecto = \App\Models\Mml\ArbolNodo::create([
            'arbol_id' => $arbolObj->id,
            'parent_id' => $objetivoCentral->id,
            'tipo_nodo' => 'medio_directo',
            'descripcion' => 'Medio directo de prueba',
            'orden' => 1,
        ]);

        // Create selected alternative
        $alternativa = \App\Models\Mml\Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alternativa 1',
            'seleccionada' => true,
        ]);
        $alternativa->nodos()->attach($medioDirecto->id);

        // Create PED alignment on FIN level
        $this->programa->mirNiveles()->create([
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'FIN placeholder',
            'orden' => 1,
            'ped_objetivo_estrategico_id' => $objetivo->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->set('objetivoEstrategicoId', $objetivo->id)
            ->call('finalizarPlaneacion')
            ->assertRedirect(route('mml.mir', $this->programa));

        $this->programa->refresh();
        $this->assertNotNull($this->programa->planeacion_completada_at);
    }

    public function test_carga_alineacion_existente_al_montar(): void
    {
        $objetivo = $this->createPedChain();

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'ped_objetivo_estrategico_id' => $objetivo->id,
            'orden' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->assertSet('ejeId', $objetivo->tema->eje->id)
            ->assertSet('temaId', $objetivo->tema->id)
            ->assertSet('objetivoEstrategicoId', $objetivo->id);
    }
}
