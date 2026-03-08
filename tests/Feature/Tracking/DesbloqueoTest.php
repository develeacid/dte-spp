<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\CapturaAvance;
use App\Livewire\Tracking\GestionarDesbloqueos;
use App\Livewire\Tracking\SolicitarDesbloqueo;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Tracking\Desbloqueo;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DesbloqueoTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;

    private User $admin;

    private Avance $avance;

    private Indicador $indicador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole('operador');

        $this->admin = User::factory()->withPersonalTeam()->create();
        $this->admin->assignRole('admin');

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test',
            'clave' => 'PC-001',
            'team_id' => $this->operador->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test',
            'orden' => 1,
            'team_id' => $this->operador->currentTeam->id,
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

        IndicadorVariable::create([
            'indicador_id' => $this->indicador->id,
            'nombre' => 'Variable A',
            'simbolo' => 'A',
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
            'estado' => EstadoAvance::APROBADO->value,
            'congelado_at' => now(),
            'capturado_por' => $this->operador->id,
        ]);
    }

    public function test_avance_congelado_bloquea_edicion(): void
    {
        $this->actingAs($this->operador);

        $variable = $this->indicador->variables->first();

        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$variable->id}", 50)
            ->call('guardar')
            ->assertStatus(403);
    }

    public function test_solicitar_desbloqueo(): void
    {
        $this->actingAs($this->operador);

        Livewire::test(SolicitarDesbloqueo::class, ['avance' => $this->avance])
            ->set('motivo', 'Necesito corregir un error en los datos capturados')
            ->call('solicitar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('desbloqueos', [
            'avance_id' => $this->avance->id,
            'estado' => 'pendiente',
            'solicitado_por' => $this->operador->id,
        ]);
    }

    public function test_no_duplica_solicitud_pendiente(): void
    {
        $this->actingAs($this->operador);

        Desbloqueo::create([
            'avance_id' => $this->avance->id,
            'motivo' => 'Primera solicitud pendiente',
            'solicitado_por' => $this->operador->id,
            'estado' => 'pendiente',
        ]);

        Livewire::test(SolicitarDesbloqueo::class, ['avance' => $this->avance])
            ->set('motivo', 'Segunda solicitud que deberia ser bloqueada')
            ->call('solicitar');

        $this->assertEquals(1, Desbloqueo::where('avance_id', $this->avance->id)->where('estado', 'pendiente')->count());
    }

    public function test_aprobar_descongela(): void
    {
        $this->actingAs($this->admin);

        $desbloqueo = Desbloqueo::create([
            'avance_id' => $this->avance->id,
            'motivo' => 'Necesito corregir datos',
            'solicitado_por' => $this->operador->id,
            'estado' => 'pendiente',
        ]);

        Livewire::test(GestionarDesbloqueos::class)
            ->call('aprobar', $desbloqueo->id);

        $this->avance->refresh();
        $desbloqueo->refresh();

        $this->assertNull($this->avance->congelado_at);
        $this->assertEquals(EstadoAvance::EN_CAPTURA, $this->avance->estado);
        $this->assertEquals('aprobado', $desbloqueo->estado);
    }

    public function test_rechazar_mantiene_congelado(): void
    {
        $this->actingAs($this->admin);

        $desbloqueo = Desbloqueo::create([
            'avance_id' => $this->avance->id,
            'motivo' => 'Necesito corregir datos',
            'solicitado_por' => $this->operador->id,
            'estado' => 'pendiente',
        ]);

        Livewire::test(GestionarDesbloqueos::class)
            ->set('resolucionTexto', 'No procede la solicitud')
            ->call('rechazar', $desbloqueo->id);

        $this->avance->refresh();
        $desbloqueo->refresh();

        $this->assertNotNull($this->avance->congelado_at);
        $this->assertEquals(EstadoAvance::APROBADO, $this->avance->estado);
        $this->assertEquals('rechazado', $desbloqueo->estado);
        $this->assertEquals('No procede la solicitud', $desbloqueo->resolucion);
    }

    public function test_solo_admin_puede_aprobar(): void
    {
        $this->actingAs($this->operador);

        $response = $this->get(route('tracking.desbloqueos'));
        $response->assertStatus(403);
    }

    public function test_historial_actualizado_al_aprobar(): void
    {
        $this->actingAs($this->admin);

        $desbloqueo = Desbloqueo::create([
            'avance_id' => $this->avance->id,
            'motivo' => 'Necesito corregir datos',
            'solicitado_por' => $this->operador->id,
            'estado' => 'pendiente',
        ]);

        Livewire::test(GestionarDesbloqueos::class)
            ->call('aprobar', $desbloqueo->id);

        $this->avance->refresh();
        $historial = $this->avance->historial_observaciones;

        $this->assertNotEmpty($historial);
        $ultimo = end($historial);
        $this->assertEquals('desbloqueo_aprobado', $ultimo['accion']);
        $this->assertEquals('aprobado', $ultimo['estado_anterior']);
        $this->assertEquals('en_captura', $ultimo['estado_nuevo']);
    }
}
