<?php

namespace Tests\Feature\Evaluation;

use App\Enums\EstadoAvance;
use App\Enums\TipoNivelMir;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\Evaluation\LogicaVerticalService;
use App\Services\Llm\LlmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LogicaVerticalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private EvaluacionPrograma $evaluacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->withPersonalTeam()->create();

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Logica Vertical',
            'clave' => 'PLV-001',
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);

        // Create MIR niveles with indicators and avances
        $this->crearNivelConIndicador(TipoNivelMir::FIN, 100, 90, 'verde', 'El PIB crece');
        $this->crearNivelConIndicador(TipoNivelMir::PROPOSITO, 100, 40, 'rojo', 'Mercado estable');

        $this->evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 65.0,
            'desglose_niveles' => [],
            'conteo_semaforos' => ['verde' => 1, 'rojo' => 1, 'amarillo' => 0, 'sin_dato' => 0],
            'indicadores_evaluados' => 2,
            'indicadores_no_evaluados' => 0,
            'configuracion_calculo' => [],
        ]);
    }

    public function test_mock_cuando_no_hay_api_key(): void
    {
        config(['llm.api_key' => '']);

        $service = app(LogicaVerticalService::class);
        $resultado = $service->analizar($this->evaluacion);

        $this->assertNotNull($resultado);
        $this->assertStringContainsString('Configure LLM_API_KEY', $resultado);
    }

    public function test_genera_analisis_con_mock(): void
    {
        config(['llm.api_key' => 'test-key']);

        $mockLlm = Mockery::mock(LlmService::class);
        $mockLlm->shouldReceive('suggest')
            ->once()
            ->withArgs(function (string $prompt) {
                // Verify prompt includes semaforo data
                return str_contains($prompt, 'PLV-001')
                    && str_contains($prompt, 'verde')
                    && str_contains($prompt, 'rojo')
                    && str_contains($prompt, 'Fin')
                    && str_contains($prompt, 'RUPTURAS');
            })
            ->andReturn('1. Ruptura detectada entre Fin y Proposito: tipo DISENO.');

        $this->app->instance(LlmService::class, $mockLlm);

        $service = app(LogicaVerticalService::class);
        $resultado = $service->analizar($this->evaluacion);

        $this->assertEquals('1. Ruptura detectada entre Fin y Proposito: tipo DISENO.', $resultado);
    }

    public function test_guarda_analisis_en_evaluacion(): void
    {
        config(['llm.api_key' => 'test-key']);

        $mockLlm = Mockery::mock(LlmService::class);
        $mockLlm->shouldReceive('suggest')
            ->once()
            ->andReturn('Analisis guardado correctamente.');

        $this->app->instance(LlmService::class, $mockLlm);

        $service = app(LogicaVerticalService::class);
        $service->analizar($this->evaluacion);

        $this->evaluacion->refresh();
        $this->assertEquals('Analisis guardado correctamente.', $this->evaluacion->analisis_ia);
    }

    public function test_retorna_null_si_llm_falla(): void
    {
        config(['llm.api_key' => 'test-key']);

        $mockLlm = Mockery::mock(LlmService::class);
        $mockLlm->shouldReceive('suggest')
            ->once()
            ->andThrow(new \RuntimeException('API connection failed'));

        $this->app->instance(LlmService::class, $mockLlm);

        $service = app(LogicaVerticalService::class);
        $resultado = $service->analizar($this->evaluacion);

        $this->assertNull($resultado);
    }

    // --- Helper ---

    private function crearNivelConIndicador(
        TipoNivelMir $tipoNivel,
        float $meta,
        float $resultado,
        string $semaforo,
        ?string $supuestos = null,
    ): void {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => $tipoNivel->value,
            'resumen_narrativo' => $tipoNivel->label() . ' test',
            'supuestos' => $supuestos,
            'orden' => $tipoNivel->orden(),
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => "Indicador {$tipoNivel->value}",
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => $meta,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => $meta / 4,
            'ejercicio_fiscal' => 2026,
        ]);

        Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'resultado' => $resultado,
            'semaforo_calculado' => $semaforo,
            'estado' => EstadoAvance::APROBADO->value,
        ]);
    }
}
