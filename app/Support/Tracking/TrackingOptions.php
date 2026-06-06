<?php

namespace App\Support\Tracking;

use Illuminate\Support\Collection;

final class TrackingOptions
{
    public static function programas(Collection $programas): array
    {
        return $programas->mapWithKeys(fn ($p) => [$p->id => $p->clave.' - '.$p->nombre])->toArray();
    }

    public static function estados(): array
    {
        return [
            'pendiente' => 'Pendiente',
            'en_captura' => 'En captura',
            'en_revision' => 'En revisión',
            'aprobado' => 'Aprobado',
            'observado' => 'Observado',
            'vencido' => 'Vencido',
        ];
    }

    public static function semaforos(): array
    {
        return [
            'verde' => 'Verde',
            'amarillo' => 'Amarillo',
            'rojo' => 'Rojo',
            'gris' => 'Sin datos',
        ];
    }

    /**
     * @param  Collection<int, \App\Models\Mml\MirNivel>  $niveles  ya ordenados jerárquicamente por la vista
     */
    public static function niveles(Collection $niveles): array
    {
        return $niveles->mapWithKeys(fn ($n) => [$n->id => $n->trazabilidad()->clave().' · '.$n->trazabilidad()->nivel()])->toArray();
    }
}
