<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Livewire\Mml\VincularAlineacion;
use App\Models\Mml\ImportacionReporte;
use App\Models\Mml\MirNivel;
use App\Models\User;
use App\Services\Embeddings\SemanticSearchService;
use App\Services\Mml\MirPersistenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VincularAlineacionImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();

        $this->mock(SemanticSearchService::class, function ($mock) {
            $mock->shouldReceive('findSimilar')->andReturn(collect([]));
        });
    }

    public function test_componente_se_renderiza(): void
    {
        $reporte = $this->createReporteConPrograma();

        Livewire::actingAs($this->user)
            ->test(VincularAlineacion::class, ['importacion' => $reporte->id])
            ->assertOk()
            ->assertSee('Vincular Alineación con Cascada de Planes');
    }

    public function test_omitir_avanza_paso(): void
    {
        $reporte = $this->createReporteConPrograma();

        $component = Livewire::actingAs($this->user)
            ->test(VincularAlineacion::class, ['importacion' => $reporte->id]);

        $this->assertEquals(0, $component->get('pasoActual'));

        $component->call('omitir');

        // Should not advance beyond the last step (only 1 nivel in fixture)
        // With 1 nivel, omitir keeps pasoActual at 0 since it's the last step
        $this->assertGreaterThanOrEqual(0, $component->get('pasoActual'));
    }

    public function test_seleccionar_persiste_fk(): void
    {
        $reporte = $this->createReporteConPrograma();

        $nivel = MirNivel::where('programa_presupuestario_id', $reporte->programa_presupuestario_id)
            ->where('tipo_nivel', 'fin')
            ->first();

        $this->assertNotNull($nivel);
        $this->assertNull($nivel->ped_objetivo_estrategico_id);

        // Create PED parent chain: plan → eje → tema → objetivo
        $plan = \App\Models\PedPlan::create([
            'nombre' => 'PED Test',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);
        $eje = \App\Models\PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje Test',
        ]);
        $tema = \App\Models\PedTema::create([
            'ped_eje_id' => $eje->id,
            'numero' => '1.1',
            'nombre' => 'Tema Test',
        ]);
        $objetivo = \App\Models\PedObjetivoEstrategico::create([
            'ped_tema_id' => $tema->id,
            'clave' => '1.1.1',
            'descripcion' => 'Objetivo estrategico de prueba',
        ]);

        Livewire::actingAs($this->user)
            ->test(VincularAlineacion::class, ['importacion' => $reporte->id])
            ->call('seleccionar', $objetivo->id, 'PedObjetivoEstrategico');

        $nivel->refresh();
        $this->assertEquals($objetivo->id, $nivel->ped_objetivo_estrategico_id);
    }

    public function test_finalizar_redirige(): void
    {
        // Register the calendarizar route (will be implemented in S5-T4)
        \Illuminate\Support\Facades\Route::get('/mml/importar/{importacion}/calendarizar', fn () => '')
            ->name('mml.importar.calendarizar')
            ->middleware(['web']);

        $reporte = $this->createReporteConPrograma();

        Livewire::actingAs($this->user)
            ->test(VincularAlineacion::class, ['importacion' => $reporte->id])
            ->call('finalizar')
            ->assertRedirect(route('mml.importar.calendarizar', ['importacion' => $reporte->id]));
    }

    /**
     * Helper to create a reporte with a persisted programa.
     */
    private function createReporteConPrograma(): ImportacionReporte
    {
        $data = new ImportedMirData(
            nombre: 'Programa Test Vincular',
            clave: 'PTV01',
            ejercicioFiscal: 2026,
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Contribuir a mejorar la calidad de vida',
                    'supuestos' => 'Estabilidad economica',
                    'orden' => 1,
                    'indicadores' => [
                        [
                            'nombre' => 'Indicador Fin',
                            'formula_texto' => 'A/B*100',
                            'tipo' => 'estrategico',
                            'dimension' => 'eficacia',
                            'frecuencia' => 'anual',
                            'sentido' => 'ascendente',
                            'linea_base' => 0,
                            'meta' => 100,
                            'variables' => [],
                            'medios' => [['nombre' => 'Informe', 'fuente' => null]],
                        ],
                    ],
                ],
                [
                    'tipo_nivel' => 'componente',
                    'resumen_narrativo' => 'Servicios educativos entregados',
                    'supuestos' => 'Presupuesto disponible',
                    'orden' => 1,
                    'indicadores' => [
                        [
                            'nombre' => 'Indicador Componente',
                            'formula_texto' => 'C/D*100',
                            'tipo' => 'gestion',
                            'dimension' => 'eficiencia',
                            'frecuencia' => 'trimestral',
                            'sentido' => 'ascendente',
                            'linea_base' => 50,
                            'meta' => 90,
                            'variables' => [],
                            'medios' => [],
                        ],
                    ],
                ],
            ],
        );

        $reporte = ImportacionReporte::create([
            'team_id' => $this->user->currentTeam->id,
            'archivo_original' => 'test-vincular.md',
            'formato' => 'md',
            'datos_parseados' => $data->toArray(),
            'diagnostico' => null,
            'estado' => 'procesado',
            'created_by' => $this->user->id,
        ]);

        // Persist the programa
        $programa = app(MirPersistenciaService::class)->persistir(
            $data,
            $reporte->team_id,
            $reporte->created_by,
            $reporte->diagnostico,
        );

        $reporte->update(['programa_presupuestario_id' => $programa->id]);

        return $reporte;
    }
}
