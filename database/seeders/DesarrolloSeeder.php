<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Enums\TipoUnidadResponsable;

use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DesarrolloSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Guardia de seguridad: NUNCA correr en producción
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar este seeder con contraseñas hardcoded en producción.');
            return;
        }

        $this->command->info('Iniciando carga de datos de desarrollo...');

        // 2. ADMIN GLOBAL
        $admin = User::factory()->create([
            'name'  => 'Administrador Sistema',
            'email' => 'admin@sistema.test',
        ]);
        $admin->assignRole(SystemRole::ADMIN->value);

        // helper interno para crear URs y usuarios asociados iterativamente
        $crearUR = function (string $nombre, string $clave, string $titular, TipoUnidadResponsable $tipo) use ($admin) {
            $team = Team::firstOrCreate(
                ['clave_ur' => $clave],
                [
                    'name'          => $nombre,
                    'user_id'       => $admin->id, // Owner de los teams (Admin)
                    'titular'       => $titular,
                    'tipo_ur'       => $tipo, // Eloquent handles the Enum cast automatically
                    'activa'        => true,
                    'personal_team' => false,
                ]
            );

            // Crear Planeador
            $planeador = User::factory()->create([
                'name'  => "Planeador {$clave}",
                'email' => "planeador." . strtolower($clave) . "@sistema.test",
            ]);
            $planeador->assignRole(SystemRole::PLANEADOR->value);
            $team->users()->attach($planeador, ['role' => 'planeador']);
            $planeador->forceFill(['current_team_id' => $team->id])->save();

            // Crear Operador
            $operador = User::factory()->create([
                'name'  => "Operador {$clave}",
                'email' => "operador." . strtolower($clave) . "@sistema.test",
            ]);
            $operador->assignRole(SystemRole::OPERADOR->value);
            $team->users()->attach($operador, ['role' => 'operador']);
            $operador->forceFill(['current_team_id' => $team->id])->save();

            return $team;
        };

        // 3. Crear URs usando el helper
        $urEducacion = $crearUR('Secretaría de Educación', 'SE-001', 'Dr. Juan Pérez', TipoUnidadResponsable::SUSTANTIVA);
        $urSalud     = $crearUR('Secretaría de Salud', 'SS-002', 'Dra. María López', TipoUnidadResponsable::APOYO);
        $urSeguridad = $crearUR('Secretaría de Seguridad', 'SEG-003', 'Lic. Roberto Sánchez', TipoUnidadResponsable::SUSTANTIVA);

        // 4. Crear Programa Transversal (Requerido para el testeo del Middleware S1-T5)
        $programaTransversal = ProgramaPresupuestario::firstOrCreate(
            ['clave' => 'TRANS-2026-001'],
            ['nombre' => 'Programa Interinstitucional de Salud Escolar']
        );

        // 5. Definir roles en el programa usando la tabla pivote de S1-T5

        // Educación es Coordinadora (Acceso total)
        $programaTransversal->equipos()->syncWithoutDetaching([
            $urEducacion->id => ['rol' => 'coordinadora']
        ]);

        // Salud es Coadyuvante (Acceso limitado a su nivel MIR)
        $programaTransversal->equipos()->syncWithoutDetaching([
            $urSalud->id => ['rol' => 'coadyuvante']
        ]);

        // Seguridad NO se agrega intencionalmente. Si el planeador de SEG intenta entrar, debe arrojar 403.

        $this->command->info('✓ Datos de desarrollo y escenario transversal cargados.');
        $this->command->table(
            ['Usuario', 'Email', 'Rol', 'UR'],
            [
                ['Admin', 'admin@sistema.test', 'admin', '-'],
                ['Planeador Edu', 'planeador.se-001@sistema.test', 'planeador', 'Educación'],
                ['Operador Edu', 'operador.se-001@sistema.test', 'operador', 'Educación'],
                ['Planeador Salud', 'planeador.ss-002@sistema.test', 'planeador', 'Salud'],
                ['Operador Salud', 'operador.ss-002@sistema.test', 'operador', 'Salud'],
                ['Planeador Seg', 'planeador.seg-003@sistema.test', 'planeador', 'Seguridad'],
                ['Operador Seg', 'operador.seg-003@sistema.test', 'operador', 'Seguridad'],
            ]
        );
        $this->command->info('Contraseña para todos los usuarios: password');
    }
}
