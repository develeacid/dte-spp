<?php

namespace Tests\Unit\Services\GeoBase;

use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoBaseClientTest extends TestCase
{
    private GeoBaseClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.geobase.url' => 'http://geobase-test:8081/api/v1/geobase',
            'services.geobase.token' => 'test-token-123',
            'services.geobase.timeout' => 10,
            'services.geobase.retry_times' => 2,
            'services.geobase.retry_sleep' => 100,
        ]);

        Cache::flush();
        $this->client = app(GeoBaseClient::class);
    }

    public function test_get_atendida_proposito_builds_url_and_returns_data(): void
    {
        Http::fake([
            '*/programs/7/atendida-proposito*' => Http::response([
                'spp_program_id' => 7,
                'ejercicio' => 2026,
                'poblacion_atendida' => 1234,
                'por_componente' => [
                    ['spp_mir_nivel_id' => 1, 'atendida' => 800],
                ],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $result = $this->client->getAtendidaProposito(7, 2026);

        $this->assertEquals(1234, $result['poblacion_atendida']);
        $this->assertEquals(2026, $result['ejercicio']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/programs/7/atendida-proposito')
                && str_contains($request->url(), 'ejercicio=2026')
                && $request->hasHeader('Authorization', 'Bearer test-token-123');
        });
    }

    public function test_get_beneficiary_returns_data(): void
    {
        Http::fake([
            '*/beneficiaries/42' => Http::response([
                'data' => [
                    'id' => 42,
                    'type' => 'persona_fisica',
                    'nombre' => 'Carlos',
                    'apellidos' => 'García',
                    'address_municipality' => 'Oaxaca de Juárez',
                ],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $result = $this->client->getBeneficiary(42);

        $this->assertEquals(42, $result['data']['id']);
        $this->assertEquals('Carlos', $result['data']['nombre']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/beneficiaries/42')
                && $request->hasHeader('Authorization', 'Bearer test-token-123');
        });
    }

    public function test_upsert_beneficiary_sends_correct_payload(): void
    {
        Http::fake([
            '*/beneficiaries' => Http::response([
                'beneficiary_id' => 42,
                'created' => true,
            ], 201),
        ]);
        Http::preventStrayRequests();

        $data = [
            'curp_rfc' => 'GARC850101HOCRRL09',
            'type' => 'persona_fisica',
            'nombre' => 'Carlos',
            'apellidos' => 'García López',
            'address_municipality' => 'Oaxaca de Juárez',
            'address_state' => 'Oaxaca',
        ];

        $result = $this->client->upsertBeneficiary($data);

        $this->assertEquals(42, $result['beneficiary_id']);
        $this->assertTrue($result['created']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/beneficiaries')
                && $request['curp_rfc'] === 'GARC850101HOCRRL09';
        });
    }

    public function test_get_program_coverage_returns_stats(): void
    {
        Http::fake([
            '*/programs/3/coverage' => Http::response([
                'data' => [
                    'total_enrollments' => 500,
                    'aprobados' => 450,
                    'rechazados' => 20,
                    'pendientes' => 30,
                ],
            ], 200),
        ]);
        Http::preventStrayRequests();

        $result = $this->client->getProgramCoverage(3);

        $this->assertEquals(500, $result['data']['total_enrollments']);
        $this->assertEquals(450, $result['data']['aprobados']);
    }

    public function test_get_program_coverage_cachea_60s_por_program_y_period(): void
    {
        Http::fake([
            '*/programs/5/coverage*' => Http::response(['data' => ['total_enrollments' => 99]], 200),
        ]);
        Http::preventStrayRequests();

        // Dos llamadas idénticas: solo 1 HTTP gracias al cache.
        $a = $this->client->getProgramCoverage(5);
        $b = $this->client->getProgramCoverage(5);

        $this->assertSame(99, $a['data']['total_enrollments']);
        $this->assertSame(99, $b['data']['total_enrollments']);
        Http::assertSentCount(1);
    }

    public function test_get_program_coverage_no_colisiona_entre_periodos_distintos(): void
    {
        Http::fake([
            '*/programs/5/coverage*' => Http::response(['data' => ['total_enrollments' => 50]], 200),
        ]);
        Http::preventStrayRequests();

        // Cada (program, period) tiene su propio slot de cache.
        $this->client->getProgramCoverage(5);                // all-time
        $this->client->getProgramCoverage(5, '2026-Q1');     // Q1
        $this->client->getProgramCoverage(5, '2026-Q2');     // Q2
        $this->client->getProgramCoverage(5, '2026-Q1');     // hit (Q1 cached)

        // 3 misses + 1 hit = 3 HTTP.
        Http::assertSentCount(3);
    }

    public function test_get_program_coverage_use_cache_false_bypassea_cache(): void
    {
        Http::fake([
            '*/programs/5/coverage*' => Http::response(['data' => ['total_enrollments' => 12]], 200),
        ]);
        Http::preventStrayRequests();

        // Sin cache: cada llamada hace HTTP, aunque sean idénticas.
        $this->client->getProgramCoverage(5, null, useCache: false);
        $this->client->getProgramCoverage(5, null, useCache: false);
        $this->client->getProgramCoverage(5, null, useCache: false);

        Http::assertSentCount(3);
    }

    public function test_get_program_coverage_no_cachea_respuestas_de_error(): void
    {
        // Si geobase responde 5xx, el throw NO debe cachear: el siguiente intento
        // debe volver a pegarle a geobase (Cache::remember no almacena excepciones).
        Http::fake([
            '*/programs/5/coverage*' => Http::response(['error' => 'down'], 500),
        ]);
        Http::preventStrayRequests();

        $threwFirst = false;
        $threwSecond = false;
        try {
            $this->client->getProgramCoverage(5);
        } catch (GeoBaseException) {
            $threwFirst = true;
        }
        try {
            $this->client->getProgramCoverage(5);
        } catch (GeoBaseException) {
            $threwSecond = true;
        }

        $this->assertTrue($threwFirst, 'Primer intento debe lanzar exception');
        $this->assertTrue($threwSecond, 'Segundo intento NO debe estar cacheado — debe re-lanzar');
    }

    public function test_get_component_coverage_hits_components_endpoint(): void
    {
        Http::fake([
            '*/components/42/coverage' => Http::response([
                'spp_mir_nivel_id' => 42,
                'component_name' => 'Componente C1',
                'total_enrollments' => 87,
                'total_beneficiaries' => 80,
            ], 200),
        ]);
        Http::preventStrayRequests();

        $result = $this->client->getComponentCoverage(42);

        $this->assertSame(42, $result['spp_mir_nivel_id']);
        $this->assertSame(87, $result['total_enrollments']);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/components/42/coverage'));
    }

    public function test_request_snapshot_sends_params(): void
    {
        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => [
                    'id' => 7,
                    'snapshot_hash' => 'a1b2c3d4e5f6',
                    'valor_oficial' => 150,
                    'evidencia_url' => 'https://minio.local/snapshots/7.csv',
                ],
            ], 201),
        ]);
        Http::preventStrayRequests();

        $result = $this->client->requestSnapshot([
            'spp_mir_nivel_id' => 45,
            'period' => '2026-Q1',
            'cutoff_date' => '2026-03-31',
        ]);

        $this->assertEquals('a1b2c3d4e5f6', $result['data']['snapshot_hash']);
        $this->assertEquals(150, $result['data']['valor_oficial']);
    }

    public function test_validate_curp_returns_existence(): void
    {
        Http::fake([
            '*/validation/curp' => Http::response([
                'exists' => true,
                'beneficiary_id' => 42,
                'active' => true,
            ], 200),
        ]);
        Http::preventStrayRequests();

        $result = $this->client->validateCurp('GARC850101HOCRRL09');

        $this->assertTrue($result['exists']);
        $this->assertEquals(42, $result['beneficiary_id']);
    }

    public function test_throws_exception_on_server_error(): void
    {
        Http::fake([
            '*/beneficiaries/999' => Http::response(['error' => 'Not found'], 404),
        ]);
        Http::preventStrayRequests();

        $this->expectException(GeoBaseException::class);

        $this->client->getBeneficiary(999);
    }

    public function test_throws_exception_on_connection_failure(): void
    {
        Http::fake([
            '*/beneficiaries/1' => Http::response(null, 500),
        ]);
        Http::preventStrayRequests();

        $this->expectException(GeoBaseException::class);

        $this->client->getBeneficiary(1);
    }

    public function test_sends_authorization_header(): void
    {
        Http::fake([
            '*/beneficiaries/1' => Http::response(['data' => ['id' => 1]], 200),
        ]);
        Http::preventStrayRequests();

        $this->client->getBeneficiary(1);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-token-123')
                && $request->hasHeader('Accept', 'application/json');
        });
    }
}
