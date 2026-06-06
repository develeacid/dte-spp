<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\Mml\CalendarizacionService;
use App\Services\Tracking\CalendarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CalendarioTest extends TestCase
{
    use RefreshDatabase;

    private CalendarioService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalendarioService;
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    public function test_calcular_fechas_trimestral(): void
    {
        // Norma SHCP: la ventana de captura cierra cierre_trimestre + 30 días.
        // Trimestre 1 de 2026 cierra el 31-mar → apertura 01-abr, cierre 30-abr.
        $result = $this->service->calcularFechas(2026, FrecuenciaMedicion::TRIMESTRAL);
        $this->assertCount(4, $result);
        $this->assertEquals('2026-04-01', $result[0]['fecha_apertura']);
        $this->assertEquals('2026-04-30', $result[0]['fecha_cierre']);
        $this->assertEquals('2026-07-01', $result[1]['fecha_apertura']);
        $this->assertEquals('2026-07-30', $result[1]['fecha_cierre']);
        $this->assertEquals('2026-10-01', $result[2]['fecha_apertura']);
        $this->assertEquals('2026-10-30', $result[2]['fecha_cierre']);
        $this->assertEquals('2027-01-01', $result[3]['fecha_apertura']);
        $this->assertEquals('2027-01-30', $result[3]['fecha_cierre']);
    }

    public function test_calcular_fechas_mensual(): void
    {
        $result = $this->service->calcularFechas(2026, FrecuenciaMedicion::MENSUAL);
        $this->assertCount(12, $result);
        // Enero cierra 31-ene → apertura 01-feb, cierre 31-ene + 30d = 02-mar.
        $this->assertEquals('2026-02-01', $result[0]['fecha_apertura']);
        $this->assertEquals('2026-03-02', $result[0]['fecha_cierre']);
        $this->assertEquals('2027-01-01', $result[11]['fecha_apertura']);
        $this->assertEquals('2027-01-30', $result[11]['fecha_cierre']);
    }

    public function test_calcular_fechas_semestral(): void
    {
        $result = $this->service->calcularFechas(2026, FrecuenciaMedicion::SEMESTRAL);
        $this->assertCount(2, $result);
        $this->assertEquals('2026-07-01', $result[0]['fecha_apertura']);
        $this->assertEquals('2026-07-30', $result[0]['fecha_cierre']);
        $this->assertEquals('2027-01-01', $result[1]['fecha_apertura']);
        $this->assertEquals('2027-01-30', $result[1]['fecha_cierre']);
    }

    public function test_calcular_fechas_respeta_config_dias_ventana_captura(): void
    {
        // Override de la ventana normativa: 45 días tras el cierre del periodo.
        // Trimestre 1 de 2026 cierra 31-mar → cierre captura 31-mar + 45d = 15-may.
        config(['tracking.dias_ventana_captura' => 45]);

        $result = $this->service->calcularFechas(2026, FrecuenciaMedicion::TRIMESTRAL);
        $this->assertEquals('2026-04-01', $result[0]['fecha_apertura']);
        $this->assertEquals('2026-05-15', $result[0]['fecha_cierre']);
    }

    public function test_confirmar_persiste_fechas_normativas_por_periodo(): void
    {
        // Integración: el flujo real del wizard (CalendarizacionService::confirmar)
        // ahora persiste fecha_apertura/fecha_cierre por periodo según la norma.
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-CAL',
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Componente trimestral', 'orden' => 1,
            'team_id' => $this->user->currentTeam->id,
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa trimestral',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => FrecuenciaMedicion::TRIMESTRAL->value, 'meta' => 100,
            'activo_seguimiento' => true, 'orden' => 1,
        ]);

        $calendarizacion = new CalendarizacionService;
        $calendarizacion->confirmar($programa, $calendarizacion->generar($programa), 2026);

        $metas = MetaPeriodo::where('indicador_id', $indicador->id)
            ->orderBy('periodo')->get();

        $this->assertCount(4, $metas);

        // Las fechas no quedan null y siguen la norma cierre_periodo + 30d.
        $this->assertEquals('2026-04-01', $metas[0]->fecha_apertura->toDateString());
        $this->assertEquals('2026-04-30', $metas[0]->fecha_cierre->toDateString());
        $this->assertEquals('2027-01-01', $metas[3]->fecha_apertura->toDateString());
        $this->assertEquals('2027-01-30', $metas[3]->fecha_cierre->toDateString());

        foreach ($metas as $meta) {
            $this->assertNotNull($meta->fecha_apertura);
            $this->assertNotNull($meta->fecha_cierre);
        }
    }

    public function test_abrir_periodos_crea_avances(): void
    {
        Notification::fake();
        Permission::findOrCreate('capturar_avance', 'web');
        $meta = $this->crearMetaPeriodoAbierta();

        $this->artisan('mir:abrir-periodos')
            ->expectsOutputToContain('Periodos abiertos: 1')
            ->assertSuccessful();

        $this->assertDatabaseHas('avances', [
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $meta->indicador_id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
        ]);
    }

    public function test_abrir_periodos_no_duplica(): void
    {
        Notification::fake();
        Permission::findOrCreate('capturar_avance', 'web');
        $meta = $this->crearMetaPeriodoAbierta();

        $this->artisan('mir:abrir-periodos')->assertSuccessful();
        $this->artisan('mir:abrir-periodos')->assertSuccessful();

        $this->assertEquals(1, Avance::where('meta_periodo_id', $meta->id)->count());
    }

    public function test_cerrar_vencidos_marca_estado(): void
    {
        Notification::fake();
        $avance = $this->crearAvanceVencido(EstadoAvance::EN_CAPTURA);

        $this->artisan('mir:cerrar-vencidos')
            ->expectsOutputToContain('Avances vencidos: 1')
            ->assertSuccessful();

        $this->assertEquals(EstadoAvance::VENCIDO, $avance->fresh()->estado);
    }

    public function test_cerrar_vencidos_no_afecta_aprobados(): void
    {
        Notification::fake();
        $avance = $this->crearAvanceVencido(EstadoAvance::APROBADO);

        $this->artisan('mir:cerrar-vencidos')
            ->expectsOutputToContain('Avances vencidos: 0')
            ->assertSuccessful();

        $this->assertEquals(EstadoAvance::APROBADO, $avance->fresh()->estado);
    }

    public function test_notificacion_periodo_abierto(): void
    {
        $meta = $this->crearMetaPeriodoAbierta();
        Permission::findOrCreate('capturar_avance', 'web');
        $this->user->givePermissionTo('capturar_avance');

        $this->artisan('mir:abrir-periodos')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'notifiable_type' => User::class,
        ]);

        $notification = $this->user->notifications()->first();
        $this->assertEquals('periodo_abierto', $notification->data['type']);
    }

    private function crearMetaPeriodoAbierta(): MetaPeriodo
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
            'team_id' => $this->user->currentTeam->id,
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa test',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'meta' => 100,
            'activo_seguimiento' => true, 'orden' => 1,
        ]);

        return MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1, 'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026, 'activo' => true,
            'fecha_apertura' => now()->subDay(),
            'fecha_cierre' => now()->addDays(13),
        ]);
    }

    private function crearAvanceVencido(EstadoAvance $estado): Avance
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-002',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
            'team_id' => $this->user->currentTeam->id,
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa vencida',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'meta' => 100,
            'activo_seguimiento' => true, 'orden' => 1,
        ]);
        $meta = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1, 'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026, 'activo' => true,
            'fecha_apertura' => now()->subDays(20),
            'fecha_cierre' => now()->subDay(),
        ]);

        return Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'estado' => $estado->value,
            'capturado_por' => $this->user->id,
        ]);
    }
}
