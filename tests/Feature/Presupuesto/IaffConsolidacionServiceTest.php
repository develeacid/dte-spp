<?php

namespace Tests\Feature\Presupuesto;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Evaluation\Asm;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\Presupuesto\IaffConsolidacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IaffConsolidacionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgramaPresupuestario $programa;

    private Indicador $indicador;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PC-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);
        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Cobertura',
            'formula_texto' => 'A', 'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100, 'activo_seguimiento' => true, 'orden' => 1,
        ]);
    }

    private function avanceConSemaforo(string $semaforo, int $periodo, int $ejercicio): Avance
    {
        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => $periodo, 'meta_periodo' => 100,
            'ejercicio_fiscal' => $ejercicio, 'activo' => true,
        ]);

        return Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'semaforo_calculado' => $semaforo,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'justificacion_final' => "obs {$semaforo} {$periodo}",
            'capturado_por' => $this->user->id,
        ]);
    }

    public function test_consolida_asms_del_programa(): void
    {
        Asm::create([
            'programa_presupuestario_id' => $this->programa->id,
            'descripcion_aspecto' => 'Mejorar focalización',
            'accion_mejora' => 'Revisar criterios',
            'tipo_plazo' => 'corto', 'tipo_accion' => 'operativo',
            'responsable_id' => $this->user->id,
            'area_responsable' => 'Dirección de Planeación',
            'fecha_compromiso' => '2026-09-30',
            'status' => 'pendiente',
        ]);

        $resultado = app(IaffConsolidacionService::class)->consolidar($this->programa, 2026);

        $this->assertCount(1, $resultado['asms']);
    }

    public function test_consolida_solo_observaciones_con_semaforo_distinto_de_verde_del_ejercicio(): void
    {
        $this->avanceConSemaforo('rojo', 1, 2026);
        $this->avanceConSemaforo('amarillo', 2, 2026);
        $this->avanceConSemaforo('verde', 3, 2026);     // no cuenta
        $this->avanceConSemaforo('rojo', 1, 2025);       // otro ejercicio, no cuenta

        $resultado = app(IaffConsolidacionService::class)->consolidar($this->programa, 2026);

        $this->assertCount(2, $resultado['observaciones']);
    }

    public function test_semaforo_ajustado_tiene_prioridad_sobre_calculado(): void
    {
        $avance = $this->avanceConSemaforo('rojo', 1, 2026);
        $avance->update(['semaforo_ajustado' => 'verde']); // ajustado a verde => no cuenta

        $resultado = app(IaffConsolidacionService::class)->consolidar($this->programa, 2026);

        $this->assertCount(0, $resultado['observaciones']);
    }
}
