<?php

namespace Database\Seeders;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PadronPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            SystemPermission::VER_PADRON,
            SystemPermission::GENERAR_SNAPSHOT_PADRON,
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        $admin = Role::findOrCreate(SystemRole::ADMIN->value, 'web');
        $admin->givePermissionTo(array_map(fn ($p) => $p->value, $permisos));

        $verPadron = SystemPermission::VER_PADRON->value;
        foreach ([
            SystemRole::PLANEADOR,
            SystemRole::OPERADOR,
            SystemRole::ANALISTA_FINANCIERO,
            SystemRole::ANALISTA_JURIDICO,
        ] as $rol) {
            Role::findOrCreate($rol->value, 'web')->givePermissionTo($verPadron);
        }

        $generar = SystemPermission::GENERAR_SNAPSHOT_PADRON->value;
        foreach ([SystemRole::PLANEADOR, SystemRole::OPERADOR] as $rol) {
            Role::findOrCreate($rol->value, 'web')->givePermissionTo($generar);
        }
    }
}
