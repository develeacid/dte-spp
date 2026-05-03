<?php

namespace App\Services\Transparencia\Publishing;

use App\Models\Mml\Indicador;
use App\Models\Transparencia\DatasetAbierto;

class MirIndicadoresPublisher extends BasePublisher
{
    public function code(): string
    {
        return 'DS-02';
    }

    protected function tabla(): string
    {
        return 'pub_mir_indicadores';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $now = now();

        return Indicador::query()
            ->with(['mirNivel.programa', 'mediosVerificacion'])
            ->orderBy('id')
            ->get()
            ->filter(fn ($i) => $i->mirNivel?->programa)
            ->map(function ($i) use ($now) {
                $nivel = $i->mirNivel;
                $programa = $nivel->programa;

                $medios = $i->mediosVerificacion
                    ->map(fn ($m) => $m->descripcion ?? $m->nombre)
                    ->filter()
                    ->implode('; ');

                return [
                    'ejercicio_fiscal' => $programa->ejercicio_fiscal,
                    'programa_clave' => $programa->clave,
                    'mir_nivel' => $nivel->tipo_nivel?->value ?? (string) $nivel->tipo_nivel,
                    'resumen_narrativo' => $nivel->resumen_narrativo ?? '',
                    'supuestos' => $nivel->supuestos,
                    'indicador_nombre' => $i->nombre,
                    'formula_texto' => $i->formula_texto ?? '',
                    'medios_verificacion' => $medios !== '' ? $medios : null,
                    'meta_anual' => $i->meta,
                    'linea_base' => $i->linea_base,
                    'frecuencia' => $i->frecuencia?->value ?? (string) $i->frecuencia,
                    'sentido' => $i->sentido?->value ?? (string) ($i->sentido ?? 'ascendente'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();
    }
}
