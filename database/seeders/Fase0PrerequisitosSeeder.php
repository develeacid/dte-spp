<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Enums\TipoUnidadResponsable;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Fase0PrerequisitosSeeder extends Seeder
{
    public function run(): void
    {
        // Guardia de producción
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar este seeder en producción.');

            return;
        }

        $this->command->info('Fase 0: Creando prerequisitos (roles, permisos, URs, usuarios)...');

        // ── 1. Permisos y roles ──────────────────────────────────────
        $this->call([
            RolesAndPermissionsSeeder::class,
            PresupuestoPermissionsSeeder::class,
            JuridicoPermissionsSeeder::class,
            AsmPermissionsSeeder::class,
            PadronPermissionsSeeder::class,
            TransparenciaPermissionsSeeder::class,
        ]);

        // ── 2. Admin global ──────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@sistema.test'],
            [
                'name' => 'Administrador Sistema',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'activated_at' => now(),
                'active' => true,
            ]
        );
        $admin->assignRole(SystemRole::ADMIN->value);

        // ── 3. Crear 4 URs ──────────────────────────────────────────
        $urs = [
            ['clave' => 'SE-001',     'nombre' => 'Secretaría de Educación',  'titular' => 'Dr. Juan Pérez',            'tipo' => TipoUnidadResponsable::SUSTANTIVA, 'slug' => 'se'],
            ['clave' => 'SS-002',     'nombre' => 'Secretaría de Salud',      'titular' => 'Dra. María López',           'tipo' => TipoUnidadResponsable::APOYO,      'slug' => 'ss'],
            ['clave' => 'SEG-003',    'nombre' => 'Secretaría de Seguridad',  'titular' => 'Lic. Roberto Sánchez',       'tipo' => TipoUnidadResponsable::SUSTANTIVA, 'slug' => 'seg'],
            ['clave' => 'SECTUR-004', 'nombre' => 'Secretaría de Turismo',    'titular' => 'Lic. Ana García Mendoza',    'tipo' => TipoUnidadResponsable::SUSTANTIVA, 'slug' => 'sectur'],
        ];

        $tableRows = [
            ['Administrador Sistema', 'admin@sistema.test', 'admin', '-'],
        ];

        // Asignar admin a la primera UR como default team
        $firstTeam = null;

        foreach ($urs as $ur) {
            $team = Team::firstOrCreate(
                ['clave_ur' => $ur['clave']],
                [
                    'name' => $ur['nombre'],
                    'user_id' => $admin->id,
                    'titular' => $ur['titular'],
                    'tipo_ur' => $ur['tipo'],
                    'activa' => true,
                    'personal_team' => false,
                ]
            );

            if ($firstTeam === null) {
                $firstTeam = $team;
            }

            // ── 4. Crear 4 usuarios por UR ───────────────────────────
            $usuarios = [
                ['prefix' => 'planeador',  'label' => 'Planeador',         'spatie' => SystemRole::PLANEADOR,           'jetstream' => 'planeador'],
                ['prefix' => 'operador',   'label' => 'Operador',          'spatie' => SystemRole::OPERADOR,            'jetstream' => 'operador'],
                ['prefix' => 'financiero', 'label' => 'Analista Financiero', 'spatie' => SystemRole::ANALISTA_FINANCIERO, 'jetstream' => 'editor'],
                ['prefix' => 'juridico',   'label' => 'Analista Jurídico',   'spatie' => SystemRole::ANALISTA_JURIDICO,   'jetstream' => 'editor'],
            ];

            foreach ($usuarios as $def) {
                $email = "{$def['prefix']}.{$ur['slug']}@sistema.test";

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "{$def['label']} {$ur['clave']}",
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'activated_at' => now(),
                        'active' => true,
                    ]
                );

                $user->assignRole($def['spatie']->value);
                $team->users()->syncWithoutDetaching([
                    $user->id => ['role' => $def['jetstream']],
                ]);
                $user->forceFill(['current_team_id' => $team->id])->save();

                $tableRows[] = [$def['label'].' '.$ur['clave'], $email, $def['spatie']->value, $ur['nombre']];
            }
        }

        // Asignar admin a SE-001 como default team
        if ($firstTeam) {
            $firstTeam->users()->syncWithoutDetaching([
                $admin->id => ['role' => 'admin'],
            ]);
            $admin->forceFill(['current_team_id' => $firstTeam->id])->save();
        }

        // ── 5. Resumen ──────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('Usuarios creados:');
        $this->command->table(
            ['Nombre', 'Email', 'Rol Spatie', 'UR'],
            $tableRows
        );
        $this->command->info('Password para todos: password');
        $this->command->info('Fase 0 completada.');
    }
}
