# Plan: S1-T6 — Seeders de datos de prueba completos

**Ticket:** S1-T6
**Tipo:** chore
**Rama:** `chore/S1-T6-seeders-desarrollo`
**Sprint:** 1 — Identidad y Aislamiento
**Depende de:** S1-T1, S1-T2, S1-T3, **S1-T5** (Modelo Programa y tabla pivote deben existir)

---

## Contexto

Los seeders de desarrollo generan un escenario realista completo que permite probar el sistema de inmediato. La mejora más importante con respecto a la instalación por defecto es la integración con el **Middleware de Aislamiento Multi-UR** desarrollado en S1-T5. 

Para poder probar este aislamiento desde el día uno, generaremos el siguiente escenario:

- **Secretaría de Educación** = UR Coordinadora de un programa transversal.
- **Secretaría de Salud** = UR Coadyuvante del mismo programa.
- **Secretaría de Seguridad** = UR sin participación (para probar accesos denegados).

Cada UR tiene 2 usuarios: 1 planeador y 1 operador. Más un admin global.
Se implementarán mejores prácticas: el uso de Factories, constantes de roles en lugar de 'magic strings', comandos idempotentes (firstOrCreate / syncWithoutDetaching), y un guardia para proteger los entornos de producción.

---

## Pre-requisitos

- S1-T1 completado (Jetstream con Teams).
- S1-T2 completado (campos `clave_ur`, `titular`, `tipo_ur`, `activa` en BD).
- S1-T3 completado (constantes de roles como `User::ROLE_ADMIN` y permisos).
- S1-T5 completado (tabla migración `programa_team` y el modelo stub `ProgramaPresupuestario`).

---

## Pasos

### 1. Crear el seeder principal de desarrollo

```bash
sail artisan make:seeder DesarrolloSeeder
```

Editar `database/seeders/DesarrolloSeeder.php`:

```php
<?php

namespace Database\Seeders;

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
        $admin->assignRole(User::ROLE_ADMIN);

        // helper interno para crear URs y usuarios asociados iterativamente
        $crearUR = function (string $nombre, string $clave, string $titular, string $tipo) use ($admin) {
            $team = Team::firstOrCreate(
                ['clave_ur' => $clave],
                [
                    'name'          => $nombre,
                    'user_id'       => $admin->id, // Owner de los teams (Admin)
                    'titular'       => $titular,
                    'tipo_ur'       => $tipo,
                    'activa'        => true,
                    'personal_team' => false,
                ]
            );

            // Crear Planeador
            $planeador = User::factory()->create([
                'name'  => "Planeador {$clave}",
                'email' => "planeador." . strtolower($clave) . "@sistema.test",
            ]);
            $planeador->assignRole(User::ROLE_PLANEADOR);
            $team->users()->attach($planeador, ['role' => 'planeador']);
            $planeador->forceFill(['current_team_id' => $team->id])->save();

            // Crear Operador
            $operador = User::factory()->create([
                'name'  => "Operador {$clave}",
                'email' => "operador." . strtolower($clave) . "@sistema.test",
            ]);
            $operador->assignRole(User::ROLE_OPERADOR);
            $team->users()->attach($operador, ['role' => 'operador']);
            $operador->forceFill(['current_team_id' => $team->id])->save();

            return $team;
        };

        // 3. Crear URs usando el helper
        $urEducacion = $crearUR('Secretaría de Educación', 'SE-001', 'Dr. Juan Pérez', Team::TIPO_SUSTANTIVA);
        $urSalud = $crearUR('Secretaría de Salud', 'SS-002', 'Dra. María López', Team::TIPO_APOYO);
        $urSeguridad = $crearUR('Secretaría de Seguridad', 'SEG-003', 'Lic. Roberto Sánchez', Team::TIPO_SUSTANTIVA);

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
                ['Admin', 'admin@sistema.test', 'admin', '—'],
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
```

### 2. Actualizar DatabaseSeeder

Editar `database/seeders/DatabaseSeeder.php` para integrar el nuevo flujo:

```php
public function run(): void
{
    $this->call([
        RolesAndPermissionsSeeder::class, // Esencial generar permisos antes que usuarios
        DesarrolloSeeder::class,          // Ejecuta los Factories y relaciones
    ]);
}
```

### 3. Ejecutar los seeders

```bash
sail artisan migrate:fresh --seed
```

### 4. Verificación Interna (Tinker)

Probar que la información quedó interconectada para S1-T5.

```bash
sail artisan tinker
```

```php
use App\Models\User;
use App\Models\ProgramaPresupuestario;

// 1. Verificar usuario y su respectiva suscripción de roles
$user = User::where('email', 'planeador.se-001@sistema.test')->first();
$user->currentTeam->name; // Debe decir "Secretaría de Educación"
$user->hasRole(User::ROLE_PLANEADOR); // true

// 2. Verificar el escenario transversal esencial para el AislamientoMultiUR
$prog = ProgramaPresupuestario::where('clave', 'TRANS-2026-001')->first();

// Verificar Rol Coordinador
$prog->equipos()->wherePivot('rol', 'coordinadora')->first()->name; 
// => "Secretaría de Educación"

// Verificar Rol Coadyuvante
$prog->equipos()->wherePivot('rol', 'coadyuvante')->first()->name; 
// => "Secretaría de Salud"
exit
```

---

## Criterios de aceptación

- [ ] Seeder usa `$user = User::factory()->create()` para facilitar el control de contraseñas.
- [ ] Implementación de Security Guard a nivel entorno (`app()->environment('production')`).
- [ ] Utilización de las constantes de rol definidas en `S1-T3` para robustez de la codebase.
- [ ] Creación con `firstOrCreate` y anexión a pivote con `syncWithoutDetaching` otorgando idempotencia al Database Seedering.
- [ ] Creación obligatoria del Sub-Escenario Transversal validando así la funcionalidad del ticket hermano (S1-T5).
- [ ] La tabla de resumen imprime todos los correos generados correctamene para simplificar visualización.

---

## Notas para el equipo

- La base de datos es ahora capaz de someterse a la prueba del middleware de Inyección Multi-UR (que lanzaba `403` a las URs que no estaban mapeadas en el query del modelo `$programa`).
- El uso nativo de las funciones idempotentes facilita hacer pruebas unitarias o de requests rápidos evitando errores Duplicate de SQL en futuras iteraciones. Todos los usuarios creados con los Factories tienen el password por defecto en "password" como es usual de Laravel Fortify.
