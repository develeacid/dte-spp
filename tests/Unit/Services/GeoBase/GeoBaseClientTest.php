<?php

namespace Tests\Unit\Services\GeoBase;

use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
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

        $this->client = app(GeoBaseClient::class);
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

        $result = $this->client->getProgramCoverage(3);

        $this->assertEquals(500, $result['data']['total_enrollments']);
        $this->assertEquals(450, $result['data']['aprobados']);
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

        $result = $this->client->requestSnapshot([
            'component_id' => 45,
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

        $result = $this->client->validateCurp('GARC850101HOCRRL09');

        $this->assertTrue($result['exists']);
        $this->assertEquals(42, $result['beneficiary_id']);
    }

    public function test_throws_exception_on_server_error(): void
    {
        Http::fake([
            '*/beneficiaries/999' => Http::response(['error' => 'Not found'], 404),
        ]);

        $this->expectException(GeoBaseException::class);

        $this->client->getBeneficiary(999);
    }

    public function test_throws_exception_on_connection_failure(): void
    {
        Http::fake([
            '*/beneficiaries/1' => Http::response(null, 500),
        ]);

        $this->expectException(GeoBaseException::class);

        $this->client->getBeneficiary(1);
    }

    public function test_sends_authorization_header(): void
    {
        Http::fake([
            '*/beneficiaries/1' => Http::response(['data' => ['id' => 1]], 200),
        ]);

        $this->client->getBeneficiary(1);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-token-123')
                && $request->hasHeader('Accept', 'application/json');
        });
    }
}
