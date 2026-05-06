<?php

namespace Tests\Feature\Transparencia\Public;

use App\Enums\EstadoDatasetAbierto;
use App\Jobs\GeoBase\DeactivateProgramOnGeoBase;
use App\Jobs\GeoBase\RegisterProgramOnGeoBase;
use App\Jobs\GeoBase\SyncMirNivelToGeoBase;
use App\Jobs\GeoBase\SyncProgramaToGeoBase;
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
 * Integration test E2E del pipeline DS-G03 (cobertura geográfica).
 *
 * Verifica el flujo completo:
 *   DatasetAbierto::publicar() → evento → listener → SyncPublicDatasetJob (sync) →
 *   CoberturaGeograficaPublisher → bulk endpoint mockeado →
 *   pub_cobertura_geografica + transparencia_publicaciones + pub_datasets_catalogo.
 *
 * El publisher inserta una fila por programa con padron_geobase_activo=true,
 * con `municipios_incluidos` casteado a Postgres text[] mediante literal `{...}`.
 */
class SyncPipelineDsg03IntegrationTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

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

    public function test_publicar_dsg03_propaga_a_pub_cobertura_geografica_y_audita(): void
    {
        $user = User::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'Programa DS-G03 E2E',
            'padron_geobase_activo' => true,
        ]);

        Http::fake([
            '*cobertura-geografica-bulk*' => Http::response([
                'data' => [[
                    'spp_program_id' => $programa->id,
                    'geojson' => ['type' => 'Feature', 'geometry' => null, 'properties' => new \stdClass],
                    'area_km2' => 0,
                    'municipios_incluidos' => ['20001'],
                ]],
            ], 200),
        ]);

        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-G03',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        $this->actingAs($user);
        $dataset->publicar();

        // pub_cobertura_geografica: 1 fila esperada.
        $this->assertSame(
            1,
            DB::connection('pgsql_public')->table('pub_cobertura_geografica')->count(),
        );

        // municipios_incluidos quedó como text[] de Postgres (no string serializada).
        $r = DB::connection('pgsql_public')->selectOne('
            SELECT array_length(municipios_incluidos, 1) as len,
                   municipios_incluidos[1] as primero
              FROM pub_cobertura_geografica
        ');
        $this->assertSame(1, (int) $r->len);
        $this->assertSame('20001', $r->primero);

        // pub_datasets_catalogo re-syncado siempre tras un publish.
        $this->assertSame(
            1,
            DB::connection('pgsql_public')->table('pub_datasets_catalogo')
                ->where('codigo', 'DS-G03')->count(),
        );

        // Auditoría histórica: success=true con hash y count.
        $publish = TransparenciaPublicacion::firstWhere('dataset_clave', 'DS-G03');
        $this->assertNotNull($publish);
        $this->assertTrue($publish->success);
        $this->assertSame('publish', $publish->action);
        $this->assertSame($user->id, $publish->publicado_por_user_id);
        $this->assertNotNull($publish->payload_hash);
        $this->assertSame(1, $publish->registros_count);
    }
}
