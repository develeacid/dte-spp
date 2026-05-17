<?php

namespace Tests\Feature\Transparencia\Public;

use App\Enums\EstadoDatasetAbierto;
use App\Jobs\GeoBase\DeactivateProgramOnGeoBase;
use App\Jobs\GeoBase\RegisterProgramOnGeoBase;
use App\Jobs\GeoBase\SyncMirNivelToGeoBase;
use App\Jobs\GeoBase\SyncProgramaToGeoBase;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

/**
 * Integration test E2E del pipeline DS-G01 (cobertura municipal).
 *
 * Verifica el flujo completo:
 *   DatasetAbierto::publicar() → evento DatasetAbiertoPublicado →
 *   listener DispatchSyncPublicTable → SyncPublicDatasetJob (sync) →
 *   CoberturaMunicipalPublisher → bulk endpoint mockeado (Http::fake) →
 *   pub_cobertura_municipal + transparencia_publicaciones + pub_datasets_catalogo.
 */
class SyncPipelineDsg01IntegrationTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected bool $fakeBusInSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
        config(['queue.default' => 'sync']);

        // Faking selectivo: silencia los jobs disparados por el observer
        // de programa_presupuestarios al cambiar padron_geobase_activo,
        // pero deja al SyncPublicDatasetJob ejecutarse sync para el E2E.
        Queue::fake([
            RegisterProgramOnGeoBase::class,
            DeactivateProgramOnGeoBase::class,
            SyncProgramaToGeoBase::class,
            SyncMirNivelToGeoBase::class,
        ]);
    }

    public function test_publicar_dsg01_propaga_a_pub_cobertura_municipal_y_audita(): void
    {
        $user = User::factory()->create();
        $programa = $this->programaActivoConMir();

        Http::fake([
            '*cobertura-municipal-bulk*' => Http::response([
                'data' => [[
                    'spp_program_id' => $programa->id,
                    'ejercicio_fiscal' => 2026,
                    'trimestre' => 1,
                    'municipio_clave' => '20001',
                    'municipio_nombre' => 'Oaxaca de Juárez',
                    'total_beneficiarios' => 100,
                    'total_inscripciones' => 100,
                    'monto_total' => 50000.0,
                ]],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-G01',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        $this->actingAs($user);
        $dataset->publicar();

        // pub_cobertura_municipal: 1 fila esperada del response.
        $this->assertSame(
            1,
            DB::connection('pgsql_public')->table('pub_cobertura_municipal')->count(),
        );

        // pub_datasets_catalogo re-syncado siempre tras un publish.
        $this->assertSame(
            1,
            DB::connection('pgsql_public')->table('pub_datasets_catalogo')
                ->where('codigo', 'DS-G01')->count(),
        );

        // Auditoría histórica: success=true con hash y count.
        $publish = TransparenciaPublicacion::firstWhere('dataset_clave', 'DS-G01');
        $this->assertNotNull($publish);
        $this->assertTrue($publish->success);
        $this->assertSame('publish', $publish->action);
        $this->assertSame($user->id, $publish->publicado_por_user_id);
        $this->assertNotNull($publish->payload_hash);
        $this->assertSame(1, $publish->registros_count);
    }

    private function programaActivoConMir(): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'Programa DS-G01 E2E',
            'padron_geobase_activo' => true,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente',
            'resumen_narrativo' => 'C',
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'IND',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
        ]);
        MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
        ]);

        return $programa;
    }
}
