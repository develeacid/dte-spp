<?php

namespace App\Services\Evaluation;

use App\Services\GeoBase\GeoBaseClient;
use App\Support\KAnonymityMasker;

class Anexo11ExportService
{
    private const GENERO_KEYS = ['masculino', 'femenino', 'otro'];

    private const GRUPO_EDAD_KEYS = ['infantes', 'ninios', 'adolescentes', 'jovenes', 'adultos', 'adultos_mayores'];

    private const TIPO_DISCAPACIDAD_KEYS = ['motriz', 'visual', 'auditiva', 'intelectual', 'psicosocial', 'multiple', 'ninguna'];

    public function __construct(
        private readonly GeoBaseClient $client,
        private readonly KAnonymityMasker $masker,
    ) {}

    /**
     * @param  int  $sppProgramId  the dte-spp programa.id (geobase resolves it)
     */
    public function build(int $sppProgramId, ?string $programaNombre = null): Anexo11ReportData
    {
        $response = $this->client->getTerritorialReport($sppProgramId);

        $rows = $response['data'] ?? [];

        $genero = $this->initBucket(self::GENERO_KEYS);
        $edad = $this->initBucket(self::GRUPO_EDAD_KEYS);
        $discapacidad = $this->initBucket(self::TIPO_DISCAPACIDAD_KEYS);
        $pueblo = [];
        $total = 0;
        $nombre = $programaNombre;

        foreach ($rows as $row) {
            $nombre ??= $row['program_name'] ?? null;
            $total += (int) ($row['total_beneficiarios'] ?? 0);
            $this->accumulate($genero, $row['por_genero'] ?? []);
            $this->accumulate($edad, $row['por_grupo_edad'] ?? []);
            $this->accumulate($discapacidad, $row['por_tipo_discapacidad'] ?? []);
            $this->accumulateDynamic($pueblo, $row['por_pueblo'] ?? []);
        }

        return new Anexo11ReportData(
            programaId: $sppProgramId,
            programaNombre: $nombre ?? '',
            totalBeneficiarios: $total,
            porGenero: $this->masker->maskBucket($genero),
            porGrupoEdad: $this->masker->maskBucket($edad),
            porPueblo: $this->masker->maskBucket($pueblo),
            porTipoDiscapacidad: $this->masker->maskBucket($discapacidad),
            refreshedAt: $response['meta']['refreshed_at'] ?? '',
        );
    }

    /**
     * @param  list<string>  $keys
     * @return array<string,int>
     */
    private function initBucket(array $keys): array
    {
        return array_fill_keys($keys, 0);
    }

    /**
     * @param  array<string,int>  $accumulator  modified in place
     * @param  array<string,int|string>|null  $increment
     */
    private function accumulate(array &$accumulator, ?array $increment): void
    {
        if (! $increment) {
            return;
        }
        foreach ($accumulator as $key => $_) {
            $accumulator[$key] += (int) ($increment[$key] ?? 0);
        }
    }

    /**
     * Used for buckets whose keys are not known in advance (por_pueblo).
     *
     * @param  array<string,int>  $accumulator  modified in place
     * @param  array<string,int|string>|null  $increment
     */
    private function accumulateDynamic(array &$accumulator, ?array $increment): void
    {
        if (! $increment) {
            return;
        }
        foreach ($increment as $key => $value) {
            $accumulator[$key] = ($accumulator[$key] ?? 0) + (int) $value;
        }
    }
}
