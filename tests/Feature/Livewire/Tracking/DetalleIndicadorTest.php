<?php

namespace Tests\Feature\Livewire\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\DetalleIndicador;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetalleIndicadorTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private Indicador $indicador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole('planeador');

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Detalle',
            'clave' => 'PD-01',
            'team_id' => $this->planeador->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel detalle',
            'orden' => 1,
            'team_id' => $this->planeador->currentTeam->id,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador detalle',
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

        Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_REVISION->value,
            'resultado' => 20,
            'semaforo_calculado' => 'verde',
            'capturado_por' => $this->planeador->id,
        ]);
    }

    #[Test]
    public function muestra_trazabilidad_y_nombre_del_indicador(): void
    {
        $this->actingAs($this->planeador);

        Livewire::test(DetalleIndicador::class, ['indicador' => $this->indicador])
            ->assertSee('Indicador detalle')
            ->assertSee('PD-01-f');
    }

    #[Test]
    public function operador_del_team_con_capturar_avance_puede_ver_historico(): void
    {
        // El operador captura avances; consultar el histórico del indicador de su
        // propia UR (solo lectura) es parte de su flujo (A.5).
        $operador = User::factory()->create();
        $operador->assignRole('operador');
        $this->planeador->currentTeam->users()->attach($operador, ['role' => 'operador']);
        $operador->switchTeam($this->planeador->currentTeam);
        $this->actingAs($operador);

        Livewire::test(DetalleIndicador::class, ['indicador' => $this->indicador])
            ->assertSee('Indicador detalle');
    }

    #[Test]
    public function usuario_sin_capturar_ni_revisar_recibe_403(): void
    {
        // Sin capturar_avance ni revisar_avance no se accede al histórico.
        $sinPermiso = User::factory()->withPersonalTeam()->create();
        $this->actingAs($sinPermiso);

        $response = $this->get(route('tracking.indicador.detalle', $this->indicador));
        $response->assertStatus(403);
    }

    #[Test]
    public function indicador_de_otro_team_devuelve_403(): void
    {
        $otroUsuario = User::factory()->withPersonalTeam()->create();
        $otroUsuario->assignRole('planeador');
        $this->actingAs($otroUsuario);

        $response = $this->get(route('tracking.indicador.detalle', $this->indicador));
        $response->assertStatus(403);
    }
}
