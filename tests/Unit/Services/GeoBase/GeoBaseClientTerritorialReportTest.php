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
            return str_starts_with($request->url(), 'https://geobase.test/api/v1/geobase/territorial-report?')
                && str_contains($request->url(), 'program_id=42')
                && ! str_contains($request->url(), 'component_id=')
                && ! str_contains($request->url(), 'municipio_id=')
                && $request->method() === 'GET'
                && $request->hasHeader('Authorization', 'Bearer fake-token')
                && $request->hasHeader('Accept', 'application/json');
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

    public function test_get_territorial_report_throws_on_non_2xx(): void
    {
        config(['services.geobase.url' => 'https://geobase.test/api/v1/geobase']);
        config(['services.geobase.token' => 'fake-token']);
        config(['services.geobase.retry_times' => 1]);  // keep the test fast

        Http::fake(['*' => Http::response(['error' => 'boom'], 500)]);

        $this->expectException(\App\Services\GeoBase\GeoBaseException::class);

        app(\App\Services\GeoBase\GeoBaseClient::class)->getTerritorialReport(42);
    }

    public function test_get_territorial_report_rejects_non_positive_program_id(): void
    {
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\GeoBase\GeoBaseClient::class)->getTerritorialReport(0);
    }
}
