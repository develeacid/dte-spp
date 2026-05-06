<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\GeoBase\GeoBaseClient;

class CoberturaGeograficaPublisher extends BasePublisher
{
    public function __construct(private GeoBaseClient $client) {}

    public function code(): string
    {
        return 'DS-G03';
    }

    protected function tabla(): string
    {
        return 'pub_cobertura_geografica';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $programas = ProgramaPresupuestario::query()
            ->where('padron_geobase_activo', true)
            ->get(['id', 'nombre']);

        if ($programas->isEmpty()) {
            return [];
        }

        $resp = $this->client->getCoberturaGeograficaBulk($programas->pluck('id')->all());
        $now = now();

        return collect($resp['data'] ?? [])
            ->map(function (array $r) use ($programas, $now) {
                $programa = $programas->firstWhere('id', $r['spp_program_id']);
                if (! $programa) {
                    return null;
                }

                $municipios = $r['municipios_incluidos'] ?? [];

                return [
                    'programa_nombre' => $programa->nombre,
                    'geojson' => json_encode($r['geojson']),
                    'area_km2' => isset($r['area_km2']) ? (float) $r['area_km2'] : null,
                    'municipios_incluidos' => $this->arrayToPgTextArrayLiteral($municipios),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Convierte un array PHP de strings a literal Postgres text[]:
     *   ['20001', '20067'] → '{"20001","20067"}'
     * El driver pgsql lo castea automáticamente al INSERT contra una columna text[].
     *
     * @param  array<int, mixed>  $values
     */
    private function arrayToPgTextArrayLiteral(array $values): string
    {
        if (empty($values)) {
            return '{}';
        }

        $escaped = array_map(function ($v) {
            $v = (string) $v;
            $v = str_replace('\\', '\\\\', $v);
            $v = str_replace('"', '\\"', $v);

            return '"'.$v.'"';
        }, $values);

        return '{'.implode(',', $escaped).'}';
    }
}
