# Plan: S1-T5 — Middleware de Aislamiento Multi-UR

**Ticket:** S1-T5
**Tipo:** feat
**Rama:** `feat/S1-T5-middleware-multi-ur`
**Sprint:** 1 — Identidad y Aislamiento
**Depende de:** S1-T1, S1-T3

---

## Contexto

El sistema soporta programas **transversales**: un mismo programa puede tener múltiples URs participantes. La lógica de acceso tiene dos niveles:

1. **UR Coordinadora** — es la UR "dueña" del programa. Tiene acceso completo (lectura + escritura en todo el programa).
2. **UR Coadyuvante** — es una UR que participa en el programa pero solo es responsable de Componentes/Actividades específicos. Tiene lectura del programa completo, pero escritura solo en sus `mir_niveles` asignados (identificados por `mir_niveles.team_id`).
3. **Sin relación** — acceso denegado (403).

El middleware consulta la tabla `programa_team` para determinar el rol de la UR activa del usuario en el programa solicitado.

> **Importante:** Aunque la lógica completa de programas pertenece al Sprint 3, aquí en el Sprint 1 necesitamos crear la estructura mínima (stubs) para la tabla `programa_team` y el modelo `ProgramaPresupuestario`. Esto evita dependencias circulares inexistentes (compilar código que busca clases que no existen) y permite probar el middleware de inmediato. Además, utilizaremos el sistema de **Policies** de Laravel y el objeto `attributes` del Request para seguir las mejores prácticas arquitectónicas.

---

## Pre-requisitos

- S1-T1 completado (Jetstream con Teams — `$user->currentTeam` disponible)
- S1-T3 completado (roles y permisos, especialmente rol `admin`)

---

## Pasos

### 1. Crear migración "Stub" para la relación programa_team

Esta migración es necesaria para que el middleware pueda consultar la base de datos. Aunque los programas se detallen en el Sprint 3, la relación de acceso se usará ahora.

```bash
sail artisan make:migration create_programa_team_table
```

Editar la migración generada:

```php
Schema::create('programa_team', function (Blueprint $table) {
    $table->id();
    $table->foreignId('programa_presupuestario_id')->constrained()->cascadeOnDelete();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->enum('rol', ['coordinadora', 'coadyuvante'])->default('coadyuvante');
    $table->timestamps();

    $table->unique(['programa_presupuestario_id', 'team_id'], 'pt_programa_team_unique'); // Una UR solo tiene un rol por programa
});
```

### 2. Crear modelo ProgramaPresupuestario (Stub)

Necesario para que el middleware pueda tipar la variable `$programa` al resolverse desde las rutas.

```bash
sail artisan make:model ProgramaPresupuestario
```

Editar `app/Models/ProgramaPresupuestario.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramaPresupuestario extends Model
{
    // Campos mínimos para las pruebas
    protected $fillable = ['nombre', 'clave'];

    // Relación clave para el middleware de aislamiento
    public function equipos()
    {
        // En un futuro se puede tipar usando BelongsToMany
        return $this->belongsToMany(Team::class, 'programa_team')
                    ->withPivot('rol')
                    ->withTimestamps();
    }
}
```

### 3. Crear el Middleware de Aislamiento Multi-UR

Creamos el middleware con "puertas traseras" para el rol `admin` y una validación estricta utilizando `$request->attributes` en lugar de `merge`. Esto evita contaminar los Form Requests ("input data") de la aplicación.

```bash
sail artisan make:middleware AislamientoMultiUR
```

Editar `app/Http/Middleware/AislamientoMultiUR.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Models\ProgramaPresupuestario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AislamientoMultiUR
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Si no hay usuario o es Admin, pasar (Admin tiene pase real)
        if (! $user || $user->hasRole('admin')) {
            return $next($request);
        }

        // 2. Obtener el programa de la ruta (Route Model Binding o ID directo)
        $programa = $request->route('programa');

        // Si la ruta no es de un programa, ignorar este middleware
        if (! $programa instanceof ProgramaPresupuestario) {
            return $next($request);
        }

        // 3. Validar Team Activo
        $teamActivo = $user->currentTeam;
        if (! $teamActivo) {
            abort(403, 'No tienes una Unidad Responsable activa asignada.');
        }

        // 4. Consultar relación en la BD (Optimizado)
        $pivote = $programa->equipos()
            ->where('team_id', $teamActivo->id)
            ->first();

        if (! $pivote) {
            abort(403, 'Tu Unidad Responsable no tiene acceso a este programa.');
        }

        // 5. Inyectar rol en el request para uso en Controladores/Policies (Mejor práctica)
        $request->attributes->set('ur_rol_en_programa', $pivote->pivot->rol);

        return $next($request);
    }
}
```

### 4. Registrar alias del middleware

> **⚠️ Importante:** Para este punto, `bootstrap/app.php` ya tiene contenido de S1-T3 (alias de Spatie) y de S1-T4 (`web(append: [...])`). **No reemplazar el bloque existente.** Agregar `ur.aislamiento` al array `alias` ya existente.

El resultado esperado del bloque `withMiddleware` debe quedar así:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        'ur.aislamiento'     => \App\Http\Middleware\AislamientoMultiUR::class, // <- agregar esta línea
    ]);
    $middleware->web(append: [
        \App\Http\Middleware\RequireTwoFactorAuthentication::class,
    ]);
})
```

### 5. Definir autorización temporal / Policy (Mejora Arquitectónica)

En lugar de crear un "Helper", utilizaremos el flujo natural de automatización de Laravel para que, en un futuro, uses `$user->can('update', $nivel)` o `$user->can('view', $programa)`.

Por el momento, aseguraremos que el Administrador siempre pase cualquier condición de Gate/Policy.
Edita el `AppServiceProvider` en `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\Gate;

// Dentro de boot():
public function boot(): void
{
    // Definir lógica global para que "admin" pueda saltarse cuaquier autorización futura
    Gate::before(function ($user, $ability) {
        if ($user->hasRole('admin')) {
            return true;
        }
    });
}
```

*Nota: La policy fina `MirNivelPolicy` se creará e implementará en el Sprint 3 para leer el atributo `ur_rol_en_programa` del request.*

### 6. Tests de Integración Ejecutables

Dado que ahora contamos con las estructuras (stubs) del modelo y tabla, los tests pueden ser reales.

```bash
sail artisan make:test AislamientoMultiURTest
```

Editar `tests/Feature/AislamientoMultiURTest.php` reemplazando su contenido por:

```php
<?php

namespace Tests\Feature;

use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AislamientoMultiURTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que existan roles (RolesAndPermissionsSeeder define: admin, planeador, operador)
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Registrar la ruta de prueba UNA SOLA VEZ en setUp para evitar problemas
        // de aislamiento entre tests cuando se definen rutas con Route::get() dentro
        // de cada método de test individual.
        Route::get('/test-programa/{programa}', function (ProgramaPresupuestario $programa) {
            return 'OK';
        })->middleware(['web', 'auth', 'ur.aislamiento']);
    }

    public function test_usuario_sin_relacion_es_bloqueado(): void
    {
        $user = User::factory()->create();
        $user->assignRole('planeador'); // Rol base (existe en RolesAndPermissionsSeeder)

        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->current_team_id = $team->id;
        $user->save();

        $programa = ProgramaPresupuestario::create(['nombre' => 'Prog Test', 'clave' => 'P-001']);

        $this->actingAs($user)
             ->get("/test-programa/{$programa->id}")
             ->assertForbidden(); // Espera 403
    }

    public function test_coordinadora_accede(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->current_team_id = $team->id;
        $user->save();

        $programa = ProgramaPresupuestario::create(['nombre' => 'Prog Test', 'clave' => 'P-002']);

        // Crear relación con rol Coordinadora
        $programa->equipos()->attach($team->id, ['rol' => 'coordinadora']);

        $this->actingAs($user)
             ->get("/test-programa/{$programa->id}")
             ->assertOk(); // Espera 200
    }
}
```

Ejecutar tests:

```bash
sail artisan test --filter AislamientoMultiURTest
```

---

## Criterios de aceptación

- [ ] Migración `create_programa_team_table` creada y ejecutada satisfactoriamente en `testing`.
- [ ] Modelo `ProgramaPresupuestario` (stub) creado con su respectiva relación `equipos()`.
- [ ] Middleware `AislamientoMultiUR` inyecta rol mediante `$request->attributes->set()` (Mejor práctica que `$request->merge()`).
- [ ] Middleware permite paso automáticamente a un usuario con rol `admin`.
- [ ] Tests validan activamente que se bloquea acceso a URs ajenas (403) y permite acceso a URs relacionadas (200).

---

## Notas para el equipo

1.  **`request()->attributes` vs `request()->merge`:** Usar `$request->attributes->set()` es muchísimo más limpio para pasar metadatos al controlador (como el rol de la UR) que `merge()`. Este último fue diseñado para inyectar input/payload, lo cual rompe responsabilidades y puede causar colisiones.
2.  **Stubs Seguros:** Crear un `ProgramaPresupuestario` semi-vacío ahora mismo era vital para compilar. En el Sprint 3, su migración extenderá o creará los campos definitivos sin romper el trabajo efectuado aquí.
3.  **Autorización Evolutiva:** Ya quedó el cimiento de Laravel Auth Gate preparado. En los tickets siguientes, implementaremos `MirNivelPolicy` para controlar qué y dónde puede editar la UR coadyuvante.
4.  **Sin escape hatch de entorno (decisión consciente):** A diferencia de `RequireTwoFactorAuthentication` (que aplica globalmente), `AislamientoMultiUR` se registra como alias y solo se activa en rutas específicas. Los tests pueden ejercitarlo directamente con `RefreshDatabase`, por lo que no necesita bypass de entorno. No agregar uno.
5.  **`bootstrap/app.php` tiene múltiples secciones:** Desde S1-T3 y S1-T4, ese archivo ya tiene `alias([...])` y `web(append: [...])` dentro de `withMiddleware`. Cualquier ticket futuro que modifique ese archivo debe extender las secciones existentes, nunca reemplazarlas. Ver Step 4 de este plan como referencia del estado esperado del archivo.
