<?php

namespace App\Policies\Transparencia;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;

class DatasetAbiertoPolicy
{
    /**
     * Abilities donde el bypass de admin (Gate::before) NO aplica.
     * Consultadas por AppServiceProvider para preservar segregación de funciones.
     */
    public const SEGREGATED_ABILITIES = [
        'aprobar',
        'rechazar',
        'publicar',
        'retirar',
        'editarPlantilla',
    ];

    public function viewAny(User $user): bool
    {
        return $user->can('ver_datasets_abiertos');
    }

    public function view(User $user, DatasetAbierto $ds): bool
    {
        return $user->can('ver_datasets_abiertos');
    }

    public function create(User $user): bool
    {
        return $user->can('gestionar_dataset_abierto');
    }

    public function update(User $user, DatasetAbierto $ds): bool
    {
        if ($ds->status !== EstadoDatasetAbierto::BORRADOR) {
            return false;
        }
        if ($user->hasPermissionTo('aprobar_datos_abiertos')) {
            return true;
        }
        return $user->can('gestionar_dataset_abierto') && $user->id === $ds->creado_por;
    }

    public function delete(User $user, DatasetAbierto $ds): bool
    {
        return $this->update($user, $ds);
    }

    public function crearEntrega(User $user, DatasetAbierto $ds): bool
    {
        return $ds->periodo === null && $user->can('gestionar_dataset_abierto');
    }

    public function editarPlantilla(User $user, DatasetAbierto $ds): bool
    {
        return $ds->periodo === null && $user->hasPermissionTo('aprobar_datos_abiertos');
    }

    public function enviarARevision(User $user, DatasetAbierto $ds): bool
    {
        if ($ds->status !== EstadoDatasetAbierto::BORRADOR) {
            return false;
        }
        if ($user->hasPermissionTo('aprobar_datos_abiertos')) {
            return true;
        }
        return $user->can('gestionar_dataset_abierto') && $user->id === $ds->creado_por;
    }

    public function aprobar(User $user, DatasetAbierto $ds): bool
    {
        return $ds->status === EstadoDatasetAbierto::REVISION && $user->hasPermissionTo('aprobar_datos_abiertos');
    }

    public function rechazar(User $user, DatasetAbierto $ds): bool
    {
        return $this->aprobar($user, $ds);
    }

    public function publicar(User $user, DatasetAbierto $ds): bool
    {
        return $ds->status === EstadoDatasetAbierto::APROBADO && $user->hasPermissionTo('aprobar_datos_abiertos');
    }

    public function retirar(User $user, DatasetAbierto $ds): bool
    {
        return $ds->status === EstadoDatasetAbierto::PUBLICADO && $user->hasPermissionTo('aprobar_datos_abiertos');
    }
}
