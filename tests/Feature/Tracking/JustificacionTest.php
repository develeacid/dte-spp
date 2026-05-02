<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\Llm\LlmService;
use App\Services\Tracking\JustificacionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JustificacionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private MirNivel $nivel;

    private Indicador $indicador;

    private MetaPeriodo $metaPeriodo;

    private Avance $avance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Los beneficiarios incrementan su calidad de vida',
            'supuestos' => 'Las condiciones economicas se mantienen estables. Los beneficiarios participan activamente.',
            'orden' => 1,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Tasa de cobertura',
            'formula_texto' => '(A/B) * 100',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $this->metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 80,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        $this->avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 50,
            'semaforo_calculado' => 'rojo',
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);
    }

    public function test_genera_justificacion_con_supuestos(): void
    {
        $mock = $this->mock(LlmService::class);

        $mock->shouldReceive('renderPrompt')
            ->once()
            ->withArgs(function (string $view, array $data) {
                return $view === 'prompts.tracking.justificar-avance'
                    && $data['supuestos'] === 'Las condiciones economicas se mantienen estables. Los beneficiarios participan activamente.';
            })
            ->andReturn('rendered prompt with supuestos');

        $mock->shouldReceive('suggest')
            ->once()
            ->with('rendered prompt with supuestos')
            ->andReturn('Justificacion generada por IA');

        $service = app(JustificacionService::class);
        $result = $service->generar($this->avance);

        $this->assertEquals('Justificacion generada por IA', $result);
    }

    public function test_genera_justificacion_sin_supuestos(): void
    {
        $this->nivel->update(['supuestos' => null]);
        $this->avance->refresh();

        $mock = $this->mock(LlmService::class);

        $mock->shouldReceive('renderPrompt')
            ->once()
            ->withArgs(function (string $view, array $data) {
                return $data['supuestos'] === null;
            })
            ->andReturn('rendered prompt without supuestos');

        $mock->shouldReceive('suggest')
            ->once()
            ->andReturn('Justificacion sin supuestos');

        $service = app(JustificacionService::class);
        $result = $service->generar($this->avance);

        $this->assertEquals('Justificacion sin supuestos', $result);
    }

    public function test_guarda_justificacion_ia_y_final(): void
    {
        $this->actingAs($this->user);

        $this->avance->update([
            'justificacion_ia' => 'Borrador IA',
            'justificacion_final' => 'Version editada por usuario',
        ]);

        $this->avance->refresh();

        $this->assertEquals('Borrador IA', $this->avance->justificacion_ia);
        $this->assertEquals('Version editada por usuario', $this->avance->justificacion_final);
    }

    public function test_retorna_null_si_llm_falla(): void
    {
        $mock = $this->mock(LlmService::class);

        $mock->shouldReceive('renderPrompt')
            ->once()
            ->andReturn('rendered prompt');

        $mock->shouldReceive('suggest')
            ->once()
            ->andThrow(new \RuntimeException('API error'));

        $service = app(JustificacionService::class);
        $result = $service->generar($this->avance);

        $this->assertNull($result);
    }

    public function test_incluye_historial(): void
    {
        // Create a previous avance for the same indicator
        $metaPeriodoAnterior = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 0,
            'meta_periodo' => 70,
            'ejercicio_fiscal' => 2026,
            'activo' => false,
        ]);

        Avance::create([
            'meta_periodo_id' => $metaPeriodoAnterior->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 65,
            'semaforo_calculado' => 'amarillo',
            'estado' => EstadoAvance::APROBADO->value,
            'capturado_por' => $this->user->id,
        ]);

        $mock = $this->mock(LlmService::class);

        $mock->shouldReceive('renderPrompt')
            ->once()
            ->withArgs(function (string $view, array $data) {
                return count($data['historial']) === 1
                    && $data['historial'][0]['resultado'] == 65
                    && $data['historial'][0]['semaforo'] === 'amarillo';
            })
            ->andReturn('prompt with history');

        $mock->shouldReceive('suggest')
            ->once()
            ->andReturn('Justificacion con historial');

        $service = app(JustificacionService::class);
        $result = $service->generar($this->avance);

        $this->assertEquals('Justificacion con historial', $result);
    }
}
