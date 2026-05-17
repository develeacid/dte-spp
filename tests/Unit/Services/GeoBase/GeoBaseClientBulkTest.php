<?php

namespace Tests\Unit\Services\GeoBase;

use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoBaseClientBulkTest extends TestCase
{
    public function test_get_cobertura_municipal_bulk_envia_payload_correcto(): void
    {
        Http::fake([
            '*reportes/cobertura-municipal-bulk*' => Http::response(['data' => []], 200),
        ]);
        Http::preventStrayRequests();

        app(GeoBaseClient::class)->getCoberturaMunicipalBulk(
            sppProgramIds: [1, 2],
            ejercicios: [['ejercicio_fiscal' => 2026, 'fechas_corte' => ['2026-03-31']]],
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'cobertura-municipal-bulk')
                && str_contains($url, 'spp_program_ids%5B0%5D=1')
                && str_contains($url, 'ejercicios%5B0%5D%5Bejercicio_fiscal%5D=2026');
        });
    }

    public function test_get_desagregacion_bulk_envia_ejercicio_fiscal(): void
    {
        Http::fake(['*desagregacion-bulk*' => Http::response(['data' => []], 200)]);
        Http::preventStrayRequests();

        app(GeoBaseClient::class)->getDesagregacionBulk([5, 7], 2026);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'ejercicio_fiscal=2026'));
    }

    public function test_get_cobertura_geografica_bulk_envia_solo_program_ids(): void
    {
        Http::fake(['*cobertura-geografica-bulk*' => Http::response(['data' => []], 200)]);
        Http::preventStrayRequests();

        app(GeoBaseClient::class)->getCoberturaGeograficaBulk([10]);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'spp_program_ids%5B0%5D=10'));
    }

    public function test_lanza_excepcion_en_422(): void
    {
        config(['services.geobase.retry_times' => 1]);

        Http::fake(['*cobertura-municipal-bulk*' => Http::response(['errors' => ['x']], 422)]);
        Http::preventStrayRequests();

        $this->expectException(GeoBaseException::class);
        app(GeoBaseClient::class)->getCoberturaMunicipalBulk([1], []);
    }
}
