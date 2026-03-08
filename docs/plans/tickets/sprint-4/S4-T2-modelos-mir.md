# S4-T2: Migraciones y Modelos para MIR — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T2
**Tipo:** feat
**Rama:** `feat/S4-T2-modelos-mir`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S3-T1 (ProgramaPresupuestario extendido), S1-T7 (Roles y Permisos)

**Goal:** Crear las tablas `mir_niveles` y `mir_versiones` para almacenar la Matriz de Indicadores para Resultados con sus 4 niveles jerárquicos (Fin, Propósito, Componente, Actividad) y soporte de snapshots.

**Architecture:** `mir_niveles` usa una FK recursiva `componente_id` (en lugar de `parent_id` genérico) para garantizar que una Actividad solo pueda pertenecer a un Componente. Incluye FKs opcionales para alineación con la cascada de planes y para UR Coadyuvante. `mir_versiones` almacena snapshots completos como JSONB.

**Tech Stack:** Laravel 12, PostgreSQL, Eloquent, PHP 8.1 Backed Enums

---

## Contexto

La MIR es el producto central del sistema. Tiene exactamente 4 niveles: Fin → Propósito → Componente → Actividad. El Fin y Propósito son únicos por programa; puede haber múltiples Componentes, y cada Componente puede tener múltiples Actividades. La trazabilidad al árbol de nodos se mantiene vía `arbol_nodo_id`.

---

## Pre-requisitos

- S3-T1 completado (tabla `programa_presupuestarios` con `team_id`)
- Tablas `arbol_nodos`, `ped_objetivos_estrategicos`, `programa_derivado_objetivos`, `ped_lineas_accion` existentes

---

## Pasos

### Task 1: Crear Enum TipoNivelMir

**Files:**
- Create: `app/Enums/TipoNivelMir.php`

**Step 1: Crear el enum**

```php
<?php

namespace App\Enums;

enum TipoNivelMir: string
{
    case FIN = 'fin';
    case PROPOSITO = 'proposito';
    case COMPONENTE = 'componente';
    case ACTIVIDAD = 'actividad';

    public function label(): string
    {
        return match($this) {
            self::FIN => 'Fin',
            self::PROPOSITO => 'Propósito',
            self::COMPONENTE => 'Componente',
            self::ACTIVIDAD => 'Actividad',
        };
    }

    public function orden(): int
    {
        return match($this) {
            self::FIN => 1,
            self::PROPOSITO => 2,
            self::COMPONENTE => 3,
            self::ACTIVIDAD => 4,
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::FIN => 'bg-blue-100 text-blue-800 border-blue-300',
            self::PROPOSITO => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::COMPONENTE => 'bg-amber-100 text-amber-800 border-amber-300',
            self::ACTIVIDAD => 'bg-violet-100 text-violet-800 border-violet-300',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

**Step 2: Commit**

```bash
git add app/Enums/TipoNivelMir.php
git commit -m "feat(S4-T2): add TipoNivelMir enum"
```

---

### Task 2: Crear migraciones

**Files:**
- Create: migración `create_mir_niveles_table`
- Create: migración `create_mir_versiones_table`

**Step 1: Crear migración mir_niveles**

```php
Schema::create('mir_niveles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('programa_presupuestario_id')
        ->constrained('programa_presupuestarios')->cascadeOnDelete();
    $table->string('tipo_nivel', 20); // TipoNivelMir enum
    $table->foreignId('componente_id')
        ->nullable()->constrained('mir_niveles')->cascadeOnDelete();
    $table->text('resumen_narrativo')->nullable();
    $table->text('supuestos')->nullable();
    $table->foreignId('arbol_nodo_id')
        ->nullable()->constrained('arbol_nodos')->nullOnDelete();
    $table->smallInteger('orden')->default(0);

    // FKs de alineación con cascada de planes
    $table->foreignId('ped_objetivo_estrategico_id')
        ->nullable()->constrained('ped_objetivos_estrategicos')->nullOnDelete();
    $table->foreignId('programa_derivado_objetivo_id')
        ->nullable()->constrained('programa_derivado_objetivos')->nullOnDelete();
    $table->foreignId('ped_linea_accion_id')
        ->nullable()->constrained('ped_lineas_accion')->nullOnDelete();

    // UR Coadyuvante
    $table->foreignId('team_id')
        ->nullable()->constrained('teams')->nullOnDelete();

    $table->timestamps();

    $table->index(['programa_presupuestario_id', 'tipo_nivel']);
    $table->index('componente_id');
});
```

**Step 2: Crear migración mir_versiones**

```php
Schema::create('mir_versiones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('programa_presupuestario_id')
        ->constrained('programa_presupuestarios')->cascadeOnDelete();
    $table->string('etiqueta', 100);
    $table->jsonb('snapshot');
    $table->foreignId('created_by')
        ->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();

    $table->index('programa_presupuestario_id');
});
```

**Step 3: Commit**

```bash
git add database/migrations/
git commit -m "feat(S4-T2): add mir_niveles and mir_versiones migrations"
```

---

### Task 3: Crear modelos

**Files:**
- Create: `app/Models/Mml/MirNivel.php`
- Create: `app/Models/Mml/MirVersion.php`

**Step 1: Crear modelo MirNivel**

```php
<?php

namespace App\Models\Mml;

use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MirNivel extends Model
{
    protected $table = 'mir_niveles';

    protected $fillable = [
        'programa_presupuestario_id', 'tipo_nivel', 'componente_id',
        'resumen_narrativo', 'supuestos', 'arbol_nodo_id', 'orden',
        'ped_objetivo_estrategico_id', 'programa_derivado_objetivo_id',
        'ped_linea_accion_id', 'team_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo_nivel' => TipoNivelMir::class,
            'orden' => 'integer',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(self::class, 'componente_id');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(self::class, 'componente_id')->orderBy('orden');
    }

    public function nodoOrigen(): BelongsTo
    {
        return $this->belongsTo(ArbolNodo::class, 'arbol_nodo_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }
}
```

**Step 2: Crear modelo MirVersion**

```php
<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MirVersion extends Model
{
    protected $table = 'mir_versiones';

    protected $fillable = [
        'programa_presupuestario_id', 'etiqueta', 'snapshot', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

**Step 3: Agregar relaciones en ProgramaPresupuestario**

En `app/Models/ProgramaPresupuestario.php`, agregar:

```php
public function mirNiveles(): HasMany
{
    return $this->hasMany(Mml\MirNivel::class);
}

public function mirVersiones(): HasMany
{
    return $this->hasMany(Mml\MirVersion::class);
}
```

**Step 4: Commit**

```bash
git add app/Models/Mml/MirNivel.php app/Models/Mml/MirVersion.php app/Models/ProgramaPresupuestario.php
git commit -m "feat(S4-T2): add MirNivel and MirVersion models with relations"
```

---

### Task 4: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/MirNivelTest.php`

**Step 1: Escribir tests**

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\Mml\MirVersion;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MirNivelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_crear_nivel_fin(): void
    {
        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Contribuir a la mejora educativa',
            'orden' => 1,
        ]);

        $this->assertDatabaseHas('mir_niveles', [
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Contribuir a la mejora educativa',
        ]);
    }

    public function test_jerarquia_componente_actividad(): void
    {
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Becas entregadas',
            'orden' => 1,
        ]);

        $actividad = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $componente->id,
            'resumen_narrativo' => 'Registro de beneficiarios',
            'orden' => 1,
        ]);

        $this->assertEquals($componente->id, $actividad->componente->id);
        $this->assertTrue($componente->actividades->contains($actividad));
    }

    public function test_cast_tipo_nivel_enum(): void
    {
        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);

        $this->assertInstanceOf(TipoNivelMir::class, $fin->fresh()->tipo_nivel);
    }

    public function test_relacion_programa(): void
    {
        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);

        $this->assertEquals($this->programa->id, $fin->programa->id);
    }

    public function test_cascade_delete(): void
    {
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => 1,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $componente->id,
            'orden' => 1,
        ]);

        $componente->delete();
        $this->assertDatabaseMissing('mir_niveles', ['componente_id' => $componente->id]);
    }

    public function test_crear_mir_version_snapshot(): void
    {
        $version = MirVersion::create([
            'programa_presupuestario_id' => $this->programa->id,
            'etiqueta' => 'Versión 1.0',
            'snapshot' => ['niveles' => [['tipo' => 'fin', 'texto' => 'Test']]],
            'created_by' => $this->user->id,
        ]);

        $this->assertIsArray($version->fresh()->snapshot);
        $this->assertEquals('Versión 1.0', $version->etiqueta);
    }

    public function test_relacion_programa_mir_niveles(): void
    {
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);

        $this->assertEquals(1, $this->programa->mirNiveles()->count());
    }
}
```

**Step 2: Ejecutar tests**

```bash
sail artisan test --filter=MirNivelTest
```

Expected: PASS (7 tests).

**Step 3: Ejecutar suite completo**

```bash
sail artisan test
```

**Step 4: Commit**

```bash
git add tests/Feature/Mml/MirNivelTest.php
git commit -m "feat(S4-T2): add MirNivel tests"
```

---

### Task 5: Actualizar documentación del esquema

**Files:**
- Create: `docs/schema/mir.md`

**Step 1: Crear documentación**

Documentar tablas `mir_niveles` y `mir_versiones` con sus campos, relaciones y restricciones.

**Step 2: Commit**

```bash
git add docs/schema/mir.md
git commit -m "docs(S4-T2): add MIR schema documentation"
```

---

## Criterios de Aceptación

- [ ] Enum TipoNivelMir con 4 cases: fin, proposito, componente, actividad
- [ ] Migración `mir_niveles` con FK recursiva `componente_id`
- [ ] Relación Actividad belongsTo Componente y Componente hasMany Actividades
- [ ] Trazabilidad a `arbol_nodos` vía `arbol_nodo_id`
- [ ] Tabla `mir_versiones` con campo `snapshot` JSONB
- [ ] Documentación del esquema actualizada
- [ ] Tests pasan
