# Plan: S1-T7 — Migración tabla pivote programa_team (Multi-UR Ready)

**Ticket:** S1-T7
**Tipo:** feat
**Rama:** `feat/S1-T7-tabla-programa-team`
**Sprint:** 1 — Identidad y Aislamiento
**Depende de:** S1-T1, S1-T2

---

## Contexto

Para que el middleware `AislamientoMultiUR` y los seeders de desarrollo funcionen correctamente desde el Sprint 1, necesitamos la estructura de datos básica donde se guarda la relación de permisos de los programas. Aunque la lógica de negocio de "Programas" y "MIR" pertenecen al Sprint 3, la infraestructura de relación (tabla pivote) la necesitamos ahora.

Para evitar errores de "tabla no encontrada" o "dependencia circular", se crearán **migraciones stub** (vacías pero con los campos clave) para las tablas padre, posibilitando así que las claves foráneas (Foreign Keys) complen satisfactoriamente desde este primer paso y permitiendo la ejecución fluida del `migrate:fresh`.

---

## Pre-requisitos

- S1-T1 completado (tabla `teams` existe).
- S1-T2 completado (campos de UR en `teams`).

---

## Pasos

### 1. Crear migración Stub: Programa Presupuestario

Necesaria para que `programa_team` pueda referenciarla. Sin la tabla padre, la clave foránea en PostgreSQL romperá.

```bash
sail artisan make:migration create_programas_presupuestarios_table
```

Editar el archivo generado para dejarlo en su versión mínima (Stub):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programas_presupuestarios', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programas_presupuestarios');
    }
};
```

### 2. Crear migración: Programa Team

Ahora que la tabla padre se ejecutará primero, podemos crear la tabla pivote de manera segura. Usaremos `string` para el campo `rol` dado que PostgreSQL maneja mejor los strings que los enums crudos cuando se hacen modificaciones a futuro.

```bash
sail artisan make:migration create_programa_team_table
```

Editar el archivo:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programa_team', function (Blueprint $table) {
            $table->id();

            // FK a la tabla stub creada en el paso anterior
            $table->foreignId('programa_presupuestario_id')
                  ->constrained('programas_presupuestarios')
                  ->onDelete('cascade');

            // FK a teams (ya existe desde S1-T1)
            $table->foreignId('team_id')
                  ->constrained('teams')
                  ->onDelete('cascade');

            // Usar string en lugar de enum (Postgres friendly y más fácil de extender)
            $table->string('rol', 20)->default('coadyuvante');

            $table->timestamps();

            // Restricción: Un equipo solo puede tener un rol por programa
            $table->unique(['programa_presupuestario_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_team');
    }
};
```

### 3. Ejecutar y Verificar Migraciones

Asegurar que las migraciones corren en el orden adecuado (el `timestamp` del archivo resuelve esto automáticamente siempre que los comandos `make:migration` se hayan corrido en orden).

```bash
sail artisan migrate:fresh
```

Salida esperada (entre otras tablas del framework):
- `xxxx_xx_xx_xxxxxx_create_programas_presupuestarios_table` ... OK
- `xxxx_xx_xx_xxxxxx_create_programa_team_table` ... OK

### 4. Actualizar Modelo Team (Relación Inversa)

Aunque `ProgramaPresupuestario` es un stub (que debiste crear en `S1-T5`), definimos la relación en el modelo actual `Team` para su uso futuro y en los Seeders (`S1-T6`).

Editar `app/Models/Team.php`:

```php
// Usa los namespaces correctos en tu archivo real
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends JetstreamTeam
{
    // ...

    public function programas(): BelongsToMany
    {
        return $this->belongsToMany(ProgramaPresupuestario::class, 'programa_team')
                    ->withPivot('rol')
                    ->withTimestamps();
    }
}
```

### 5. Documentar Deuda Técnica (Tech Debt)

Dado que usamos un "Stub inteligente" para sortear la limitante técnica, debemos documentarlo para el Sprint 3. Puedes agregar una línea al `readme` o crear un archivo en `docs/architecture/stubs.md` (si la carpeta existe) indicando:

> "La tabla `programas_presupuestarios` generada en S1-T7 es un stub con los campos mínimos. Será expandida con todos los campos MIR, de alineación y lógica de negocio mediante un alter table en el **Sprint 3 (S3-T1)**."

---

## Criterios de Aceptación (Actualizados)

- [ ] Migración `create_programas_presupuestarios_table` (stub) ejecutada sin errores.
- [ ] Migración `create_programa_team_table` ejecutada con FKs funcionales sin causar errores en BD.
- [ ] Campo `rol` implementado como `string` (no `enum`).
- [ ] Constraint a nivel base de datos de `UNIQUE(programa_presupuestario_id, team_id)` creado satisfactoriamente.
- [ ] `$user->currentTeam->programas` devuelve la relación correctamente (comprobable en seeder S1-T6).
- [ ] Toda la suite de migraciones ejecuta `sail artisan migrate:fresh` desde inicio a fin sin romper por faltas de tablas ligadas.

---

## Notas Críticas

1.  **Omisión controlada de `mir_niveles`:** No se debe incluir bajo ninguna circunstancia ninguna migración refiriendo o modificando la tabla `mir_niveles` en este Sprint. Cualquier plan desactualizado que propusiera el "team_id en mir_niveles" queda invalidado en este ticket y esa tarea es movida integralmente al **Sprint 3 (S3-T2)**.
2.  **Sobre el Modelo Stub:** El modelo `ProgramaPresupuestario` ya debió haber sido creado en el ticket **S1-T5** como se recomendó. Si el desarrollador que toma esta tarea se da cuenta de que no existe, debe crearlo antes del paso 4 (`sail artisan make:model ProgramaPresupuestario` con atributos `['clave', 'nombre']`), o la relación en el paso 4 fallará.
