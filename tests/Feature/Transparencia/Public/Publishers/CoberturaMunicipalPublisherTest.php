<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use App\Services\Transparencia\Publishing\CoberturaMunicipalPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class CoberturaMunicipalPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();

        // Silencia los jobs RegisterProgramOnGeoBase / DeactivateProgramOnGeoBase
        // disparados por el observer cuando se cambia padron_geobase_activo en
        // los factories. El test del publisher no necesita verificar esos jobs.
        Queue::fake();
    }

    public function test_filtra_solo_programas_con_padron_geobase_activo_y_envia_payload(): void
    {
        $programaActivo = ProgramaPresupuestario::factory()->create([
            'nombre' => 'Programa Activo', 'padron_geobase_activo' => true,
        ]);
        $programaInactivo = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => false,
        ]);
        $this->seedMirHasta(programa: $programaActivo, trimestre: 2, ejercicio: 2026);

        Http::fake([
            '*cobertura-municipal-bulk*' => Http::response(['data' => []], 200),
        ]);
        Http::preventStrayRequests();

        app(CoberturaMunicipalPublisher::class)->publish(DatasetAbierto::factory()->create());

        Http::assertSent(function ($request) use ($programaActivo, $programaInactivo) {
            $url = $request->url();

            // Activo SI debe aparecer como spp_program_ids[0]=<id>.
            // Inactivo NO debe aparecer en NINGÚN índice de spp_program_ids.
            // Comparamos contra el patrón estricto ".=ID&" o ".=ID$" para
            // evitar falsos positivos contra otros números de la URL (ej. ejercicio_fiscal=2026).
            $hasActivo = (bool) preg_match(
                '/spp_program_ids%5B\d+%5D='.$programaActivo->id.'(&|$)/',
                $url,
            );
            $hasInactivo = (bool) preg_match(
                '/spp_program_ids%5B\d+%5D='.$programaInactivo->id.'(&|$)/',
                $url,
            );

            return $hasActivo && ! $hasInactivo;
        });
    }

    public function test_mapea_response_a_filas_pub_y_resuelve_programa_nombre_local(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'Programa Test', 'padron_geobase_activo' => true,
        ]);
        $this->seedMirHasta(programa: $programa, trimestre: 1, ejercicio: 2026);

        Http::fake([
            '*cobertura-municipal-bulk*' => Http::response([
                'data' => [
                    [
                        'spp_program_id' => $programa->id,
                        'ejercicio_fiscal' => 2026,
                        'trimestre' => 1,
                        'municipio_clave' => '20001',
                        'municipio_nombre' => 'Oaxaca de Juárez',
                        'total_beneficiarios' => 50,
                        'total_inscripciones' => 50,
                        'monto_total' => 12345.67,
                    ],
                ],
            ], 200),
        ]);
        Http::preventStrayRequests();

        app(CoberturaMunicipalPublisher::class)->publish(DatasetAbierto::factory()->create());

        $row = DB::connection('pgsql_public')->table('pub_cobertura_municipal')->first();
        $this->assertNotNull($row);
        $this->assertSame('Programa Test', $row->programa_nombre);
        $this->assertSame('20001', $row->municipio_clave);
        $this->assertSame(50, (int) $row->total_beneficiarios);
    }

    public function test_calcula_hash_idempotente_entre_corridas(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'P', 'padron_geobase_activo' => true,
        ]);
        $this->seedMirHasta(programa: $programa, trimestre: 1, ejercicio: 2026);

        Http::fake([
            '*cobertura-municipal-bulk*' => Http::response([
                'data' => [[
                    'spp_program_id' => $programa->id, 'ejercicio_fiscal' => 2026,
                    'trimestre' => 1, 'municipio_clave' => '20001',
                    'municipio_nombre' => 'X', 'total_beneficiarios' => 10,
                    'total_inscripciones' => 10, 'monto_total' => 100.0,
                ]],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $r1 = app(CoberturaMunicipalPublisher::class)->publish(DatasetAbierto::factory()->create());
        $r2 = app(CoberturaMunicipalPublisher::class)->publish(DatasetAbierto::factory()->create());

        $this->assertSame($r1['hash'], $r2['hash']);
    }

    public function test_no_envia_request_si_no_hay_programas_activos(): void
    {
        // Solo programas inactivos.
        ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => false]);

        Http::fake([
            '*cobertura-municipal-bulk*' => Http::response(['data' => []], 200),
        ]);
        Http::preventStrayRequests();

        $r = app(CoberturaMunicipalPublisher::class)->publish(DatasetAbierto::factory()->create());

        Http::assertNothingSent();
        $this->assertSame(0, $r['count']);
    }

    public function test_code_es_ds_g01(): void
    {
        $this->assertSame('DS-G01', app(CoberturaMunicipalPublisher::class)->code());
    }

    private function seedMirHasta(ProgramaPresupuestario $programa, int $trimestre, int $ejercicio): void
    {
        User::factory()->create();
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente', 'resumen_narrativo' => 'C',
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'IND',
            'tipo' => 'gestion', 'dimension' => 'eficacia', 'frecuencia' => 'trimestral',
        ]);
        for ($q = 1; $q <= $trimestre; $q++) {
            MetaPeriodo::create([
                'indicador_id' => $indicador->id, 'periodo' => $q,
                'meta_periodo' => 100, 'ejercicio_fiscal' => $ejercicio,
            ]);
        }
    }
}
