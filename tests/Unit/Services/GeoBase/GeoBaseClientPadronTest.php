<?php

namespace Tests\Unit\Services\GeoBase;

use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use BadMethodCallException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoBaseClientPadronTest extends TestCase
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

    public function test_get_snapshots_lista_snapshots_de_un_programa_componente(): void
    {
        Http::fake([
            '*/snapshots*' => Http::response([
                'data' => [
                    ['id' => 1, 'snapshot_hash' => 'abc', 'cutoff_date' => '2026-03-31'],
                    ['id' => 2, 'snapshot_hash' => 'def', 'cutoff_date' => '2026-06-30'],
                ],
                'meta' => ['total' => 2, 'per_page' => 15, 'current_page' => 1],
            ], 200),
        ]);

        $result = $this->client->getSnapshots(programId: 12, componentId: 3);

        $this->assertCount(2, $result['data']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'program_id=12')
                && str_contains($request->url(), 'component_id=3')
                && $request->hasHeader('Authorization', 'Bearer test-token-123');
        });
    }

    public function test_get_snapshots_omite_component_id_cuando_no_se_pasa(): void
    {
        Http::fake([
            '*/snapshots*' => Http::response(['data' => [], 'meta' => []], 200),
        ]);

        $this->client->getSnapshots(programId: 12);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'program_id=12')
                && ! str_contains($request->url(), 'component_id');
        });
    }

    public function test_get_snapshot_kpis_retorna_metadata_desagregados(): void
    {
        Http::fake([
            '*/snapshots/892' => Http::response([
                'data' => [
                    'id' => 892,
                    'snapshot_hash' => 'e3b0c4...',
                    'row_count' => 1847,
                    'valor_oficial' => 1820,
                    'cutoff_date' => '2026-03-31T23:59:59Z',
                    'metadata' => [
                        'program_name' => 'Programa Mezcalero',
                        'component_name' => 'Componente 1',
                        'desagregados' => [
                            'por_genero' => ['M' => 950, 'H' => 897],
                            'por_grupo_edad' => ['18-29' => 600, '30-44' => 800, '45-59' => 300, '60+' => 147],
                            'por_indigena' => ['indigena' => 412, 'no_indigena' => 1435],
                            'por_discapacidad' => ['con_discapacidad' => 89, 'sin_discapacidad' => 1758],
                            'por_pueblo' => [],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->client->getSnapshotKpis(892);

        $this->assertSame(892, $result['data']['id']);
        $this->assertSame(1847, $result['data']['row_count']);
        $this->assertSame(412, $result['data']['metadata']['desagregados']['por_indigena']['indigena']);
    }

    public function test_get_snapshot_kpis_propaga_geobase_exception_en_404(): void
    {
        Http::fake([
            '*/snapshots/999' => Http::response(['message' => 'Not found'], 404),
        ]);

        $this->expectException(GeoBaseException::class);
        $this->client->getSnapshotKpis(999);
    }

    public function test_get_program_component_kpis_lanza_excepcion_modo_vivo_deshabilitado(): void
    {
        // El modo "vivo" requiere componer ≥3 endpoints (equidad-genero,
        // densidad-etnica, cobertura-componente). Se deshabilita en N3
        // (Opción B del design doc, sec. 9). Habilitar en sprint posterior.
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Modo vivo deshabilitado');

        $this->client->getProgramComponentKpis(programId: 12, componentId: 3);
    }

    public function test_request_snapshot_pega_a_snapshots_generate(): void
    {
        Http::fake([
            '*/snapshots/generate' => Http::response([
                'data' => [
                    'id' => 4421,
                    'snapshot_hash' => 'a3f7c9e2deadbeef',
                    'row_count' => 1847,
                    'cutoff_date' => '2026-03-31',
                    'period' => '2026-Q1',
                ],
            ], 201),
        ]);

        $result = $this->client->requestSnapshot([
            'program_id' => 12,
            'component_id' => 3,
            'period' => '2026-Q1',
            'cutoff_date' => '2026-03-31',
        ]);

        $this->assertSame(4421, $result['data']['id']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/snapshots/generate')
                && $request['period'] === '2026-Q1';
        });
    }
}
