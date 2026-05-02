<?php

namespace Database\Seeders;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TransparenciaPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate(SystemPermission::APROBAR_DATOS_ABIERTOS->value, 'web');
        Permission::findOrCreate(SystemPermission::VER_DATASETS_ABIERTOS->value, 'web');
        Permission::findOrCreate(SystemPermission::GESTIONAR_DATASET_ABIERTO->value, 'web');

        // Segregación de funciones: admin NO recibe aprobar_datos_abiertos
        // (a diferencia de PadronPermissionsSeeder y JuridicoPermissionsSeeder).
        // Solo el rol RDA puede aprobar publicación de datasets abiertos.
        $rda = Role::findOrCreate(SystemRole::RESPONSABLE_DATOS_ABIERTOS->value, 'web');
        $rda->givePermissionTo([
            SystemPermission::APROBAR_DATOS_ABIERTOS->value,
            SystemPermission::VER_DATASETS_ABIERTOS->value,
            SystemPermission::GESTIONAR_DATASET_ABIERTO->value,
        ]);
    }
}
