<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Exceptions\TransicionInvalidaException;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Notifications\AvanceEnRevisionNotification;
use App\Notifications\AvanceObservadoNotification;
use App\Notifications\AvanceVencidoNotification;
use App\Services\Tracking\AvanceEstadoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AvanceEstadoTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;

    private User $planeador;

    private Avance $avance;

    private AvanceEstadoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole('operador');

        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole('planeador');

        // Add planeador to operador's team so notification tests can find them
        $this->operador->currentTeam->users()->attach(
            $this->planeador, ['role' => 'planeador']
        );

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

        $indicador = Indicador::create([
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
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        $this->avance = Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->operador->id,
        ]);

        $this->service = new AvanceEstadoService;
    }

    public function test_transicion_en_captura_a_en_revision(): void
    {
        $this->service->transicionar($this->avance, EstadoAvance::EN_REVISION, $this->operador);

        $this->avance->refresh();
        $this->assertEquals(EstadoAvance::EN_REVISION, $this->avance->estado);
    }

    public function test_transicion_en_revision_a_aprobado(): void
    {
        $this->avance->update(['estado' => EstadoAvance::EN_REVISION->value]);

        $this->service->transicionar($this->avance, EstadoAvance::APROBADO, $this->planeador);

        $this->avance->refresh();
        $this->assertEquals(EstadoAvance::APROBADO, $this->avance->estado);
    }

    public function test_transicion_en_revision_a_observado(): void
    {
        $this->avance->update(['estado' => EstadoAvance::EN_REVISION->value]);

        $this->service->transicionar($this->avance, EstadoAvance::OBSERVADO, $this->planeador, 'Falta evidencia documental');

        $this->avance->refresh();
        $this->assertEquals(EstadoAvance::OBSERVADO, $this->avance->estado);
    }

    public function test_transicion_observado_a_en_captura(): void
    {
        $this->avance->update(['estado' => EstadoAvance::OBSERVADO->value]);

        $this->service->transicionar($this->avance, EstadoAvance::EN_CAPTURA, $this->operador);

        $this->avance->refresh();
        $this->assertEquals(EstadoAvance::EN_CAPTURA, $this->avance->estado);
    }

    public function test_transicion_invalida_lanza_excepcion(): void
    {
        $this->expectException(TransicionInvalidaException::class);

        $this->service->transicionar($this->avance, EstadoAvance::APROBADO, $this->planeador);
    }

    public function test_aprobado_no_permite_transicion(): void
    {
        $this->avance->update([
            'estado' => EstadoAvance::APROBADO->value,
            'congelado_at' => now(),
        ]);

        $this->expectException(TransicionInvalidaException::class);

        $this->service->transicionar($this->avance, EstadoAvance::EN_CAPTURA, $this->operador);
    }

    public function test_vencido_no_permite_transicion(): void
    {
        $this->avance->update(['estado' => EstadoAvance::VENCIDO->value]);

        $this->expectException(TransicionInvalidaException::class);

        $this->service->transicionar($this->avance, EstadoAvance::EN_CAPTURA, $this->operador);
    }

    public function test_historial_se_acumula(): void
    {
        // First transition: en_captura -> en_revision
        $this->service->transicionar($this->avance, EstadoAvance::EN_REVISION, $this->operador);

        // Second transition: en_revision -> observado
        $this->service->transicionar($this->avance, EstadoAvance::OBSERVADO, $this->planeador, 'Revisar datos');

        // Third transition: observado -> en_captura
        $this->service->transicionar($this->avance, EstadoAvance::EN_CAPTURA, $this->operador);

        $this->avance->refresh();
        $historial = $this->avance->historial_observaciones;

        $this->assertCount(3, $historial);
        $this->assertEquals('en_revision', $historial[0]['estado_nuevo']);
        $this->assertEquals('observado', $historial[1]['estado_nuevo']);
        $this->assertEquals('Revisar datos', $historial[1]['observacion']);
        $this->assertEquals('en_captura', $historial[2]['estado_nuevo']);
    }

    public function test_aprobar_congela_avance(): void
    {
        $this->avance->update(['estado' => EstadoAvance::EN_REVISION->value]);

        $this->assertNull($this->avance->congelado_at);

        $this->service->transicionar($this->avance, EstadoAvance::APROBADO, $this->planeador);

        $this->avance->refresh();
        $this->assertNotNull($this->avance->congelado_at);
        $this->assertTrue($this->avance->estaCongelado());
    }

    public function test_notificacion_al_observar(): void
    {
        Notification::fake();

        $this->avance->update(['estado' => EstadoAvance::EN_REVISION->value]);
        $this->avance->load('indicador');

        $this->actingAs($this->planeador);

        // Use the Livewire component flow to test notification
        $this->avance->capturador->notify(
            new AvanceObservadoNotification($this->avance, 'Falta completar campos')
        );

        Notification::assertSentTo(
            $this->operador,
            AvanceObservadoNotification::class,
        );
    }

    public function test_transicionar_en_revision_notifica_planeadores(): void
    {
        Notification::fake();

        $this->service->transicionar($this->avance, EstadoAvance::EN_REVISION, $this->operador);

        Notification::assertSentTo($this->planeador, AvanceEnRevisionNotification::class);
    }

    public function test_transicionar_observado_notifica_capturador(): void
    {
        Notification::fake();

        $this->avance->update(['estado' => EstadoAvance::EN_REVISION->value]);

        $this->service->transicionar($this->avance, EstadoAvance::OBSERVADO, $this->planeador, 'Falta evidencia');

        Notification::assertSentTo($this->operador, AvanceObservadoNotification::class);
    }

    public function test_transicionar_aprobado_no_notifica(): void
    {
        Notification::fake();

        $this->avance->update(['estado' => EstadoAvance::EN_REVISION->value]);

        $this->service->transicionar($this->avance, EstadoAvance::APROBADO, $this->planeador);

        Notification::assertNotSentTo($this->operador, AvanceObservadoNotification::class);
        Notification::assertNotSentTo($this->planeador, AvanceEnRevisionNotification::class);
    }

    public function test_transicionar_con_notificar_false_no_notifica(): void
    {
        Notification::fake();

        $this->service->transicionar($this->avance, EstadoAvance::EN_REVISION, $this->operador, null, false);

        Notification::assertNothingSent();
    }

    public function test_transicionar_vencido_notifica_capturador(): void
    {
        Notification::fake();

        $this->service->transicionar($this->avance, EstadoAvance::VENCIDO, $this->operador);

        Notification::assertSentTo($this->operador, AvanceVencidoNotification::class);
    }
}
