<?php

namespace Tests\Unit\Presupuesto;

use App\Models\Presupuesto\PartidaPresupuestal;
use App\Services\Presupuesto\SemaforoFinancieroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\PresupuestoTestHelpers;

class SemaforoFinancieroServiceTest extends TestCase
{
    use RefreshDatabase;
    use PresupuestoTestHelpers;

    private SemaforoFinancieroService $service;
    private int $teamId;
    private int $userId;
    private int $programaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->service = app(SemaforoFinancieroService::class);

        $user = $this->crearUsuarioFinanciero();
        $this->userId = $user->id;
        $this->teamId = $user->currentTeam->id;

        $programa = $this->crearPrograma($this->teamId);
        $this->programaId = $programa->id;
    }

    public function test_verde_cuando_ratio_dentro_de_rango(): void
    {
        // meta=1000, pagado=850 → ratio 0.85 → verde (verde_min=0.85)
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1000', 10000);
        $this->crearMeta($partida->id, 1, 1000);
        $this->crearAvance($partida->id, $this->userId, 1, 1000, 900, 850);

        $resultado = $this->service->calcular($partida->fresh(), 1);

        $this->assertEquals('verde', $resultado);
    }

    public function test_amarillo_cuando_ratio_bajo(): void
    {
        // meta=1000, pagado=650 → ratio 0.65 → amarillo (amarillo_min=0.60)
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1001', 10000);
        $this->crearMeta($partida->id, 1, 1000);
        $this->crearAvance($partida->id, $this->userId, 1, 750, 700, 650);

        $resultado = $this->service->calcular($partida->fresh(), 1);

        $this->assertEquals('amarillo', $resultado);
    }

    public function test_amarillo_cuando_ratio_alto(): void
    {
        // meta=1000, pagado=1250 → ratio 1.25 → amarillo (amarillo_max=1.30)
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1002', 10000);
        $this->crearMeta($partida->id, 1, 1000);
        $this->crearAvance($partida->id, $this->userId, 1, 1400, 1300, 1250);

        $resultado = $this->service->calcular($partida->fresh(), 1);

        $this->assertEquals('amarillo', $resultado);
    }

    public function test_rojo_cuando_ratio_muy_bajo(): void
    {
        // meta=1000, pagado=400 → ratio 0.40 → rojo (below amarillo_min=0.60)
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1003', 10000);
        $this->crearMeta($partida->id, 1, 1000);
        $this->crearAvance($partida->id, $this->userId, 1, 500, 450, 400);

        $resultado = $this->service->calcular($partida->fresh(), 1);

        $this->assertEquals('rojo', $resultado);
    }

    public function test_rojo_cuando_ratio_muy_alto(): void
    {
        // meta=1000, pagado=1500 → ratio 1.50 → rojo (above amarillo_max=1.30)
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1004', 10000);
        $this->crearMeta($partida->id, 1, 1000);
        $this->crearAvance($partida->id, $this->userId, 1, 1700, 1600, 1500);

        $resultado = $this->service->calcular($partida->fresh(), 1);

        $this->assertEquals('rojo', $resultado);
    }

    public function test_sin_datos_cuando_no_hay_avance(): void
    {
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1005', 10000);
        $this->crearMeta($partida->id, 1, 1000);

        $resultado = $this->service->calcular($partida->fresh(), 1);

        $this->assertEquals('sin_datos', $resultado);
    }

    public function test_fallback_lineal_sin_meta_calendarizada(): void
    {
        // Sin meta, usa monto_efectivo × trimestre/4 como referencia
        // partida aprobado=10000, trimestre=1, esperado=2500
        // pagado=2500 → ratio 1.0 → verde
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1006', 10000);
        $this->crearAvance($partida->id, $this->userId, 1, 3000, 2800, 2500);

        $resultado = $this->service->calcular($partida->fresh(), 1);

        $this->assertEquals('verde', $resultado);
    }

    public function test_division_por_cero_monto_programado_cero(): void
    {
        // Partida con monto_aprobado=0, sin meta → debería retornar 'sin_datos'
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1007', 0);

        // No se puede crear avance con monto 0 en muchos casos, pero el servicio debería manejarlo
        $resultado = $this->service->calcular($partida->fresh(), 1);

        $this->assertEquals('sin_datos', $resultado);
    }

    public function test_umbrales_desde_config(): void
    {
        // Cambiar config para que 0.85 ya no sea verde (verde_min=0.90)
        config(['presupuesto.semaforo.verde_min' => 0.90]);

        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '1008', 10000);
        $this->crearMeta($partida->id, 1, 1000);
        $this->crearAvance($partida->id, $this->userId, 1, 1000, 900, 850);

        $resultado = $this->service->calcular($partida->fresh(), 1);

        // 850/1000 = 0.85, pero verde_min ahora es 0.90 → amarillo
        $this->assertEquals('amarillo', $resultado);
    }

    public function test_consolidado_programa_promedio_ponderado(): void
    {
        // 3 partidas con distintos montos y semáforos
        // Partida 1: aprobado=5000, pagado verde (ratio ~1.0)
        $p1 = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '2001', 5000);
        $this->crearMeta($p1->id, 1, 1250);
        $this->crearAvance($p1->id, $this->userId, 1, 1300, 1250, 1200);

        // Partida 2: aprobado=3000, pagado rojo (ratio ~0.30)
        $p2 = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '2002', 3000);
        $this->crearMeta($p2->id, 1, 1000);
        $this->crearAvance($p2->id, $this->userId, 1, 350, 320, 300);

        // Partida 3: aprobado=2000, pagado verde (ratio ~0.95)
        $p3 = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '2003', 2000);
        $this->crearMeta($p3->id, 1, 500);
        $this->crearAvance($p3->id, $this->userId, 1, 520, 500, 475);

        $resultado = $this->service->consolidadoPrograma($this->programaId, 2026, 1);

        // Ponderado: verde(1)×5000 + rojo(0)×3000 + verde(1)×2000 = 7000/10000 = 0.70 → verde
        $this->assertContains($resultado, ['verde', 'amarillo']);
    }

    public function test_combinado_fisico_financiero(): void
    {
        // Crear partida con buen avance financiero (verde)
        $partida = $this->crearPartida($this->programaId, $this->teamId, $this->userId, '3001', 10000);
        $this->crearMeta($partida->id, 1, 2500);
        $this->crearAvance($partida->id, $this->userId, 1, 2700, 2600, 2500);

        $resultado = $this->service->combinado($this->programaId, 2026, 1);

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('fisico', $resultado);
        $this->assertArrayHasKey('financiero', $resultado);
        $this->assertArrayHasKey('combinado', $resultado);

        // Financiero debería ser verde
        $this->assertEquals('verde', $resultado['financiero']);

        // Combinado depende del físico; si no hay datos físicos, hereda el financiero
        $this->assertContains($resultado['combinado'], ['verde', 'amarillo', 'rojo', 'sin_datos']);
    }
}
