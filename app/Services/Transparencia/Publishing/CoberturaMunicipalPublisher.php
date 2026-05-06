<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\GeoBase\GeoBaseClient;
use Illuminate\Support\Carbon;

class CoberturaMunicipalPublisher extends BasePublisher
{
    public function __construct(private GeoBaseClient $client) {}

    public function code(): string
    {
        return 'DS-G01';
    }

    protected function tabla(): string
    {
        return 'pub_cobertura_municipal';
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

        // Descubrir ejercicios fiscales con metas publicadas. Generamos siempre
        // los 4 cortes trimestrales del año (independiente de la frecuencia
        // de los indicadores: mensual/trimestral/semestral/anual). El portal
        // muestra cobertura acumulada al cierre de cada Q.
        $ejerciciosConMetas = MetaPeriodo::query()
            ->join('indicadores', 'indicadores.id', '=', 'metas_periodo.indicador_id')
            ->join('mir_niveles', 'mir_niveles.id', '=', 'indicadores.mir_nivel_id')
            ->whereIn('mir_niveles.programa_presupuestario_id', $sppIds)
            ->distinct()
            ->pluck('metas_periodo.ejercicio_fiscal');

        $ejercicios = $ejerciciosConMetas->map(fn ($anio) => [
            'ejercicio_fiscal' => (int) $anio,
            'fechas_corte' => collect([1, 2, 3, 4])->map(
                fn ($q) => Carbon::create((int) $anio, $q * 3, 1)->endOfMonth()->toDateString()
            )->all(),
        ])->values()->all();

        if (empty($ejercicios)) {
            return [];
        }

        $response = $this->client->getCoberturaMunicipalBulk($sppIds, $ejercicios);
        $now = now();

        return collect($response['data'] ?? [])
            ->map(function (array $row) use ($programas, $now) {
                $programa = $programas->firstWhere('id', $row['spp_program_id']);
                if (! $programa) {
                    return null;
                }

                return [
                    'ejercicio_fiscal' => (int) $row['ejercicio_fiscal'],
                    'trimestre' => (int) $row['trimestre'],
                    'programa_nombre' => $programa->nombre,
                    'municipio_clave' => (string) $row['municipio_clave'],
                    'municipio_nombre' => (string) $row['municipio_nombre'],
                    'total_beneficiarios' => (int) $row['total_beneficiarios'],
                    'total_inscripciones' => (int) $row['total_inscripciones'],
                    'monto_total' => isset($row['monto_total']) ? (float) $row['monto_total'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
