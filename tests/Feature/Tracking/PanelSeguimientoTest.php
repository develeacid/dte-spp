<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\PanelSeguimiento;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelSeguimientoTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private ProgramaPresupuestario $programa;

    private Indicador $indicador;

    private Avance $avance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole('planeador');

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $this->planeador->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel test',
            'orden' => 1,
            'team_id' => $this->planeador->currentTeam->id,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador de prueba',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        $this->avance = Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_REVISION->value,
            'resultado' => 20,
            'semaforo_calculado' => 'verde',
            'capturado_por' => $this->planeador->id,
        ]);
    }

    public function test_aislamiento_multi_ur(): void
    {
        $otroUsuario = User::factory()->withPersonalTeam()->create();
        $otroUsuario->assignRole('planeador');

        $otroProgramma = ProgramaPresupuestario::create([
            'nombre' => 'Otro Programa',
            'clave' => 'OP-001',
            'team_id' => $otroUsuario->currentTeam->id,
        ]);

        $otroNivel = MirNivel::create([
            'programa_presupuestario_id' => $otroProgramma->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Otro nivel',
            'orden' => 1,
            'team_id' => $otroUsuario->currentTeam->id,
        ]);

        Indicador::create([
            'mir_nivel_id' => $otroNivel->id,
            'nombre' => 'Indicador ajeno',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 50,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $this->actingAs($this->planeador);

        Livewire::test(PanelSeguimiento::class)
            ->set('activeTab', 'tabla')
            ->assertSee('Indicador de prueba')
            ->assertDontSee('Indicador ajeno');
    }

    public function test_filtro_por_programa(): void
    {
        $programa2 = ProgramaPresupuestario::create([
            'nombre' => 'Programa Dos',
            'clave' => 'PD-002',
            'team_id' => $this->planeador->currentTeam->id,
        ]);

        $nivel2 = MirNivel::create([
            'programa_presupuestario_id' => $programa2->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Nivel dos',
            'orden' => 1,
            'team_id' => $this->planeador->currentTeam->id,
        ]);

        Indicador::create([
            'mir_nivel_id' => $nivel2->id,
            'nombre' => 'Indicador segundo programa',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 80,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $this->actingAs($this->planeador);

        Livewire::test(PanelSeguimiento::class)
            ->set('activeTab', 'tabla')
            ->set('filtroPrograma', $this->programa->id)
            ->assertSee('Indicador de prueba')
            ->assertDontSee('Indicador segundo programa');
    }

    public function test_filtro_por_estado(): void
    {
        $this->actingAs($this->planeador);

        Livewire::test(PanelSeguimiento::class)
            ->set('activeTab', 'tabla')
            ->set('filtroEstado', EstadoAvance::EN_REVISION->value)
            ->assertSee('Indicador de prueba');

        Livewire::test(PanelSeguimiento::class)
            ->set('activeTab', 'tabla')
            ->set('filtroEstado', EstadoAvance::APROBADO->value)
            ->assertDontSee('Indicador de prueba');
    }

    public function test_requiere_permiso_revisar_avance(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $this->actingAs($operador);

        $response = $this->get(route('tracking.panel'));
        $response->assertStatus(403);
    }

    public function test_estado_vacio(): void
    {
        $planeador2 = User::factory()->withPersonalTeam()->create();
        $planeador2->assignRole('planeador');

        $this->actingAs($planeador2);

        Livewire::test(PanelSeguimiento::class)
            ->set('activeTab', 'tabla')
            ->assertSee('No se encontraron indicadores');
    }
}
