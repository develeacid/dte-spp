<?php

namespace App\Services\Transparencia\Publishing;

class PublisherResolver
{
    /**
     * Mapeo dataset_clave → FQCN del publisher.
     * DS-00 (políticas) deliberadamente NO mapeados (no son data, son texto).
     */
    private const MAP = [
        'DS-01' => ProgramasPublisher::class,
        'DS-02' => MirIndicadoresPublisher::class,
        'DS-03' => AvancesTrimestralesPublisher::class,
        'DS-04' => EvaluacionesAnualesPublisher::class,
        'DS-05' => AlineacionEstrategicaPublisher::class,
        'DS-G01' => CoberturaMunicipalPublisher::class,
        'DS-G02' => DesagregacionDemograficaPublisher::class,
        'DS-G03' => CoberturaGeograficaPublisher::class,
        'DS-G04' => EvolucionTemporalPublisher::class,
    ];

    public function for(string $code): ?PublisherInterface
    {
        $class = self::MAP[$code] ?? null;

        return $class ? app($class) : null;
    }

    /**
     * @return array<string>
     */
    public function supportedCodes(): array
    {
        return array_keys(self::MAP);
    }
}
