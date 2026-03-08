<?php

namespace Tests\Feature\Evaluation;

use App\Enums\EstadoAvance;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\Evaluation\IndiceEficaciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndiceEficaciaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private IndiceEficaciaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test Eficacia',
            'clave' => 'PTE-001',
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);

        $this->service = app(IndiceEficaciaService::class);
    }

    public function test_calcula_indice_basico(): void
    {
        // FIN: 1 indicador, meta=100, resultado=80 → 80%
        $this->crearIndicadorConAvance(TipoNivelMir::FIN, 100, 80, 'verde');

        // PROPOSITO: 1 indicador, meta=200, resultado=100 → 50%
        $this->crearIndicadorConAvance(TipoNivelMir::PROPOSITO, 200, 100, 'amarillo');

        $evaluacion = $this->service->calcular($this->programa, 2026);

        // indice = 0.40*80 + 0.30*50 = 32 + 15 = 47
        $this->assertEquals(47.0, (float) $evaluacion->indice_eficacia);
        $this->assertEquals(2, $evaluacion->indicadores_evaluados);
        $this->assertEquals(0, $evaluacion->indicadores_no_evaluados);

        $this->assertEquals(80.0, $evaluacion->desglose_niveles['fin']['promedio']);
        $this->assertEquals(50.0, $evaluacion->desglose_niveles['proposito']['promedio']);
    }

    public function test_excluye_indicadores_sin_capturas(): void
    {
        // FIN con avance aprobado
        $this->crearIndicadorConAvance(TipoNivelMir::FIN, 100, 90, 'verde');

        // PROPOSITO sin avance (solo indicador + meta periodo, sin avance)
        $nivelP = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Propósito test',
            'orden' => 1,
        ]);

        $indicadorP = Indicador::create([
            'mir_nivel_id' => $nivelP->id,
            'nombre' => 'Indicador sin avance',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        MetaPeriodo::create([
            'indicador_id' => $indicadorP->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
        ]);

        $evaluacion = $this->service->calcular($this->programa, 2026);

        // Solo FIN cuenta: 0.40 * 90 = 36
        $this->assertEquals(36.0, (float) $evaluacion->indice_eficacia);
        $this->assertEquals(1, $evaluacion->indicadores_evaluados);
        $this->assertEquals(1, $evaluacion->indicadores_no_evaluados);
        $this->assertNull($evaluacion->desglose_niveles['proposito']['promedio']);
    }

    public function test_solo_avances_aprobados(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin test',
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador estados',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        // Avance EN_CAPTURA (should be ignored)
        $mp1 = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
        ]);

        Avance::create([
            'meta_periodo_id' => $mp1->id,
            'indicador_id' => $indicador->id,
            'resultado' => 90,
            'semaforo_calculado' => 'verde',
            'estado' => EstadoAvance::EN_CAPTURA->value,
        ]);

        // Avance EN_REVISION (should be ignored)
        $mp2 = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 2,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
        ]);

        Avance::create([
            'meta_periodo_id' => $mp2->id,
            'indicador_id' => $indicador->id,
            'resultado' => 80,
            'semaforo_calculado' => 'amarillo',
            'estado' => EstadoAvance::EN_REVISION->value,
        ]);

        $evaluacion = $this->service->calcular($this->programa, 2026);

        // No approved avances, so no_evaluado
        $this->assertEquals(0.0, (float) $evaluacion->indice_eficacia);
        $this->assertEquals(0, $evaluacion->indicadores_evaluados);
        $this->assertEquals(1, $evaluacion->indicadores_no_evaluados);
    }

    public function test_conteo_semaforos_correcto(): void
    {
        // Verde
        $this->crearIndicadorConAvance(TipoNivelMir::FIN, 100, 95, 'verde');
        // Amarillo
        $this->crearIndicadorConAvance(TipoNivelMir::PROPOSITO, 100, 70, 'amarillo');
        // Rojo
        $this->crearIndicadorConAvance(TipoNivelMir::COMPONENTE, 100, 30, 'rojo');

        // Sin avance (sin_dato)
        $nivelA = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'resumen_narrativo' => 'Actividad sin dato',
            'orden' => 1,
        ]);

        Indicador::create([
            'mir_nivel_id' => $nivelA->id,
            'nombre' => 'Sin avance',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 50,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $evaluacion = $this->service->calcular($this->programa, 2026);

        $this->assertEquals(1, $evaluacion->conteo_semaforos['verde']);
        $this->assertEquals(1, $evaluacion->conteo_semaforos['amarillo']);
        $this->assertEquals(1, $evaluacion->conteo_semaforos['rojo']);
        $this->assertEquals(1, $evaluacion->conteo_semaforos['sin_dato']);
    }

    public function test_pesos_desde_config(): void
    {
        // FIN: resultado=100, meta=100 → 100%
        $this->crearIndicadorConAvance(TipoNivelMir::FIN, 100, 100, 'verde');

        // PROPOSITO: resultado=100, meta=100 → 100%
        $this->crearIndicadorConAvance(TipoNivelMir::PROPOSITO, 100, 100, 'verde');

        // With default weights: 0.40*100 + 0.30*100 = 70
        $evaluacion1 = $this->service->calcular($this->programa, 2026);
        $this->assertEquals(70.0, (float) $evaluacion1->indice_eficacia);

        // Change config to equal weights
        config(['evaluation.pesos' => [
            'fin' => 0.50,
            'proposito' => 0.50,
            'componente' => 0.00,
            'actividad' => 0.00,
        ]]);

        $evaluacion2 = $this->service->calcular($this->programa, 2026);
        // 0.50*100 + 0.50*100 = 100
        $this->assertEquals(100.0, (float) $evaluacion2->indice_eficacia);
    }

    public function test_activo_seguimiento_false_excluido(): void
    {
        // Indicador activo con avance
        $this->crearIndicadorConAvance(TipoNivelMir::FIN, 100, 80, 'verde');

        // Indicador inactivo (should be excluded entirely)
        $nivel = $this->programa->mirNiveles()->where('tipo_nivel', TipoNivelMir::FIN->value)->first();

        $indicadorInactivo = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador inactivo',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => false,
            'orden' => 2,
        ]);

        $mp = MetaPeriodo::create([
            'indicador_id' => $indicadorInactivo->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
        ]);

        Avance::create([
            'meta_periodo_id' => $mp->id,
            'indicador_id' => $indicadorInactivo->id,
            'resultado' => 10,
            'semaforo_calculado' => 'rojo',
            'estado' => EstadoAvance::APROBADO->value,
        ]);

        $evaluacion = $this->service->calcular($this->programa, 2026);

        // Only active indicator counts: 0.40 * 80 = 32
        $this->assertEquals(32.0, (float) $evaluacion->indice_eficacia);
        $this->assertEquals(1, $evaluacion->indicadores_evaluados);
        // The inactive one should NOT appear as no_evaluado either
        $this->assertEquals(0, $evaluacion->indicadores_no_evaluados);
    }

    // --- Helper ---

    private function crearIndicadorConAvance(
        TipoNivelMir $tipoNivel,
        float $meta,
        float $resultado,
        string $semaforo,
    ): Indicador {
        $nivel = MirNivel::firstOrCreate(
            [
                'programa_presupuestario_id' => $this->programa->id,
                'tipo_nivel' => $tipoNivel->value,
            ],
            [
                'resumen_narrativo' => $tipoNivel->label() . ' test',
                'orden' => $tipoNivel->orden(),
            ]
        );

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => "Indicador {$tipoNivel->value} " . uniqid(),
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => $meta,
            'activo_seguimiento' => true,
            'orden' => $nivel->indicadores()->count() + 1,
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

        return $indicador;
    }
}
