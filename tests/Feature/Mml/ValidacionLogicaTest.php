<?php

namespace Tests\Feature\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\MirLogicaValidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ValidacionLogicaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        // Create full MIR structure
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Contribuir a la mejora',
            'orden' => 1,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Población beneficiada',
            'orden' => 1,
        ]);
        $comp = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Becas entregadas',
            'orden' => 1,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $comp->id,
            'resumen_narrativo' => 'Registro de beneficiarios',
            'orden' => 1,
        ]);
    }

    private function mockLlmForLogica(array $verticalResponse, ?array $horizontalResponse = null): void
    {
        $mock = Mockery::mock(LlmServiceInterface::class);

        // Vertical call
        $mock->shouldReceive('suggest')
            ->andReturnUsing(function ($prompt) use ($verticalResponse, $horizontalResponse) {
                if (str_contains($prompt, 'cadena causal') || str_contains($prompt, 'lógica vertical')) {
                    return json_encode($verticalResponse);
                }
                return json_encode($horizontalResponse ?? ['coherente' => true, 'hallazgos' => []]);
            });

        $this->app->instance(LlmServiceInterface::class, $mock);
    }

    public function test_validar_mir_genera_reporte_con_hallazgos(): void
    {
        $this->mockLlmForLogica([
            'coherente' => false,
            'hallazgos' => [
                ['nivel' => 'componente', 'tipo' => 'advertencia', 'mensaje' => 'Salto lógico', 'detalle' => 'Test'],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarMirCompleta')
            ->assertSet('validacionLogicaEjecutada', true)
            ->assertSee('hallazgo');
    }

    public function test_hallazgos_clasificados_por_severidad(): void
    {
        $this->mockLlmForLogica([
            'coherente' => false,
            'hallazgos' => [
                ['nivel' => 'fin', 'tipo' => 'error_critico', 'mensaje' => 'Error grave', 'detalle' => 'Detalle 1'],
                ['nivel' => 'componente', 'tipo' => 'advertencia', 'mensaje' => 'Advertencia', 'detalle' => 'Detalle 2'],
                ['nivel' => 'actividad', 'tipo' => 'sugerencia', 'mensaje' => 'Mejora', 'detalle' => 'Detalle 3'],
            ],
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarMirCompleta');

        $hallazgos = $component->get('hallazgosLogica');
        $tipos = array_column($hallazgos, 'tipo');
        $this->assertContains('error_critico', $tipos);
        $this->assertContains('advertencia', $tipos);
        $this->assertContains('sugerencia', $tipos);
    }

    public function test_no_bloquea_guardado(): void
    {
        $fin = $this->programa->mirNiveles()->where('tipo_nivel', 'fin')->first();

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarNivel', $fin->id, 'resumen_narrativo', 'Editado después de validación');

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $fin->id,
            'resumen_narrativo' => 'Editado después de validación',
        ]);
    }

    public function test_servicio_retorna_estructura_correcta(): void
    {
        $this->mockLlmForLogica(
            ['coherente' => true, 'hallazgos' => []],
            ['coherente' => true, 'hallazgos' => []]
        );

        $servicio = app(MirLogicaValidacionService::class);
        $resultado = $servicio->validarCompleta($this->programa);

        $this->assertArrayHasKey('vertical', $resultado);
        $this->assertArrayHasKey('horizontal', $resultado);
        $this->assertArrayHasKey('hallazgos', $resultado);
    }
}
