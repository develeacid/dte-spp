<?php

namespace App\Http\Controllers\Mml;

use App\Http\Controllers\Controller;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Services\GeoBase\GeoBaseException;
use App\Support\PeriodRange;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MapaCoberturaProgramaController extends Controller
{
    private const CACHE_TTL_SECONDS = 60;

    public function __invoke(Request $request, ProgramaPresupuestario $programa, GeoBaseClient $client): Response
    {
        $period = $request->query('period');
        $dateFilters = [];
        if (is_string($period) && $period !== '') {
            try {
                $dateFilters = PeriodRange::fromQuarterString($period)->toFilterArray();
            } catch (InvalidArgumentException) {
                // periodo malformado: ignorar, all-time
            }
        }

        $queryConfig = [
            'group_by' => ['municipio'],
            'aggregates' => ['total_beneficiarios'],
            'filters' => array_merge(
                // geobase resuelve por spp_program_id (contrato M2M), no por su PK interno:
                // así el mapa cae en el mismo programa que el panel de cobertura (getProgramCoverage).
                ['spp_program_id' => $programa->id],
                $dateFilters,
            ),
        ];

        $cacheFragment = empty($dateFilters)
            ? 'all'
            : "{$dateFilters['date_from']}_{$dateFilters['date_to']}";
        $cacheKey = "geobase:map:program:{$programa->id}:{$cacheFragment}";
        try {
            $png = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $client->getConsultaImage($queryConfig));
        } catch (GeoBaseException|ConnectionException $e) {
            Log::warning('mapa cobertura: fetch a geobase falló', [
                'program_id' => $programa->id,
                'period' => $period,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response('', 503);
        }

        return response($png, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'private, max-age='.self::CACHE_TTL_SECONDS);
    }
}
