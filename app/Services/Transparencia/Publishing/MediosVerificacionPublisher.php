<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Mml\MedioVerificacion;
use App\Models\Transparencia\DatasetAbierto;

class MediosVerificacionPublisher extends BasePublisher
{
    public function code(): string
    {
        return 'DS-07';
    }

    protected function tabla(): string
    {
        return 'pub_medios_verificacion';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $now = now();

        return MedioVerificacion::query()
            ->with(['indicador.mirNivel.programa'])
            ->orderBy('id')
            ->get()
            ->filter(fn ($mv) => $mv->indicador?->mirNivel?->programa)
            ->map(function ($mv) use ($now) {
                $indicador = $mv->indicador;
                $nivel = $indicador->mirNivel;
                $programa = $nivel->programa;

                return [
                    'ejercicio_fiscal' => $programa->ejercicio_fiscal,
                    'programa_clave' => $programa->clave,
                    'mir_nivel' => $nivel->tipo_nivel?->value ?? (string) $nivel->tipo_nivel,
                    'indicador_nombre' => $indicador->nombre,
                    'mv_nombre' => $mv->nombre,
                    'descripcion' => $mv->descripcion,
                    'fuente' => $mv->fuente,
                    'organismo' => $mv->organismo,
                    'url' => $mv->url,
                    'frecuencia' => $mv->frecuencia,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();
    }
}
