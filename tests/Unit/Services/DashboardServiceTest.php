<?php

namespace Tests\Unit\Services;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    private User $admin;

    private ProgramaPresupuestario $programa;

    private Indicador $indicador;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();

        $this->service = new DashboardService;

        $this->admin = User::factory()->withPersonalTeam()->create();
        $this->admin->assignRole('admin');
        $this->teamId = $this->admin->currentTeam->id;

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Dashboard',
            'clave' => 'PD-001',
            'team_id' => $this->teamId,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel test',
            'orden' => 1,
            'team_id' => $this->teamId,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Dashboard',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);
    }

    public function test_admin_stats_counts_programs(): void
    {
        $stats = $this->service->getAdminStats($this->teamId);

        $this->assertEquals(1, $stats->programas);
    }

    public function test_admin_stats_counts_indicators(): void
    {
        $stats = $this->service->getAdminStats($this->teamId);

        $this->assertEquals(1, $stats->indicadores);
    }

    public function test_admin_stats_average_progress(): void
    {
        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 75,
            'semaforo_calculado' => 'amarillo',
            'estado' => EstadoAvance::EN_REVISION->value,
            'capturado_por' => $this->admin->id,
        ]);

        Cache::flush();
        $stats = $this->service->getAdminStats($this->teamId);

        $this->assertEquals(75.0, $stats->avancePromedio);
    }

    public function test_admin_stats_counts_overdue(): void
    {
        MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
            'fecha_cierre' => now()->subDay(),
        ]);

        Cache::flush();
        $stats = $this->service->getAdminStats($this->teamId);

        $this->assertEquals(1, $stats->vencidos);
    }

    public function test_admin_stats_zero_when_no_data(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $stats = $this->service->getAdminStats($user->currentTeam->id);

        $this->assertEquals(0, $stats->programas);
        $this->assertEquals(0, $stats->indicadores);
        $this->assertEquals(0.0, $stats->avancePromedio);
        $this->assertEquals(0, $stats->vencidos);
    }

    public function test_operador_stats_counts_pending(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 0,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $operador->id,
        ]);

        $stats = $this->service->getOperadorStats($operador->id, $this->teamId);

        $this->assertEquals(1, $stats->pendientes);
    }

    public function test_semaforo_distribution(): void
    {
        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 95,
            'semaforo_calculado' => 'verde',
            'estado' => EstadoAvance::EN_REVISION->value,
            'capturado_por' => $this->admin->id,
        ]);

        $dist = $this->service->getSemaforoDistribution($this->teamId);

        $this->assertEquals(1, $dist['verde']);
        $this->assertEquals(0, $dist['amarillo']);
        $this->assertEquals(0, $dist['rojo']);
    }

    public function test_semaforo_distribution_empty(): void
    {
        $dist = $this->service->getSemaforoDistribution($this->teamId);

        $this->assertEquals(0, $dist['verde']);
        $this->assertEquals(0, $dist['amarillo']);
        $this->assertEquals(0, $dist['rojo']);
    }

    public function test_avance_por_programa(): void
    {
        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 80,
            'semaforo_calculado' => 'amarillo',
            'estado' => EstadoAvance::EN_REVISION->value,
            'capturado_por' => $this->admin->id,
        ]);

        Cache::flush();
        $result = $this->service->getAvancePorPrograma($this->teamId);

        $this->assertCount(1, $result);
        $this->assertEquals('PD-001', $result[0]['programa']);
        $this->assertEquals(80.0, $result[0]['real']);
    }

    public function test_tendencia_captura_fills_missing_months(): void
    {
        $result = $this->service->getTendenciaCaptura($this->teamId);

        $this->assertCount(6, $result);
        $result->each(fn ($item) => $this->assertArrayHasKey('mes', $item));
        $result->each(fn ($item) => $this->assertArrayHasKey('count', $item));
    }

    public function test_results_are_cached(): void
    {
        $this->service->getAdminStats($this->teamId);

        // Create another program — should NOT be reflected due to cache
        ProgramaPresupuestario::create([
            'nombre' => 'Programa Nuevo',
            'clave' => 'PN-002',
            'team_id' => $this->teamId,
        ]);

        $stats = $this->service->getAdminStats($this->teamId);
        $this->assertEquals(1, $stats->programas); // Still 1, cached
    }

    public function test_team_isolation(): void
    {
        $otroUser = User::factory()->withPersonalTeam()->create();
        $otroTeamId = $otroUser->currentTeam->id;

        $stats = $this->service->getAdminStats($otroTeamId);

        $this->assertEquals(0, $stats->programas);
    }
}
