# Plan: S2-T4 — Migraciones y Modelos para Programas Derivados

**Ticket:** S2-T4
**Tipo:** feat
**Rama:** `feat/S2-T4-programas-derivados`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T3 (tabla `ped_planes` existente)

---

## Contexto

Los Programas Derivados son instrumentos de planeación intermedios que emanan del PED y tienen objetivos propios. Se clasifican en 4 tipos: **Sectoriales, Especiales, Institucionales y Regionales**. Sus objetivos se vinculan con las Líneas de Acción del PED mediante la Matriz de Alineación (S2-T5).

**Características especiales:**

- Uso de **ENUM nativo de PostgreSQL** para el tipo (requiere manejo especial en migraciones)
- Solo los objetivos tienen columna `embedding` (no los programas en sí)
- Relación directa con `ped_planes` (no con niveles intermedios del PED)

---

## Pre-requisitos

- S2-T3 completado (tabla `ped_planes` con datos)
- Extensión pgvector habilitada (S0-T2)

---

## Pasos

### 1. Crear Backed Enum PHP

```bash
mkdir -p app/Enums
```

Crear `app/Enums/TipoProgramaDerivado.php`:

```php
<?php

namespace App\Enums;

enum TipoProgramaDerivado: string
{
    case SECTORIAL = 'sectorial';
    case ESPECIAL = 'especial';
    case INSTITUCIONAL = 'institucional';
    case REGIONAL = 'regional';

    /**
     * Retorna etiqueta legible para mostrar en UI.
     */
    public function label(): string
    {
        return match($this) {
            self::SECTORIAL => 'Programa Sectorial',
            self::ESPECIAL => 'Programa Especial',
            self::INSTITUCIONAL => 'Programa Institucional',
            self::REGIONAL => 'Programa Regional',
        };
    }

    /**
     * Retorna descripción del tipo.
     */
    public function descripcion(): string
    {
        return match($this) {
            self::SECTORIAL => 'Programas que abordan temas sectoriales específicos del desarrollo estatal.',
            self::ESPECIAL => 'Programas diseñados para atender problemáticas específicas o emergentes.',
            self::INSTITUCIONAL => 'Programas que orientan la gestión interna de una institución.',
            self::REGIONAL => 'Programas enfocados al desarrollo de regiones geográficas específicas.',
        };
    }

    /**
     * Retorna todos los valores para validaciones.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

---

### 2. Crear migración: Programas Derivados

```bash
sail artisan make:migration create_programas_derivados_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear ENUM nativo de PostgreSQL primero
        DB::statement(
            "CREATE TYPE tipo_programa_derivado AS ENUM ('sectorial', 'especial', 'institucional', 'regional')"
        );

        // 2. Crear tabla
        Schema::create('programas_derivados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_plan_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            // Columna tipo usando ENUM nativo via raw SQL
            // Se agrega después para controlar el tipo exacto
        });

        // 3. Agregar columna tipo como ENUM nativo
        DB::statement(
            "ALTER TABLE programas_derivados ADD COLUMN tipo tipo_programa_derivado NOT NULL DEFAULT 'sectorial'"
        );

        // 4. Índice para búsquedas por tipo
        DB::statement(
            'CREATE INDEX programas_derivados_tipo_index ON programas_derivados (tipo)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('programas_derivados');

        // IMPORTANTE: Eliminar ENUM después de eliminar la tabla
        DB::statement('DROP TYPE IF EXISTS tipo_programa_derivado');
    }
};
```

---

### 3. Crear migración: Objetivos de Programas Derivados

```bash
sail artisan make:migration create_programas_derivados_objetivos_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programas_derivados_objetivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_derivado_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 20); // "OS.1", "OE.1", etc.
            $table->text('descripcion');
            $table->timestamps();

            $table->unique(['programa_derivado_id', 'clave']);
        });

        // Columna vectorial para búsqueda semántica
        DB::statement('ALTER TABLE programas_derivados_objetivos ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('programas_derivados_objetivos');
    }
};
```

---

### 4. Crear modelo: ProgramaDerivado

```bash
sail artisan make:model ProgramaDerivado
```

```php
<?php

namespace App\Models;

use App\Enums\TipoProgramaDerivado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramaDerivado extends Model
{
    protected $fillable = [
        'ped_plan_id',
        'nombre',
        'descripcion',
        'tipo',
    ];

    protected function casts(): array
    {
        return [
            'ped_plan_id' => 'integer',
            'tipo' => TipoProgramaDerivado::class,
        ];
    }

    // ============================================
    // Relaciones
    // ============================================

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PedPlan::class, 'ped_plan_id');
    }

    public function objetivos(): HasMany
    {
        return $this->hasMany(ProgramaDerivadoObjetivo::class, 'programa_derivado_id');
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopeSectoriales($query)
    {
        return $query->where('tipo', TipoProgramaDerivado::SECTORIAL);
    }

    public function scopeEspeciales($query)
    {
        return $query->where('tipo', TipoProgramaDerivado::ESPECIAL);
    }

    public function scopeInstitucionales($query)
    {
        return $query->where('tipo', TipoProgramaDerivado::INSTITUCIONAL);
    }

    public function scopeRegionales($query)
    {
        return $query->where('tipo', TipoProgramaDerivado::REGIONAL);
    }

    // ============================================
    // Métodos auxiliares
    // ============================================

    /**
     * Retorna el prefijo de clave según el tipo.
     * Útil para generar claves de objetivos automáticamente.
     */
    public function prefijoClave(): string
    {
        return match($this->tipo) {
            TipoProgramaDerivado::SECTORIAL => 'OS',
            TipoProgramaDerivado::ESPECIAL => 'OE',
            TipoProgramaDerivado::INSTITUCIONAL => 'OI',
            TipoProgramaDerivado::REGIONAL => 'OR',
        };
    }
}
```

---

### 5. Crear modelo: ProgramaDerivadoObjetivo

```bash
sail artisan make:model ProgramaDerivadoObjetivo
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramaDerivadoObjetivo extends Model
{
    protected $fillable = [
        'programa_derivado_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'programa_derivado_id' => 'integer',
        ];
    }

    // ============================================
    // Relaciones
    // ============================================

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaDerivado::class, 'programa_derivado_id');
    }

    public function plan()
    {
        return $this->programa->plan;
    }

    // ============================================
    // Accessors
    // ============================================

    /**
     * Retorna clave completa con prefijo del programa.
     */
    public function getClaveCompletaAttribute(): string
    {
        return $this->programa->prefijoClave() . '.' . $this->clave;
    }
}
```

---

### 6. Crear Seeder

```bash
sail artisan make:seeder ProgramasDerivadosSeeder
```

```php
<?php

namespace Database\Seeders;

use App\Enums\TipoProgramaDerivado;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Database\Seeder;

class ProgramasDerivadosSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando programas derivados de prueba...');

        // Obtener plan activo
        $plan = PedPlan::where('activo', true)->first();

        if (!$plan) {
            $this->command->error('No existe un plan activo. Ejecuta PedSeeder primero.');
            return;
        }

        // 1. Programa Sectorial
        $sectorial = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Sectorial de Educación'],
            [
                'tipo' => TipoProgramaDerivado::SECTORIAL,
                'descripcion' => 'Programa que orienta las acciones del sector educativo estatal.',
            ]
        );
        $this->crearObjetivos($sectorial, 4);
        $this->command->info("  {$sectorial->prefijoClave()}: {$sectorial->nombre}");

        // 2. Programa Especial
        $especial = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Especial de Cambio Climático'],
            [
                'tipo' => TipoProgramaDerivado::ESPECIAL,
                'descripcion' => 'Programa para atender los efectos del cambio climático en la entidad.',
            ]
        );
        $this->crearObjetivos($especial, 3);
        $this->command->info("  {$especial->prefijoClave()}: {$especial->nombre}");

        // 3. Programa Institucional
        $institucional = ProgramaDerivado::updateOrCreate(
            ['ped_plan_id' => $plan->id, 'nombre' => 'Programa Institucional de Modernización Administrativa'],
            [
                'tipo' => TipoProgramaDerivado::INSTITUCIONAL,
                'descripcion' => 'Programa para fortalecer la gestión pública institucional.',
            ]
        );
        $this->crearObjetivos($institucional, 3);
        $this->command->info("  {$institucional->prefijoClave()}: {$institucional->nombre}");

        // Resumen
        $this->command->newLine();
        $this->command->table(
            ['Tipo', 'Programa', 'Objetivos'],
            [
                [
                    'Sectorial',
                    $sectorial->nombre,
                    $sectorial->objetivos()->count()
                ],
                [
                    'Especial',
                    $especial->nombre,
                    $especial->objetivos()->count()
                ],
                [
                    'Institucional',
                    $institucional->nombre,
                    $institucional->objetivos()->count()
                ],
            ]
        );
    }

    /**
     * Crea objetivos para un programa derivado.
     */
    private function crearObjetivos(ProgramaDerivado $programa, int $cantidad): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            ProgramaDerivadoObjetivo::updateOrCreate(
                [
                    'programa_derivado_id' => $programa->id,
                    'clave' => (string) $i
                ],
                [
                    'descripcion' => "Objetivo {$i} del {$programa->nombre}"
                ]
            );
        }
    }
}
```

---

### 7. Registrar Seeder

Editar `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([
        RolesAndPermissionsSeeder::class,
        DesarrolloSeeder::class,
        OdsSeeder::class,
        PndSeeder::class,
        PedSeeder::class,
        ProgramasDerivadosSeeder::class,  // Nuevo
    ]);
}
```

---

### 8. Crear Test: Validación del ENUM

```bash
sail artisan make:test ProgramasDerivadosEnumTest
```

```php
<?php

namespace Tests\Feature;

use App\Enums\TipoProgramaDerivado;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramasDerivadosEnumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PedSeeder::class);
    }

    public function test_enum_php_contiene_los_cuatro_tipos(): void
    {
        $cases = TipoProgramaDerivado::cases();

        $this->assertCount(4, $cases);
        $this->assertEquals('sectorial', TipoProgramaDerivado::SECTORIAL->value);
        $this->assertEquals('especial', TipoProgramaDerivado::ESPECIAL->value);
        $this->assertEquals('institucional', TipoProgramaDerivado::INSTITUCIONAL->value);
        $this->assertEquals('regional', TipoProgramaDerivado::REGIONAL->value);
    }

    public function test_modelo_castea_enum_correctamente(): void
    {
        $plan = PedPlan::first();

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $this->assertInstanceOf(TipoProgramaDerivado::class, $programa->tipo);
        $this->assertEquals(TipoProgramaDerivado::SECTORIAL, $programa->tipo);
    }

    public function test_no_permite_valor_invalido_en_enum(): void
    {
        $this->expectException(QueryException::class);

        $plan = PedPlan::first();

        // Intentar insertar valor no válido en el ENUM
        DB::table('programas_derivados')->insert([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Test Invalid',
            'tipo' => 'valor_invalido',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_scope_sectoriales_filtra_correctamente(): void
    {
        $plan = PedPlan::first();

        ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Sectorial 1',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Especial 1',
            'tipo' => TipoProgramaDerivado::ESPECIAL,
        ]);

        $sectoriales = ProgramaDerivado::sectoriales()->get();

        $this->assertCount(1, $sectoriales);
        $this->assertEquals('Sectorial 1', $sectoriales->first()->nombre);
    }

    public function test_prefijo_clave_por_tipo(): void
    {
        $plan = PedPlan::first();

        $sectorial = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Test Sectorial',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $especial = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Test Especial',
            'tipo' => TipoProgramaDerivado::ESPECIAL,
        ]);

        $this->assertEquals('OS', $sectorial->prefijoClave());
        $this->assertEquals('OE', $especial->prefijoClave());
    }
}
```

---

### 9. Ejecutar y Verificar

```bash
# Ejecutar migraciones
sail artisan migrate

# Verificar ENUM en PostgreSQL
sail shell
psql -U sail -d laravel -c "\dT tipo_programa_derivado"
# Esperado:
# List of data types
# Schema | Name | Description
# -------+------------------------+-------------
# public | tipo_programa_derivado |

psql -U sail -d laravel -c "SELECT unnest(enum_range(NULL::tipo_programa_derivado));"
# Esperado:
# unnest
# ------------
# sectorial
# especial
# institucional
# regional
exit

# Ejecutar seeder
sail artisan db:seed --class=ProgramasDerivadosSeeder

# Ejecutar tests
sail artisan test --filter ProgramasDerivadosEnumTest

# Verificar rollback
sail artisan migrate:rollback --step=2
sail artisan migrate
```

Verificación en Tinker:

```php
use App\Models\ProgramaDerivado;
use App\Enums\TipoProgramaDerivado;

// Verificar programas creados
ProgramaDerivado::count();

// Verificar tipos
ProgramaDerivado::where('tipo', TipoProgramaDerivado::SECTORIAL)->count();

// Verificar relación
$programa = ProgramaDerivado::with('objetivos', 'plan')->first();
$programa->tipo->label();        // "Programa Sectorial"
$programa->prefijoClave();        // "OS"
$programa->objetivos->count();    // 4

// Verificar clave completa de objetivo
$objetivo = $programa->objetivos->first();
$objetivo->clave_completa;        // "OS.1"
```

---

### 10. Documentar Esquema

Crear `docs/schema/programas-derivados.md`:

````markdown
# Esquema: Programas Derivados

Instrumentos de planeación intermedios que emanan del PED.

## Tipos de Programa

| Tipo          | Prefijo | Descripción                                  |
| ------------- | ------- | -------------------------------------------- |
| Sectorial     | OS      | Programas sectoriales del desarrollo estatal |
| Especial      | OE      | Programas para problemáticas específicas     |
| Institucional | OI      | Programas de gestión institucional           |
| Regional      | OR      | Programas de desarrollo regional             |

## Tablas

### programas_derivados

| Columna     | Tipo   | Notas                                      |
| ----------- | ------ | ------------------------------------------ |
| id          | bigint | PK                                         |
| ped_plan_id | FK     | Cascade on delete                          |
| tipo        | ENUM   | `tipo_programa_derivado` nativo PostgreSQL |
| nombre      | string | Nombre del programa                        |
| descripcion | text   | Nullable                                   |
| timestamps  | —      | —                                          |

**ENUM PostgreSQL:**

```sql
CREATE TYPE tipo_programa_derivado AS ENUM (
    'sectorial',
    'especial',
    'institucional',
    'regional'
);
```
````

### programas_derivados_objetivos

| Columna              | Tipo         | Notas                                  |
| -------------------- | ------------ | -------------------------------------- |
| id                   | bigint       | PK                                     |
| programa_derivado_id | FK           | Cascade on delete                      |
| clave                | string(20)   | "1", "2", etc. UNIQUE con programa_id  |
| descripcion          | text         | Texto del objetivo                     |
| embedding            | vector(1536) | Búsqueda semántica (null hasta S2-T10) |
| timestamps           | —            | —                                      |

## Relaciones Eloquent

```php
// Navegación
$programa->plan;        // BelongsTo PedPlan
$programa->objetivos;   // HasMany ProgramaDerivadoObjetivo

$objetivo->programa;    // BelongsTo ProgramaDerivado
$objetivo->plan;        // A través de programa

// Scopes por tipo
ProgramaDerivado::sectoriales()->get();
ProgramaDerivado::especiales()->get();
ProgramaDerivado::institucionales()->get();
ProgramaDerivado::regionales()->get();

// Utilidades
$programa->tipo->label();      // "Programa Sectorial"
$programa->prefijoClave();      // "OS"
$objetivo->clave_completa;      // "OS.1"
```

## PHP Enum

```php
use App\Enums\TipoProgramaDerivado;

TipoProgramaDerivado::SECTORIAL;      // case
TipoProgramaDerivado::SECTORIAL->value; // "sectorial"
TipoProgramaDerivado::SECTORIAL->label(); // "Programa Sectorial"
TipoProgramaDerivado::values(); // ['sectorial', 'especial', ...]
```

## Vinculación con PED

Los objetivos de programas derivados se vinculan con Líneas de Acción del PED mediante la **Matriz de Alineación** (S2-T5).

```
ProgramaDerivadoObjetivo ↔ PedLineaAccion
         (N:N via tabla pivote)
```

```

---

## Criterios de Aceptación

- [ ] ENUM nativo PostgreSQL `tipo_programa_derivado` creado con 4 valores
- [ ] Verificación en psql: `\dT tipo_programa_derivado` muestra el tipo
- [ ] Verificación en psql: `enum_range(NULL::tipo_programa_derivado)` lista los 4 valores
- [ ] Backed Enum PHP `App\Enums\TipoProgramaDerivado` con métodos `label()` y `values()`
- [ ] Modelo `ProgramaDerivado` castea `tipo` al Enum PHP correctamente
- [ ] Columna `embedding vector(1536)` en `programas_derivados_objetivos`
- [ ] FK `ped_plan_id` con `cascadeOnDelete()` funciona
- [ ] Scopes por tipo funcionan (`sectoriales()`, `especiales()`, etc.)
- [ ] Método `prefijoClave()` retorna prefijo correcto según tipo
- [ ] Seeder crea 3 programas de tipos distintos con objetivos
- [ ] Test `ProgramasDerivadosEnumTest` pasa (5 assertions)
- [ ] `migrate:rollback --step=2` elimina tablas y ENUM sin errores
- [ ] Documentación `docs/schema/programas-derivados.md` creada

---

## Notas Importantes

### Sobre ENUM nativo PostgreSQL

| Aspecto | Consideración |
|---------|---------------|
| Orden de creación | ENUM debe crearse **antes** de la tabla |
| Orden de eliminación | ENUM debe eliminarse **después** de la tabla |
| Modificación | Agregar valores requiere `ALTER TYPE ... ADD VALUE` |
| Performance | ENUM nativo es más eficiente que string con validación |
| Portabilidad | Menos portable entre BD; usar string si se planea migrar |

### Rollback correcto

El `down()` de la migración de `programas_derivados` **siempre** debe:

1. `Schema::dropIfExists('programas_derivados')`
2. `DB::statement('DROP TYPE IF EXISTS tipo_programa_derivado')`

El orden es crítico: no se puede eliminar un TYPE si una columna lo está usando.

---

## Diferencias con otros catálogos

| Aspecto | ODS/PND (S2-T1, T2) | PED (S2-T3) | Programas Derivados (S2-T4) |
|---------|---------------------|-------------|------------------------------|
| Naturaleza | Inmutable | Editable | Editable |
| ENUM nativo | No | No | **Sí** (tipo) |
| Embedding | En todos los niveles | En niveles 2-6 | Solo en objetivos |
| FK cascade | No aplica | Sí | Sí |

---
```
