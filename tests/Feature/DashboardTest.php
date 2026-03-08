<?php

namespace Tests\Feature;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Dashboard;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operador;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();

        $this->admin = User::factory()->withPersonalTeam()->create();
        $this->admin->assignRole('admin');
        $this->teamId = $this->admin->currentTeam->id;

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole('operador');
    }

    private function createProgramWithAvance(string $semaforo = 'verde'): void
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $this->teamId,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel test',
            'orden' => 1,
            'team_id' => $this->teamId,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $meta = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'resultado' => 90,
            'semaforo_calculado' => $semaforo,
            'estado' => EstadoAvance::EN_REVISION->value,
            'capturado_por' => $this->admin->id,
        ]);
    }

    public function test_dashboard_renders_for_admin(): void
    {
        $this->createProgramWithAvance();

        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('Dashboard')
            ->assertSee('Programas')
            ->assertSee('Indicadores')
            ->assertSee('Avance Promedio');
    }

    public function test_dashboard_shows_real_stats(): void
    {
        $this->createProgramWithAvance();

        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('90%');
    }

    public function test_admin_sees_charts_section(): void
    {
        $this->createProgramWithAvance();

        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('Semáforo Global')
            ->assertSee('Avance por Programa')
            ->assertSee('Tendencia de Captura');
    }

    public function test_admin_empty_state_no_programs(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('No hay programas registrados')
            ->assertSee('Ir a Programas');
    }

    public function test_operador_sees_pendientes_widget(): void
    {
        $this->actingAs($this->operador);
        Livewire::test(Dashboard::class)
            ->assertSee('Mis Pendientes')
            ->assertSee('Capturados este mes');
    }

    public function test_operador_no_pendientes_empty_state(): void
    {
        $this->actingAs($this->operador);
        Livewire::test(Dashboard::class)
            ->assertSee('No tienes indicadores pendientes');
    }

    public function test_operador_does_not_see_admin_widgets(): void
    {
        $this->actingAs($this->operador);
        Livewire::test(Dashboard::class)
            ->assertDontSee('Avance Promedio')
            ->assertDontSee('Avance por Programa');
    }

    public function test_activity_placeholder_visible(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('Actividad Reciente');
    }

    public function test_guest_redirected(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_vencidos_widget_shown_when_overdue(): void
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Vencido',
            'clave' => 'PV-001',
            'team_id' => $this->teamId,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel',
            'orden' => 1,
            'team_id' => $this->teamId,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Vencido',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
            'fecha_cierre' => now()->subDay(),
        ]);

        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('Vencidos');
    }
}
