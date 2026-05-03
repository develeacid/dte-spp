<?php

namespace App\Services\Transparencia\Publishing;

use App\Enums\EstadoPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;

class ProgramasPublisher extends BasePublisher
{
    public function code(): string
    {
        return 'DS-01';
    }

    protected function tabla(): string
    {
        return 'pub_programas';
    }

    protected function buildRows(DatasetAbierto $dataset): array
    {
        $now = now();

        return ProgramaPresupuestario::query()
            ->with('team')
            ->orderBy('id')
            ->get()
            ->map(fn ($p) => [
                'ejercicio_fiscal' => $p->ejercicio_fiscal,
                'programa_clave' => $p->clave,
                'programa_nombre' => $p->nombre,
                'unidad_responsable' => $p->team?->name ?? '',
                'modalidad' => null,
                'activo' => $p->estado === EstadoPrograma::ACTIVO,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();
    }
}
