<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Enums\SystemPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché obligatoriamente
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos de forma segura (evita duplicidad)
        foreach (SystemPermission::cases() as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        // Crear roles de forma segura y asignar permisos
        // Permisos segregados: NO se otorgan automáticamente a admin (segregación de funciones).
        // Cada uno se asigna al rol específico via su seeder de dominio.
        $permisosSegregados = [
            SystemPermission::APROBAR_DATOS_ABIERTOS->value, // solo rol RDA (TransparenciaPermissionsSeeder)
        ];

        $admin = Role::findOrCreate(SystemRole::ADMIN->value, 'web');
        $admin->givePermissionTo(
            Permission::whereNotIn('name', $permisosSegregados)->get()
        );

        $planeador = Role::findOrCreate(SystemRole::PLANEADOR->value, 'web');
        $planeador->givePermissionTo([
            SystemPermission::GESTIONAR_CATALOGOS->value,
            SystemPermission::CREAR_PROGRAMA->value,
            SystemPermission::EDITAR_MIR->value,
            SystemPermission::REVISAR_AVANCE->value,
            SystemPermission::APROBAR_AVANCE->value,
            SystemPermission::EXPORTAR_REPORTES->value,
            SystemPermission::VER_SABANA_CAPTURA->value,
            SystemPermission::VER_CONCENTRADO_CAPTURA->value,
        ]);

        $operador = Role::findOrCreate(SystemRole::OPERADOR->value, 'web');
        $operador->givePermissionTo([
            SystemPermission::CAPTURAR_AVANCE->value,
            SystemPermission::EXPORTAR_REPORTES->value,
            SystemPermission::VER_SABANA_CAPTURA->value,
            SystemPermission::VER_CONCENTRADO_CAPTURA->value,
        ]);
    }
}
