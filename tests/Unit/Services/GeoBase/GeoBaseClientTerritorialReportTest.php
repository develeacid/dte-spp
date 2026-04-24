<?php

namespace Tests\Unit\Services\GeoBase;

use App\Services\GeoBase\GeoBaseClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoBaseClientTerritorialReportTest extends TestCase
{
    public function test_get_territorial_report_hits_expected_endpoint(): void
    {
        config(['services.geobase.url' => 'https://geobase.test/api/v1/geobase']);
        config(['services.geobase.token' => 'fake-token']);

        Http::fake([
            'https://geobase.test/api/v1/geobase/territorial-report*' => Http::response([
                'data' => [[
                    'municipio_id' => 1,
                    'program_id' => 42,
                    'por_genero' => ['masculino' => 10, 'femenino' => 12, 'otro' => 1],
                ]],
                'meta' => ['total_rows' => 1, 'refreshed_at' => '2026-04-24T12:00:00Z'],
            ], 200),
        ]);

        $result = app(GeoBaseClient::class)->getTerritorialReport(programId: 42);

        $this->assertSame(1, $result['meta']['total_rows']);
        $this->assertSame(42, $result['data'][0]['program_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://geobase.test/api/v1/geobase/territorial-report?program_id=42'
                && $request->hasHeader('Authorization', 'Bearer fake-token');
        });
    }

    public function test_get_territorial_report_forwards_component_filter(): void
    {
        config(['services.geobase.url' => 'https://geobase.test/api/v1/geobase']);
        config(['services.geobase.token' => 'fake-token']);

        Http::fake([
            '*' => Http::response(['data' => [], 'meta' => []], 200),
        ]);

        app(GeoBaseClient::class)->getTerritorialReport(programId: 42, componentId: 7);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'program_id=42')
            && str_contains($request->url(), 'component_id=7')
        );
    }
}
