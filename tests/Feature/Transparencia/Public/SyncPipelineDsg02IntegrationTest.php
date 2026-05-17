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
 * Integration test E2E del pipeline DS-G02 (desagregación demográfica).
 *
 * Verifica el flujo completo:
 *   DatasetAbierto::publicar() → evento → listener → SyncPublicDatasetJob (sync) →
 *   DesagregacionDemograficaPublisher → bulk endpoint mockeado →
 *   pub_desagregacion_demografica + transparencia_publicaciones + pub_datasets_catalogo.
 *
 * El publisher desnormaliza 4 dimensiones (sexo / grupo_edad / discapacidad / pueblo)
 * y agrega filas estatales (municipio_clave NULL). Para 1 municipio con todas las
 * dimensiones pobladas se esperan: 2 sexo + 4 edad + 2 discapacidad + 1 etnia
 * municipales = 9 filas, más sus 9 réplicas estatales = 18 filas.
 */
class SyncPipelineDsg02IntegrationTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected bool $fakeBusInSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
        config(['queue.default' => 'sync']);

        Queue::fake([
            RegisterProgramOnGeoBase::class,
            DeactivateProgramOnGeoBase::class,
            SyncProgramaToGeoBase::class,
            SyncMirNivelToGeoBase::class,
        ]);
    }

    public function test_publicar_dsg02_propaga_a_pub_desagregacion_demografica_y_audita(): void
    {
        $user = User::factory()->create();
        $programa = $this->programaActivoConMir();

        Http::fake([
            '*desagregacion-bulk*' => Http::response([
                'data' => [[
                    'spp_program_id' => $programa->id,
                    'ejercicio_fiscal' => 2026,
                    'municipio_clave' => '20001',
                    'por_genero' => ['masculino' => 30, 'femenino' => 70, 'otro' => 0],
                    'por_grupo_edad' => ['nna' => 10, 'juventud' => 50, 'adulto' => 30, 'adulto_mayor' => 10],
                    'por_tipo_discapacidad' => ['motriz' => 5, 'ninguna' => 95],
                    'por_etnia' => ['no_indigena' => 100],
                ]],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-G02',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        $this->actingAs($user);
        $dataset->publicar();

        // pub_desagregacion_demografica:
        // 9 filas municipales: sexo M/F (otro=0 omitido) + 4 edad + 2 disc + 1 etnia.
        // 9 filas estatales (municipio_clave=NULL) replican el agregado por dimensión.
        $count = DB::connection('pgsql_public')->table('pub_desagregacion_demografica')->count();
        $this->assertGreaterThanOrEqual(9, $count, 'Debe haber al menos 9 filas (municipales + estatales)');

        // Verificar que existen ambas: municipales y estatales.
        $municipales = DB::connection('pgsql_public')->table('pub_desagregacion_demografica')
            ->where('municipio_clave', '20001')->count();
        $estatales = DB::connection('pgsql_public')->table('pub_desagregacion_demografica')
            ->whereNull('municipio_clave')->count();
        $this->assertSame(9, $municipales);
        $this->assertSame(9, $estatales);

        // pub_datasets_catalogo re-syncado siempre tras un publish.
        $this->assertSame(
            1,
            DB::connection('pgsql_public')->table('pub_datasets_catalogo')
                ->where('codigo', 'DS-G02')->count(),
        );

        // Auditoría histórica: success=true con hash y count.
        $publish = TransparenciaPublicacion::firstWhere('dataset_clave', 'DS-G02');
        $this->assertNotNull($publish);
        $this->assertTrue($publish->success);
        $this->assertSame('publish', $publish->action);
        $this->assertSame($user->id, $publish->publicado_por_user_id);
        $this->assertNotNull($publish->payload_hash);
        $this->assertSame($count, $publish->registros_count);
    }

    private function programaActivoConMir(): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'Programa DS-G02 E2E',
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
