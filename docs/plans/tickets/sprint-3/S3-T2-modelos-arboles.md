# S3-T2: Migraciones y Modelos para Árboles (Problema/Objetivos) — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S3-T2
**Tipo:** feat
**Rama:** `feat/S3-T2-modelos-arboles`
**Sprint:** 3 — Metodología de Marco Lógico (Etapas 1-4)
**Depende de:** S3-T1 (Programa Presupuestario extendido)

**Goal:** Crear las tablas `arboles` y `arbol_nodos` con estructura Adjacency List para representar árboles de problemas y de objetivos en la metodología MML.

**Architecture:** Cada `ProgramaPresupuestario` puede tener múltiples árboles (uno de problemas, uno de objetivos). Cada árbol tiene nodos organizados en una estructura jerárquica vía Adjacency List (`parent_id`). Los nodos del árbol de objetivos se vinculan con su nodo origen del árbol de problemas vía `nodo_origen_id`. Se crea un enum `TipoNodo` con los 10 tipos requeridos y un enum `TipoArbol` (problema/objetivos).

**Tech Stack:** Laravel 12, PostgreSQL, Eloquent Adjacency List (relación recursiva), Enums PHP 8.1

---

## Contexto

Los árboles son el corazón de la MML. El árbol de problemas identifica causas y efectos de un problema central. El árbol de objetivos transforma cada nodo negativo en positivo. La estructura Adjacency List (`parent_id`) es suficiente para la profundidad esperada (máx 3-4 niveles).

---

## Pre-requisitos

- S3-T1 completado (tabla `programa_presupuestarios` extendida)

---

## Pasos

### Task 1: Crear Enums TipoArbol y TipoNodo

**Files:**
- Create: `app/Enums/TipoArbol.php`
- Create: `app/Enums/TipoNodo.php`

**Step 1: Crear enum TipoArbol**

```php
<?php

namespace App\Enums;

enum TipoArbol: string
{
    case PROBLEMA = 'problema';
    case OBJETIVOS = 'objetivos';

    public function label(): string
    {
        return match($this) {
            self::PROBLEMA => 'Árbol de Problemas',
            self::OBJETIVOS => 'Árbol de Objetivos',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

**Step 2: Crear enum TipoNodo**

```php
<?php

namespace App\Enums;

enum TipoNodo: string
{
    // Árbol de problemas
    case PROBLEMA_CENTRAL = 'problema_central';
    case CAUSA_DIRECTA = 'causa_directa';
    case CAUSA_INDIRECTA = 'causa_indirecta';
    case EFECTO_DIRECTO = 'efecto_directo';
    case EFECTO_INDIRECTO = 'efecto_indirecto';

    // Árbol de objetivos
    case OBJETIVO_CENTRAL = 'objetivo_central';
    case MEDIO_DIRECTO = 'medio_directo';
    case MEDIO_INDIRECTO = 'medio_indirecto';
    case FIN_DIRECTO = 'fin_directo';
    case FIN_INDIRECTO = 'fin_indirecto';

    public function label(): string
    {
        return match($this) {
            self::PROBLEMA_CENTRAL => 'Problema Central',
            self::CAUSA_DIRECTA => 'Causa Directa',
            self::CAUSA_INDIRECTA => 'Causa Indirecta',
            self::EFECTO_DIRECTO => 'Efecto Directo',
            self::EFECTO_INDIRECTO => 'Efecto Indirecto',
            self::OBJETIVO_CENTRAL => 'Objetivo Central',
            self::MEDIO_DIRECTO => 'Medio Directo',
            self::MEDIO_INDIRECTO => 'Medio Indirecto',
            self::FIN_DIRECTO => 'Fin Directo',
            self::FIN_INDIRECTO => 'Fin Indirecto',
        };
    }

    public function esProblema(): bool
    {
        return in_array($this, [
            self::PROBLEMA_CENTRAL,
            self::CAUSA_DIRECTA,
            self::CAUSA_INDIRECTA,
            self::EFECTO_DIRECTO,
            self::EFECTO_INDIRECTO,
        ]);
    }

    public function esObjetivo(): bool
    {
        return !$this->esProblema();
    }

    public function colorClass(): string
    {
        return match($this) {
            self::PROBLEMA_CENTRAL => 'bg-red-100 text-red-800 border-red-300',
            self::CAUSA_DIRECTA => 'bg-orange-100 text-orange-800 border-orange-300',
            self::CAUSA_INDIRECTA => 'bg-amber-100 text-amber-800 border-amber-300',
            self::EFECTO_DIRECTO => 'bg-rose-100 text-rose-800 border-rose-300',
            self::EFECTO_INDIRECTO => 'bg-pink-100 text-pink-800 border-pink-300',
            self::OBJETIVO_CENTRAL => 'bg-green-100 text-green-800 border-green-300',
            self::MEDIO_DIRECTO => 'bg-teal-100 text-teal-800 border-teal-300',
            self::MEDIO_INDIRECTO => 'bg-cyan-100 text-cyan-800 border-cyan-300',
            self::FIN_DIRECTO => 'bg-blue-100 text-blue-800 border-blue-300',
            self::FIN_INDIRECTO => 'bg-indigo-100 text-indigo-800 border-indigo-300',
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
git add app/Enums/TipoArbol.php app/Enums/TipoNodo.php
git commit -m "feat(S3-T2): add TipoArbol and TipoNodo enums"
```

---

### Task 2: Crear migración para tabla arboles

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_arboles_table.php`

**Step 1: Crear la migración**

```bash
sail artisan make:migration create_arboles_table
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
        Schema::create('arboles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->string('tipo', 20); // problema, objetivos
            $table->timestamps();

            $table->unique(['programa_presupuestario_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arboles');
    }
};
```

**Step 3: Ejecutar migración**

```bash
sail artisan migrate
```

**Step 4: Commit**

```bash
git add database/migrations/*create_arboles_table*
git commit -m "feat(S3-T2): create arboles migration"
```

---

### Task 3: Crear migración para tabla arbol_nodos

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_arbol_nodos_table.php`

**Step 1: Crear la migración**

```bash
sail artisan make:migration create_arbol_nodos_table
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
        Schema::create('arbol_nodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arbol_id')->constrained('arboles')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('arbol_nodos')->cascadeOnDelete();
            $table->string('tipo_nodo', 30);
            $table->text('descripcion');
            $table->foreignId('nodo_origen_id')->nullable()->constrained('arbol_nodos')->nullOnDelete();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('arbol_id');
            $table->index('nodo_origen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arbol_nodos');
    }
};
```

**Step 3: Ejecutar migración**

```bash
sail artisan migrate
```

**Step 4: Commit**

```bash
git add database/migrations/*create_arbol_nodos_table*
git commit -m "feat(S3-T2): create arbol_nodos migration with adjacency list"
```

---

### Task 4: Crear modelos Arbol y ArbolNodo

**Files:**
- Create: `app/Models/Mml/Arbol.php`
- Create: `app/Models/Mml/ArbolNodo.php`

**Step 1: Escribir test**

Crear `tests/Feature/Mml/ArbolTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArbolTest extends TestCase
{
    use RefreshDatabase;

    private function crearProgramaConArbol(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $user->currentTeam->id,
        ]);
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);

        return [$programa, $arbol, $user];
    }

    public function test_crear_arbol_de_problemas(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $this->assertDatabaseHas('arboles', [
            'programa_presupuestario_id' => $programa->id,
            'tipo' => 'problema',
        ]);
    }

    public function test_arbol_pertenece_a_programa(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $this->assertInstanceOf(ProgramaPresupuestario::class, $arbol->programa);
        $this->assertEquals($programa->id, $arbol->programa->id);
    }

    public function test_unique_constraint_tipo_por_programa(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
    }

    public function test_crear_nodo_problema_central(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $nodo = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Alto índice de deserción escolar',
        ]);

        $this->assertDatabaseHas('arbol_nodos', [
            'arbol_id' => $arbol->id,
            'tipo_nodo' => 'problema_central',
            'parent_id' => null,
        ]);
    }

    public function test_relacion_recursiva_parent_children(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $central = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema central',
        ]);

        $causa = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'parent_id' => $central->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Causa directa 1',
            'orden' => 1,
        ]);

        $this->assertCount(1, $central->children);
        $this->assertEquals($central->id, $causa->parent->id);
    }

    public function test_vinculo_nodo_origen_problema_a_objetivo(): void
    {
        [$programa, $arbolProblema] = $this->crearProgramaConArbol();

        $nodoProblem = ArbolNodo::create([
            'arbol_id' => $arbolProblema->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Alto índice de deserción',
        ]);

        $arbolObj = Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);

        $nodoObj = ArbolNodo::create([
            'arbol_id' => $arbolObj->id,
            'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL->value,
            'descripcion' => 'Reducir el índice de deserción',
            'nodo_origen_id' => $nodoProblem->id,
        ]);

        $this->assertEquals($nodoProblem->id, $nodoObj->nodoOrigen->id);
    }

    public function test_cascade_delete_arbol_elimina_nodos(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema',
        ]);

        $arbol->delete();

        $this->assertDatabaseMissing('arbol_nodos', ['arbol_id' => $arbol->id]);
    }

    public function test_arbol_tiene_nodos(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Central',
        ]);

        $this->assertCount(1, $arbol->nodos);
    }

    public function test_cast_tipo_nodo_a_enum(): void
    {
        [$programa, $arbol] = $this->crearProgramaConArbol();

        $nodo = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Una causa',
        ]);

        $nodo->refresh();
        $this->assertInstanceOf(TipoNodo::class, $nodo->tipo_nodo);
    }
}
```

**Step 2: Ejecutar tests para verificar que fallan**

```bash
sail artisan test --filter=ArbolTest
```

Expected: FAIL (modelos no existen).

**Step 3: Crear modelo Arbol**

Crear `app/Models/Mml/Arbol.php`:

```php
<?php

namespace App\Models\Mml;

use App\Enums\TipoArbol;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Arbol extends Model
{
    protected $table = 'arboles';

    protected $fillable = [
        'programa_presupuestario_id',
        'tipo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoArbol::class,
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function nodos(): HasMany
    {
        return $this->hasMany(ArbolNodo::class, 'arbol_id');
    }
}
```

**Step 4: Crear modelo ArbolNodo**

Crear `app/Models/Mml/ArbolNodo.php`:

```php
<?php

namespace App\Models\Mml;

use App\Enums\TipoNodo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArbolNodo extends Model
{
    protected $table = 'arbol_nodos';

    protected $fillable = [
        'arbol_id',
        'parent_id',
        'tipo_nodo',
        'descripcion',
        'nodo_origen_id',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'tipo_nodo' => TipoNodo::class,
            'orden' => 'integer',
        ];
    }

    public function arbol(): BelongsTo
    {
        return $this->belongsTo(Arbol::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('orden');
    }

    public function nodoOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'nodo_origen_id');
    }

    public function nodosDerivados(): HasMany
    {
        return $this->hasMany(self::class, 'nodo_origen_id');
    }
}
```

**Step 5: Ejecutar tests para verificar que pasan**

```bash
sail artisan test --filter=ArbolTest
```

Expected: PASS.

**Step 6: Ejecutar suite completo**

```bash
sail artisan test
```

Expected: Baseline + nuevos tests pasan.

**Step 7: Commit**

```bash
git add app/Models/Mml/ tests/Feature/Mml/
git commit -m "feat(S3-T2): add Arbol and ArbolNodo models with adjacency list"
```

---

### Task 5: Agregar relaciones en ProgramaPresupuestario

**Files:**
- Modify: `app/Models/ProgramaPresupuestario.php`

**Step 1: Agregar relaciones hasMany arboles**

Agregar al modelo `ProgramaPresupuestario`:

```php
use App\Models\Mml\Arbol;

public function arboles(): HasMany
{
    return $this->hasMany(Arbol::class, 'programa_presupuestario_id');
}

public function arbolProblema()
{
    return $this->arboles()->where('tipo', 'problema')->first();
}

public function arbolObjetivos()
{
    return $this->arboles()->where('tipo', 'objetivos')->first();
}
```

Importar `HasMany`:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

**Step 2: Commit**

```bash
git add app/Models/ProgramaPresupuestario.php
git commit -m "feat(S3-T2): add arboles relations to ProgramaPresupuestario"
```

---

## Criterios de Aceptación

- [ ] Enum TipoNodo cubre los 10 tipos: problema_central, causa_directa, causa_indirecta, efecto_directo, efecto_indirecto, objetivo_central, medio_directo, medio_indirecto, fin_directo, fin_indirecto
- [ ] Relación recursiva: `ArbolNodo hasMany children`, `belongsTo parent`
- [ ] `nodo_origen_id` permite vincular nodos del árbol de objetivos con su nodo origen del árbol de problemas
- [ ] Índice en parent_id
- [ ] Constraint unique en (programa_presupuestario_id, tipo) para arboles
- [ ] Cascade delete: eliminar árbol elimina sus nodos
- [ ] Tests pasan
