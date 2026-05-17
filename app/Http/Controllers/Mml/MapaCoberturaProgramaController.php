<?php

namespace App\Http\Controllers\Mml;

use App\Http\Controllers\Controller;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseClient;
use App\Support\PeriodRange;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class MapaCoberturaProgramaController extends Controller
{
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
                ['program_id' => $programa->id],
                $dateFilters,
            ),
        ];

        $png = $client->getConsultaImage($queryConfig);

        return response($png, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'private, max-age=60');
    }
}
