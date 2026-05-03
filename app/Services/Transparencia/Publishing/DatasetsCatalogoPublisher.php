<?php

namespace App\Services\Transparencia\Publishing;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;

class DatasetsCatalogoPublisher extends BasePublisher
{
    public function code(): string
    {
        return '(catalogo)';
    }

    protected function tabla(): string
    {
        return 'pub_datasets_catalogo';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $now = now();

        return DatasetAbierto::query()
            ->where('status', EstadoDatasetAbierto::PUBLICADO)
            ->orderBy('dataset_clave')
            ->get()
            ->map(fn ($d) => [
                'codigo' => $d->dataset_clave,
                'titulo' => $d->nombre,
                'descripcion' => $d->descripcion ?? '',
                'fecha_publicacion' => optional($d->publicado_en)->toDateString(),
                'frecuencia_actualizacion' => null,
                'url_csv' => null,
                'url_json' => null,
                'total_registros' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();
    }
}
