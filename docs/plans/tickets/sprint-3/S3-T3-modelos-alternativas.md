# S3-T3: Migraciones y Modelos para Alternativas — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S3-T3
**Tipo:** feat
**Rama:** `feat/S3-T3-modelos-alternativas`
**Sprint:** 3 — Metodología de Marco Lógico (Etapas 1-4)
**Depende de:** S3-T2 (Modelos Árboles)

**Goal:** Crear tablas `alternativas` y `alternativa_nodo` (pivote) para representar las estrategias candidatas y su selección en la Etapa 4 de MML.

**Architecture:** Cada `ProgramaPresupuestario` puede tener múltiples alternativas. Cada alternativa agrupa nodos del árbol de objetivos (medios) vía tabla pivote. Una alternativa se marca como `seleccionada` con su `justificacion_seleccion`. Los nodos no seleccionados quedan "podados".

**Tech Stack:** Laravel 12, PostgreSQL, Eloquent BelongsToMany (pivote)

---

## Pre-requisitos

- S3-T2 completado (tablas `arboles` y `arbol_nodos`)
- S3-T1 completado (tabla `programa_presupuestarios` extendida)

---

## Pasos

### Task 1: Crear migración para tabla alternativas

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_alternativas_table.php`

**Step 1: Crear la migración**

```bash
sail artisan make:migration create_alternativas_table
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
        Schema::create('alternativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->string('nombre');
            $table->boolean('seleccionada')->default(false);
            $table->text('justificacion_seleccion')->nullable();
            $table->timestamps();

            $table->index('programa_presupuestario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternativas');
    }
};
```

**Step 3: Ejecutar migración**

```bash
sail artisan migrate
```

**Step 4: Commit**

```bash
git add database/migrations/*create_alternativas_table*
git commit -m "feat(S3-T3): create alternativas migration"
```

---

### Task 2: Crear migración para tabla pivote alternativa_nodo

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_alternativa_nodo_table.php`

**Step 1: Crear la migración**

```bash
sail artisan make:migration create_alternativa_nodo_table
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
        Schema::create('alternativa_nodo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alternativa_id')->constrained('alternativas')->cascadeOnDelete();
            $table->foreignId('arbol_nodo_id')->constrained('arbol_nodos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['alternativa_id', 'arbol_nodo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternativa_nodo');
    }
};
```

**Step 3: Ejecutar migración**

```bash
sail artisan migrate
```

**Step 4: Commit**

```bash
git add database/migrations/*create_alternativa_nodo_table*
git commit -m "feat(S3-T3): create alternativa_nodo pivot migration"
```

---

### Task 3: Crear modelos Alternativa

**Files:**
- Create: `app/Models/Mml/Alternativa.php`
- Create: `tests/Feature/Mml/AlternativaTest.php`

**Step 1: Escribir test**

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlternativaTest extends TestCase
{
    use RefreshDatabase;

    private function crearContextoMml(): array
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test', 'clave' => 'PT-001',
            'team_id' => $user->currentTeam->id,
        ]);
        $arbolObj = Arbol::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);
        $medio1 = ArbolNodo::create([
            'arbol_id' => $arbolObj->id,
            'tipo_nodo' => TipoNodo::MEDIO_DIRECTO->value,
            'descripcion' => 'Medio directo 1',
        ]);
        $medio2 = ArbolNodo::create([
            'arbol_id' => $arbolObj->id,
            'tipo_nodo' => TipoNodo::MEDIO_INDIRECTO->value,
            'descripcion' => 'Medio indirecto 1',
        ]);

        return [$programa, $arbolObj, $medio1, $medio2];
    }

    public function test_crear_alternativa(): void
    {
        [$programa] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alternativa A',
        ]);

        $this->assertDatabaseHas('alternativas', [
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alternativa A',
            'seleccionada' => false,
        ]);
    }

    public function test_alternativa_pertenece_a_programa(): void
    {
        [$programa] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alt A',
        ]);

        $this->assertInstanceOf(ProgramaPresupuestario::class, $alternativa->programa);
    }

    public function test_asignar_nodos_a_alternativa(): void
    {
        [$programa, $arbol, $medio1, $medio2] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alt A',
        ]);

        $alternativa->nodos()->attach([$medio1->id, $medio2->id]);

        $this->assertCount(2, $alternativa->nodos);
    }

    public function test_seleccionar_alternativa_con_justificacion(): void
    {
        [$programa] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alt A',
            'seleccionada' => true,
            'justificacion_seleccion' => 'Mayor viabilidad técnica e institucional',
        ]);

        $this->assertTrue($alternativa->seleccionada);
        $this->assertEquals('Mayor viabilidad técnica e institucional', $alternativa->justificacion_seleccion);
    }

    public function test_cascade_delete_programa_elimina_alternativas(): void
    {
        [$programa, $arbol, $medio1] = $this->crearContextoMml();

        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $programa->id,
            'nombre' => 'Alt A',
        ]);
        $alternativa->nodos()->attach($medio1->id);

        $programa->forceDelete();

        $this->assertDatabaseMissing('alternativas', ['id' => $alternativa->id]);
        $this->assertDatabaseMissing('alternativa_nodo', ['alternativa_id' => $alternativa->id]);
    }
}
```

**Step 2: Ejecutar tests para verificar que fallan**

```bash
sail artisan test --filter=AlternativaTest
```

Expected: FAIL.

**Step 3: Crear modelo Alternativa**

Crear `app/Models/Mml/Alternativa.php`:

```php
<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alternativa extends Model
{
    protected $fillable = [
        'programa_presupuestario_id',
        'nombre',
        'seleccionada',
        'justificacion_seleccion',
    ];

    protected function casts(): array
    {
        return [
            'seleccionada' => 'boolean',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function nodos(): BelongsToMany
    {
        return $this->belongsToMany(ArbolNodo::class, 'alternativa_nodo')
                    ->withTimestamps();
    }
}
```

**Step 4: Ejecutar tests para verificar que pasan**

```bash
sail artisan test --filter=AlternativaTest
```

Expected: PASS.

**Step 5: Agregar relaciones en ProgramaPresupuestario**

Agregar a `app/Models/ProgramaPresupuestario.php`:

```php
use App\Models\Mml\Alternativa;

public function alternativas(): HasMany
{
    return $this->hasMany(Alternativa::class, 'programa_presupuestario_id');
}
```

**Step 6: Ejecutar test suite completo**

```bash
sail artisan test
```

**Step 7: Commit**

```bash
git add app/Models/Mml/Alternativa.php app/Models/ProgramaPresupuestario.php tests/Feature/Mml/AlternativaTest.php
git commit -m "feat(S3-T3): add Alternativa model with pivot to ArbolNodo"
```

---

## Criterios de Aceptación

- [ ] Alternativa belongsTo ProgramaPresupuestario
- [ ] Pivote vincula alternativa con nodos del árbol de objetivos
- [ ] Campo `seleccionada` (boolean) marca la alternativa elegida
- [ ] Campo `justificacion_seleccion` documenta la decisión
- [ ] Cascade delete funciona correctamente
- [ ] Tests pasan
