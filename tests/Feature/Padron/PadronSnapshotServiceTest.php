<?php

namespace Tests\Feature\Padron;

use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Padron\PadronSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PadronSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Capture sync jobs dispatched by the MirNivelGeoBaseObserver when
        // a Componente is created on a programa with padron_geobase_activo.
        Queue::fake();
    }

    public function test_generar_solicita_snapshot_persiste_evidencia_y_logea(): void
    {
        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => [
                    'id' => 4421,
                    'snapshot_hash' => 'a3f7c9e2deadbeef',
                    'row_count' => 1847,
                    'cutoff_date' => '2026-03-31T23:59:59.000000Z',
                    'period' => '2026-Q1',
                    'valor_oficial' => 1820,
                ],
            ], 201),
        ]);
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $user = User::factory()->withPersonalTeam()->create();

        $service = app(PadronSnapshotService::class);
        $evidencia = $service->generar($programa, componenteId: 3, user: $user);

        $this->assertInstanceOf(AvanceEvidencia::class, $evidencia);
        $this->assertSame('a3f7c9e2deadbeef', $evidencia->hash_archivo);
        $this->assertSame(4421, $evidencia->geobase_snapshot_id);
        $this->assertSame($user->id, $evidencia->subido_por);
        $this->assertNull($evidencia->avance_id);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'padron-snapshot',
            'subject_id' => $evidencia->id,
            'subject_type' => AvanceEvidencia::class,
            'causer_id' => $user->id,
        ]);
    }

    public function test_generar_envia_period_en_formato_q(): void
    {
        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => ['id' => 1, 'snapshot_hash' => 'h', 'row_count' => 0, 'cutoff_date' => '2026-03-31'],
            ], 201),
        ]);
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $user = User::factory()->withPersonalTeam()->create();

        app(PadronSnapshotService::class)->generar($programa, 3, $user);

        Http::assertSent(function ($req) {
            return preg_match('/^\d{4}-Q[1-4]$/', $req['period']) === 1;
        });
    }

    public function test_generar_lanza_excepcion_si_geobase_falla(): void
    {
        Http::fake(['*' => Http::response(['error' => 'down'], 500)]);
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $user = User::factory()->withPersonalTeam()->create();

        $this->expectException(GeoBaseException::class);
        app(PadronSnapshotService::class)->generar($programa, 3, $user);
    }

    public function test_generar_no_persiste_si_geobase_falla(): void
    {
        Http::fake(['*' => Http::response(['error' => 'down'], 500)]);
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $user = User::factory()->withPersonalTeam()->create();

        try {
            app(PadronSnapshotService::class)->generar($programa, 3, $user);
        } catch (GeoBaseException) {
            // expected
        }

        $this->assertSame(0, AvanceEvidencia::count());
    }

    public function test_generar_vincula_avance_id_si_existe_avance_del_trimestre(): void
    {
        // Snapshots use the current quarter; lock time so the test is deterministic.
        Carbon::setTestNow('2026-04-15 12:00:00');

        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => ['id' => 100, 'snapshot_hash' => 'h', 'row_count' => 0, 'cutoff_date' => '2026-06-30'],
            ], 201),
        ]);
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'C1',
            'orden' => 1,
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $componente->id,
            'nombre' => 'Indicador C1',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'orden' => 1,
        ]);
        $meta = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 2,
            'ejercicio_fiscal' => 2026,
            'meta_periodo' => 100,
        ]);
        $user = User::factory()->withPersonalTeam()->create();
        $avance = Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'estado' => 'en_captura',
            'capturado_por' => $user->id,
        ]);

        $evidencia = app(PadronSnapshotService::class)->generar($programa, $componente->id, $user);

        $this->assertSame($avance->id, $evidencia->avance_id);

        Carbon::setTestNow();
    }

    public function test_generar_no_duplica_evidencia_para_el_mismo_snapshot(): void
    {
        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => ['id' => 100, 'snapshot_hash' => 'h', 'row_count' => 0, 'cutoff_date' => '2026-06-30'],
            ], 201),
        ]);
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $user = User::factory()->withPersonalTeam()->create();

        $service = app(PadronSnapshotService::class);
        $service->generar($programa, 3, $user);
        $service->generar($programa, 3, $user);

        $this->assertSame(1, AvanceEvidencia::where('geobase_snapshot_id', 100)->count());
    }

    public function test_generar_deja_avance_id_null_si_no_hay_avance_del_trimestre(): void
    {
        Carbon::setTestNow('2026-04-15 12:00:00');

        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => ['id' => 100, 'snapshot_hash' => 'h', 'row_count' => 0, 'cutoff_date' => '2026-06-30'],
            ], 201),
        ]);
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'C1',
            'orden' => 1,
        ]);
        $user = User::factory()->withPersonalTeam()->create();

        $evidencia = app(PadronSnapshotService::class)->generar($programa, $componente->id, $user);

        $this->assertNull($evidencia->avance_id);

        Carbon::setTestNow();
    }
}
