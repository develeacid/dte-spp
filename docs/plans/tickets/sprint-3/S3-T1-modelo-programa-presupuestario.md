# S3-T1: Migración y Modelo para Programas Presupuestarios — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S3-T1
**Tipo:** feat
**Rama:** `feat/S3-T1-modelo-programa-presupuestario`
**Sprint:** 3 — Metodología de Marco Lógico (Etapas 1-4)
**Depende de:** S1-T1 (Jetstream Teams), S1-T3 (Permisos Spatie)

**Goal:** Extender la tabla `programa_presupuestarios` con campos de dominio MML (team_id, ejercicio_fiscal, origen, estado, created_by) y actualizar el modelo con scopes, relaciones y enums.

**Architecture:** La tabla `programa_presupuestarios` ya existe con campos básicos (`nombre`, `clave`). Se creará una migración de alteración para agregar los campos faltantes. Se crearán dos enums (`OrigenPrograma`, `EstadoPrograma`) y se actualizará el modelo con scopes de aislamiento por team y relaciones hacia las futuras tablas de MML.

**Tech Stack:** Laravel 12, PostgreSQL, Spatie Permissions, Enums PHP 8.1

---

## Contexto

El modelo `ProgramaPresupuestario` actual (`app/Models/ProgramaPresupuestario.php`) solo tiene `nombre` y `clave`. El sprint 3 lo necesita como cabecera del módulo MML: cada programa pertenece a un team (UR), tiene un ejercicio fiscal, un origen (nuevo/importado) y un estado (borrador/activo/cerrado).

La tabla pivote `programa_team` ya existe y vincula programas con teams.

---

## Pre-requisitos

- Tabla `programa_presupuestarios` existente (migración `2026_03_06_010558`)
- Tabla `programa_team` existente (migración `2026_03_06_010559`)
- Tabla `teams` y `users` de Jetstream
- Permisos Spatie configurados (S1-T3)

---

## Pasos

### Task 1: Crear Enums OrigenPrograma y EstadoPrograma

**Files:**
- Create: `app/Enums/OrigenPrograma.php`
- Create: `app/Enums/EstadoPrograma.php`

**Step 1: Crear enum OrigenPrograma**

```php
<?php

namespace App\Enums;

enum OrigenPrograma: string
{
    case NUEVO = 'nuevo';
    case IMPORTADO = 'importado';

    public function label(): string
    {
        return match($this) {
            self::NUEVO => 'Nuevo',
            self::IMPORTADO => 'Importado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

**Step 2: Crear enum EstadoPrograma**

```php
<?php

namespace App\Enums;

enum EstadoPrograma: string
{
    case BORRADOR = 'borrador';
    case ACTIVO = 'activo';
    case CERRADO = 'cerrado';

    public function label(): string
    {
        return match($this) {
            self::BORRADOR => 'Borrador',
            self::ACTIVO => 'Activo',
            self::CERRADO => 'Cerrado',
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::BORRADOR => 'bg-yellow-100 text-yellow-800',
            self::ACTIVO => 'bg-green-100 text-green-800',
            self::CERRADO => 'bg-gray-100 text-gray-800',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

**Step 3: Commit**

```bash
git add app/Enums/OrigenPrograma.php app/Enums/EstadoPrograma.php
git commit -m "feat(S3-T1): add OrigenPrograma and EstadoPrograma enums"
```

---

### Task 2: Migración para extender programa_presupuestarios

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_mml_fields_to_programa_presupuestarios_table.php`

**Step 1: Crear la migración**

```bash
sail artisan make:migration add_mml_fields_to_programa_presupuestarios_table
```

**Step 2: Escribir la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('id')->constrained('teams')->nullOnDelete();
            $table->unsignedSmallInteger('ejercicio_fiscal')->default(2026)->after('clave');
            $table->string('origen', 20)->default('nuevo')->after('ejercicio_fiscal');
            $table->string('estado', 20)->default('borrador')->after('origen');
            $table->foreignId('created_by')->nullable()->after('estado')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index(['team_id', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'ejercicio_fiscal']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['team_id']);
            $table->dropColumn(['team_id', 'ejercicio_fiscal', 'origen', 'estado', 'created_by', 'deleted_at']);
        });
    }
};
```

**Step 3: Ejecutar migración**

```bash
sail artisan migrate
```

Expected: migración ejecuta sin errores.

**Step 4: Verificar rollback**

```bash
sail artisan migrate:rollback --step=1
sail artisan migrate
```

**Step 5: Commit**

```bash
git add database/migrations/*add_mml_fields_to_programa_presupuestarios*
git commit -m "feat(S3-T1): add MML fields to programa_presupuestarios table"
```

---

### Task 3: Actualizar modelo ProgramaPresupuestario

**Files:**
- Modify: `app/Models/ProgramaPresupuestario.php`

**Step 1: Escribir test del modelo**

Crear `tests/Feature/ProgramaPresupuestarioMmlTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\EstadoPrograma;
use App\Enums\OrigenPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramaPresupuestarioMmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_programa_has_mml_fields(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $team = $user->currentTeam;

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa de Prueba',
            'clave' => 'PP-TEST-001',
            'team_id' => $team->id,
            'ejercicio_fiscal' => 2026,
            'origen' => OrigenPrograma::NUEVO->value,
            'estado' => EstadoPrograma::BORRADOR->value,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('programa_presupuestarios', [
            'id' => $programa->id,
            'team_id' => $team->id,
            'ejercicio_fiscal' => 2026,
            'origen' => 'nuevo',
            'estado' => 'borrador',
        ]);
    }

    public function test_scope_para_team(): void
    {
        $user1 = User::factory()->withPersonalTeam()->create();
        $user2 = User::factory()->withPersonalTeam()->create();

        ProgramaPresupuestario::create([
            'nombre' => 'Programa Team 1',
            'clave' => 'PT1',
            'team_id' => $user1->currentTeam->id,
        ]);
        ProgramaPresupuestario::create([
            'nombre' => 'Programa Team 2',
            'clave' => 'PT2',
            'team_id' => $user2->currentTeam->id,
        ]);

        $programas = ProgramaPresupuestario::paraTeam($user1->currentTeam->id)->get();

        $this->assertCount(1, $programas);
        $this->assertEquals('PT1', $programas->first()->clave);
    }

    public function test_scope_ejercicio(): void
    {
        $programa2026 = ProgramaPresupuestario::create([
            'nombre' => 'P 2026', 'clave' => 'P26', 'ejercicio_fiscal' => 2026,
        ]);
        $programa2027 = ProgramaPresupuestario::create([
            'nombre' => 'P 2027', 'clave' => 'P27', 'ejercicio_fiscal' => 2027,
        ]);

        $result = ProgramaPresupuestario::ejercicio(2026)->get();

        $this->assertCount(1, $result);
        $this->assertEquals('P26', $result->first()->clave);
    }

    public function test_relacion_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'T1', 'team_id' => $user->currentTeam->id,
        ]);

        $this->assertInstanceOf(Team::class, $programa->team);
    }

    public function test_relacion_creador(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'T1', 'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $programa->creador);
    }

    public function test_cast_enums(): void
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'TCAST',
            'origen' => OrigenPrograma::IMPORTADO->value,
            'estado' => EstadoPrograma::ACTIVO->value,
        ]);

        $programa->refresh();

        $this->assertInstanceOf(OrigenPrograma::class, $programa->origen);
        $this->assertInstanceOf(EstadoPrograma::class, $programa->estado);
    }
}
```

**Step 2: Ejecutar tests para verificar que fallan**

```bash
sail artisan test --filter=ProgramaPresupuestarioMmlTest
```

Expected: FAIL (scopes y relaciones no existen aún).

**Step 3: Actualizar el modelo**

```php
<?php

namespace App\Models;

use App\Enums\EstadoPrograma;
use App\Enums\OrigenPrograma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramaPresupuestario extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'clave',
        'team_id',
        'ejercicio_fiscal',
        'origen',
        'estado',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ejercicio_fiscal' => 'integer',
            'origen' => OrigenPrograma::class,
            'estado' => EstadoPrograma::class,
        ];
    }

    // --- Relaciones ---

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function equipos()
    {
        return $this->belongsToMany(Team::class, 'programa_team')
                    ->withPivot('rol')
                    ->withTimestamps();
    }

    // --- Scopes ---

    public function scopeParaTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeEjercicio(Builder $query, int $anio): Builder
    {
        return $query->where('ejercicio_fiscal', $anio);
    }
}
```

**Step 4: Ejecutar tests para verificar que pasan**

```bash
sail artisan test --filter=ProgramaPresupuestarioMmlTest
```

Expected: PASS (todos los tests).

**Step 5: Ejecutar test suite completo**

```bash
sail artisan test
```

Expected: Baseline (37 passed, 7 skipped) + los nuevos tests.

**Step 6: Commit**

```bash
git add app/Models/ProgramaPresupuestario.php tests/Feature/ProgramaPresupuestarioMmlTest.php
git commit -m "feat(S3-T1): extend ProgramaPresupuestario model with MML fields, scopes, and relations"
```

---

## Criterios de Aceptación

- [ ] Migraciones completas con FK a teams y users
- [ ] Modelo con scope `paraTeam` (aislamiento por UR)
- [ ] Modelo con scope `ejercicio`
- [ ] Relaciones: belongsTo Team, belongsTo User (creador)
- [ ] Enums OrigenPrograma y EstadoPrograma
- [ ] Índice en team_id y ejercicio_fiscal
- [ ] SoftDeletes habilitado
- [ ] Tests pasan
