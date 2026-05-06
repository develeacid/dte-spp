<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\CoberturaGeograficaPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class CoberturaGeograficaPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
        Queue::fake();
    }

    public function test_inserta_una_fila_por_programa_con_padron_activo(): void
    {
        $programaActivo = ProgramaPresupuestario::factory()->create([
            'nombre' => 'Programa Activo', 'padron_geobase_activo' => true,
        ]);
        $programaInactivo = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);

        Http::fake(['*cobertura-geografica-bulk*' => Http::response([
            'data' => [
                [
                    'spp_program_id' => $programaActivo->id,
                    'geojson' => ['type' => 'Feature', 'geometry' => ['type' => 'MultiPolygon', 'coordinates' => [[[[0, 0], [1, 0], [1, 1], [0, 0]]]]], 'properties' => new \stdClass],
                    'area_km2' => 1234.56,
                    'municipios_incluidos' => ['20001', '20067'],
                ],
            ],
        ], 200)]);

        app(CoberturaGeograficaPublisher::class)->publish(DatasetAbierto::factory()->create());

        $rows = DB::connection('pgsql_public')->table('pub_cobertura_geografica')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Programa Activo', $rows[0]->programa_nombre);
        $this->assertEqualsWithDelta(1234.56, (float) $rows[0]->area_km2, 0.01);
    }

    public function test_mapea_municipios_incluidos_a_text_array_postgres(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'P', 'padron_geobase_activo' => true,
        ]);

        Http::fake(['*cobertura-geografica-bulk*' => Http::response([
            'data' => [[
                'spp_program_id' => $programa->id,
                'geojson' => ['type' => 'Feature', 'geometry' => null, 'properties' => new \stdClass],
                'area_km2' => 0,
                'municipios_incluidos' => ['20001', '20067', '20100'],
            ]],
        ], 200)]);

        app(CoberturaGeograficaPublisher::class)->publish(DatasetAbierto::factory()->create());

        // Validar que Postgres lo guardó como text[] (no como string serializada)
        $r = DB::connection('pgsql_public')->selectOne("
            SELECT array_length(municipios_incluidos, 1) as len,
                   municipios_incluidos[1] as primero
              FROM pub_cobertura_geografica
        ");
        $this->assertSame(3, (int) $r->len);
        $this->assertSame('20001', $r->primero);
    }

    public function test_acepta_geometry_null_para_programa_sin_actividad(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'P', 'padron_geobase_activo' => true,
        ]);

        Http::fake(['*cobertura-geografica-bulk*' => Http::response([
            'data' => [[
                'spp_program_id' => $programa->id,
                'geojson' => ['type' => 'Feature', 'geometry' => null, 'properties' => new \stdClass],
                'area_km2' => 0,
                'municipios_incluidos' => [],
            ]],
        ], 200)]);

        app(CoberturaGeograficaPublisher::class)->publish(DatasetAbierto::factory()->create());

        $row = DB::connection('pgsql_public')->table('pub_cobertura_geografica')->first();
        $this->assertNotNull($row);
        $geojson = is_string($row->geojson) ? json_decode($row->geojson, true) : $row->geojson;
        $this->assertNull($geojson['geometry']);
    }

    public function test_code_es_ds_g03(): void
    {
        $this->assertSame('DS-G03', app(CoberturaGeograficaPublisher::class)->code());
    }
}
