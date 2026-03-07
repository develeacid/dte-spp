# Plan: S1-T3 — Integrar Spatie/laravel-permission

**Ticket:** S1-T3 | **Tipo:** feat | **Rama:** `feat/S1-T3-integracion-spatie-roles` | **Sprint:** 1 | **Depende de:** S1-T1

---

## Contexto

Integración del paquete estándar para roles y permisos (`spatie/laravel-permission`). Operará exclusivamente para controlar **acciones** (qué puede hacer el usuario), delegando el control de **alcance** (a qué UR pertenece) a los Teams de Jetstream. 

**Decisión de diseño importante:** Este sistema **coexiste** con el sistema de permisos de Teams de Jetstream:
- Los roles de Spatie (`admin`, `planeador`, `operador`) responden: **¿Qué puede hacer?**
- Los equipos de Jetstream responden: **¿A qué UR pertenece?**

**Mejoras blindadas:**
- Se usarán **Enums nativos de PHP** en lugar de Magic Strings (prevención de errores)
- Se registran explícitamente los middlewares en `bootstrap/app.php` (obligatorio en Laravel 11/12)
- Se usa `findOrCreate()` con guard explícito en lugar de `create()` (anti-duplicados)
- Se limpia la caché de Spatie en tests para evitar "falsos positivos" (RefreshDatabase no refresca automáticamente los datos en caché)
- Se blinda contra la característica "teams" de Spatie (que colisiona con Jetstream)

---

## Pre-requisitos

- S1-T1 completado (Jetstream instalado con tabla `users`)
- `config/permission.php` no debe existir previamente

---

## Pasos

### 1. Instalar el paquete y publicar configuración

```bash
sail composer require spatie/laravel-permission
sail artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Esto genera:
- `config/permission.php` — configuración del paquete
- Migraciones para tablas: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

### 2. Blindar la configuración contra colisiones de "Teams"

Abrir `config/permission.php` y asegurar explícitamente que la característica de equipos de Spatie esté apagada (para no chocar con Jetstream):

Buscar la línea:
```php
'teams' => false,  // ← Confirmar que está en false
```

Si no existe esta línea, agregarla al nivel raíz de la configuración.

### 3. Limpiar caché y ejecutar migraciones

```bash
sail artisan optimize:clear
sail artisan migrate
```

---

### 4. Crear Enums para Roles (Prevención de Magic Strings)

Crear el archivo `app/Enums/SystemRole.php`:

```php
<?php

namespace App\Enums;

enum SystemRole: string
{
    case ADMIN = 'admin';
    case PLANEADOR = 'planeador';
    case OPERADOR = 'operador';
}
```

### 5. Crear Enums para Permisos (Prevención de Magic Strings)

Crear el archivo `app/Enums/SystemPermission.php`:

```php
<?php

namespace App\Enums;

enum SystemPermission: string
{
    case GESTIONAR_CATALOGOS = 'gestionar_catalogos';
    case CREAR_PROGRAMA = 'crear_programa';
    case EDITAR_MIR = 'editar_mir';
    case CAPTURAR_AVANCE = 'capturar_avance';
    case REVISAR_AVANCE = 'revisar_avance';
    case APROBAR_AVANCE = 'aprobar_avance';
    case EXPORTAR_REPORTES = 'exportar_reportes';
    case ADMINISTRAR_USUARIOS = 'administrar_usuarios';
}
```

---

### 6. Actualizar el modelo User

Abrir `app/Models/User.php` y agregar el trait `HasRoles`:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Jetstream\HasTeams;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasTeams;
    use HasRoles; // ← Agregar este trait

    // ... resto del modelo sin cambios
}
```

---

### 7. Registrar Middlewares (Específico para Laravel 11/12)

Abrir `bootstrap/app.php` y registrar los alias de middleware de Spatie en la sección `withMiddleware()`:

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

**Nota:** Sin este registro explícito, Laravel arrojará error: *"Target class does not exist"* al intentar usar los middlewares en rutas.

---

### 8. Crear el Seeder Robusto (Anti-duplicados con findOrCreate)

```bash
sail artisan make:seeder RolesAndPermissionsSeeder
```

Editar `database/seeders/RolesAndPermissionsSeeder.php`:

```php
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
        $admin = Role::findOrCreate(SystemRole::ADMIN->value, 'web');
        $admin->givePermissionTo(Permission::all());

        $planeador = Role::findOrCreate(SystemRole::PLANEADOR->value, 'web');
        $planeador->givePermissionTo([
            SystemPermission::GESTIONAR_CATALOGOS->value,
            SystemPermission::CREAR_PROGRAMA->value,
            SystemPermission::EDITAR_MIR->value,
            SystemPermission::REVISAR_AVANCE->value,
            SystemPermission::APROBAR_AVANCE->value,
            SystemPermission::EXPORTAR_REPORTES->value,
        ]);

        $operador = Role::findOrCreate(SystemRole::OPERADOR->value, 'web');
        $operador->givePermissionTo([
            SystemPermission::CAPTURAR_AVANCE->value,
            SystemPermission::EXPORTAR_REPORTES->value,
        ]);
    }
}
```

### 9. Registrar el seeder en DatabaseSeeder

Abrir `database/seeders/DatabaseSeeder.php` y agregar la llamada:

```php
public function run(): void
{
    $this->call([
        RolesAndPermissionsSeeder::class,
    ]);
}
```

### 10. Ejecutar el seeder

```bash
sail artisan db:seed --class=RolesAndPermissionsSeeder
```

---

### 11. Verificar con Tinker

```bash
sail artisan tinker
```

```php
use App\Enums\SystemRole;
use App\Enums\SystemPermission;
use Spatie\Permission\Models\Role;

// Verificar roles creados
Role::all()->pluck('name')->toArray();
// => ["admin", "planeador", "operador"]

// Verificar permisos del planeador
Role::findByName(SystemRole::PLANEADOR->value)
    ->permissions
    ->pluck('name')
    ->toArray();
// => ["gestionar_catalogos", "crear_programa", "editar_mir", "revisar_avance", "aprobar_avance", "exportar_reportes"]

// Verificar que operador NO tiene crear_programa
Role::findByName(SystemRole::OPERADOR->value)
    ->hasPermissionTo(SystemPermission::CREAR_PROGRAMA->value);
// => false

exit
```

---

### 12. Crear tests blindados con limpieza de caché

```bash
sail artisan make:test RolesAndPermissionsTest
```

Editar `tests/Feature/RolesAndPermissionsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\SystemRole;
use App\Enums\SystemPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpieza estricta de caché para evitar interferencia entre tests
        // (RefreshDatabase resetea la BD pero no la caché de Spatie)
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_planeador_tiene_permiso_crear_programa(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $this->assertTrue($user->hasPermissionTo(SystemPermission::CREAR_PROGRAMA->value));
    }

    public function test_operador_no_tiene_permiso_crear_programa(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        $this->assertFalse($user->hasPermissionTo(SystemPermission::CREAR_PROGRAMA->value));
    }

    public function test_admin_tiene_todos_los_permisos(): void
    {
        $user = User::factory()->create();
        $user->assignRole(SystemRole::ADMIN->value);

        foreach (SystemPermission::cases() as $permiso) {
            $this->assertTrue($user->hasPermissionTo($permiso->value));
        }
    }
}
```

Ejecutar los tests:

```bash
sail artisan test --filter RolesAndPermissionsTest
```

---

## Criterios de aceptación

- [ ] Paquete instalado: `composer require spatie/laravel-permission`
- [ ] `config/permission.php` verifica explícitamente `'teams' => false`
- [ ] Migraciones ejecutadas: `migrate` crea tablas `roles`, `permissions`, `model_has_roles`, etc.
- [ ] Enums creados: `App\Enums\SystemRole` y `App\Enums\SystemPermission` con todos los casos
- [ ] Modelo `User` usa trait `HasRoles`
- [ ] Middlewares registrados en `bootstrap/app.php` con alias: `role`, `permission`, `role_or_permission`
- [ ] Seeder usa `findOrCreate()` con guard `'web'` explícito
- [ ] `sail artisan db:seed --class=RolesAndPermissionsSeeder` ejecuta sin errores
- [ ] Tests corren exitosamente (3 assertions verdes)
- [ ] Tinker verifica roles y permisos creados correctamente

---

## Tabla de permisos por rol

| Permiso               | admin | planeador | operador |
|-----------------------|:-----:|:---------:|:--------:|
| gestionar_catalogos   |  ✓   |    ✓     |          |
| crear_programa        |  ✓   |    ✓     |          |
| editar_mir            |  ✓   |    ✓     |          |
| capturar_avance       |  ✓   |           |    ✓    |
| revisar_avance        |  ✓   |    ✓     |          |
| aprobar_avance        |  ✓   |    ✓     |          |
| exportar_reportes     |  ✓   |    ✓     |    ✓    |
| administrar_usuarios  |  ✓   |           |          |

---

## Notas importantes

- **Guard por defecto:** El guard `'web'` es correcto para esta aplicación. Si en el futuro se conecta una API con Sanctum, se usará `'api'` como guard adicional
- **Caché en producción:** Los permisos se cachean automáticamente. Al modificar roles/permisos en producción: `php artisan permission:cache-reset`
- **Uso en rutas:** Proteger rutas con middleware: `Route::post('/programa', ...)->middleware('permission:crear_programa')`
- **Uso en controladores:** Validar con Gates/Policies: `$this->authorize('crear_programa')` o `abort_unless(auth()->user()->hasPermissionTo('crear_programa'), 403)`
- **No usar "teams" de Spatie:** El aislamiento multi-UR se maneja con Teams de Jetstream y middleware personalizado en S1-T5, no con el sistema de "teams" de Spatie
