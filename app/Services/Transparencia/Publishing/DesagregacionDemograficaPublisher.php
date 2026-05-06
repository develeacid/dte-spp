<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\GeoBase\GeoBaseClient;

class DesagregacionDemograficaPublisher extends BasePublisher
{
    public function __construct(private GeoBaseClient $client) {}

    public function code(): string
    {
        return 'DS-G02';
    }

    protected function tabla(): string
    {
        return 'pub_desagregacion_demografica';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $programas = ProgramaPresupuestario::query()
            ->where('padron_geobase_activo', true)
            ->get(['id', 'nombre']);

        if ($programas->isEmpty()) {
            return [];
        }

        $sppIds = $programas->pluck('id')->all();

        $ejercicios = MetaPeriodo::query()
            ->join('indicadores', 'indicadores.id', '=', 'metas_periodo.indicador_id')
            ->join('mir_niveles', 'mir_niveles.id', '=', 'indicadores.mir_nivel_id')
            ->whereIn('mir_niveles.programa_presupuestario_id', $sppIds)
            ->distinct()
            ->pluck('metas_periodo.ejercicio_fiscal');

        if ($ejercicios->isEmpty()) {
            return [];
        }

        $now = now();
        $rows = [];
        foreach ($ejercicios as $ejercicio) {
            $resp = $this->client->getDesagregacionBulk($sppIds, (int) $ejercicio);
            foreach ($resp['data'] ?? [] as $r) {
                $programa = $programas->firstWhere('id', $r['spp_program_id']);
                if (! $programa) {
                    continue;
                }

                $dimensiones = [
                    'sexo' => $r['por_genero'] ?? [],
                    'grupo_edad' => $r['por_grupo_edad'] ?? [],
                    'discapacidad' => $r['por_tipo_discapacidad'] ?? [],
                    'pueblo' => $r['por_etnia'] ?? [],
                ];

                foreach ($dimensiones as $dim => $categorias) {
                    $totalDim = array_sum(array_map('intval', $categorias));
                    foreach ($categorias as $cat => $count) {
                        $count = (int) $count;
                        // Omit 0 except sexo M/F que siempre se incluyen
                        if ($count === 0 && ! ($dim === 'sexo' && in_array($cat, ['masculino', 'femenino']))) {
                            continue;
                        }
                        $rows[] = [
                            'ejercicio_fiscal' => (int) $ejercicio,
                            'programa_nombre' => $programa->nombre,
                            'municipio_clave' => ! empty($r['municipio_clave']) ? (string) $r['municipio_clave'] : null,
                            'dimension' => $dim,
                            'categoria' => $cat,
                            'total_beneficiarios' => $count,
                            'porcentaje' => $totalDim > 0 ? round($count / $totalDim * 100, 2) : null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        return array_merge($rows, $this->aggregateEstatal($rows, $now));
    }

    /**
     * Filas estatales: agregan todos los municipios por (ejercicio, programa, dimension, categoria)
     * con municipio_clave=NULL. Porcentaje recalculado a nivel estatal.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function aggregateEstatal(array $rows, $now): array
    {
        // Solo agregar filas con municipio_clave NO null (las municipales)
        $municipales = collect($rows)->filter(fn ($r) => $r['municipio_clave'] !== null);
        if ($municipales->isEmpty()) {
            return [];
        }

        // Sum por (ejercicio, programa, dimension, categoria)
        $agg = $municipales
            ->groupBy(fn ($r) => "{$r['ejercicio_fiscal']}|{$r['programa_nombre']}|{$r['dimension']}|{$r['categoria']}")
            ->map(function ($group) use ($now) {
                $first = $group->first();

                return [
                    'ejercicio_fiscal' => $first['ejercicio_fiscal'],
                    'programa_nombre' => $first['programa_nombre'],
                    'municipio_clave' => null,
                    'dimension' => $first['dimension'],
                    'categoria' => $first['categoria'],
                    'total_beneficiarios' => $group->sum('total_beneficiarios'),
                    'porcentaje' => null,  // recalcular abajo
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values();

        // Recalcular porcentaje estatal por (ejercicio, programa, dimension)
        return $agg
            ->groupBy(fn ($r) => "{$r['ejercicio_fiscal']}|{$r['programa_nombre']}|{$r['dimension']}")
            ->flatMap(function ($group) {
                $totalDim = $group->sum('total_beneficiarios');

                return $group->map(function ($r) use ($totalDim) {
                    $r['porcentaje'] = $totalDim > 0 ? round($r['total_beneficiarios'] / $totalDim * 100, 2) : null;

                    return $r;
                });
            })
            ->values()
            ->all();
    }
}
