<?php

namespace Database\Seeders\Evaluation;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EvaluacionExternaPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            SystemPermission::VER_EVALUACION_EXTERNA,
            SystemPermission::GESTIONAR_EVALUACION_EXTERNA,
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        // Todos los roles reciben ver_evaluacion_externa (mismo set que ver_asm:
        // lectura amplia para auditoría y consistencia).
        foreach (SystemRole::cases() as $role) {
            $rol = Role::findOrCreate($role->value, 'web');
            $rol->givePermissionTo(SystemPermission::VER_EVALUACION_EXTERNA->value);
        }

        // gestionar_evaluacion_externa SOLO para planeador y admin
        // (la evaluación externa es responsabilidad de planeación, NO de operador).
        $rolesGestion = [
            SystemRole::PLANEADOR,
            SystemRole::ADMIN,
        ];

        foreach ($rolesGestion as $role) {
            $rol = Role::findOrCreate($role->value, 'web');
            $rol->givePermissionTo(SystemPermission::GESTIONAR_EVALUACION_EXTERNA->value);
        }
    }
}
