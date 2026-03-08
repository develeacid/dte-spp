# S4-T3: Migraciones y Modelos para Indicadores y Ficha Técnica — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T3
**Tipo:** feat
**Rama:** `feat/S4-T3-modelos-indicadores`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T2 (MirNivel)

**Goal:** Crear las tablas `indicadores`, `indicador_variables`, `catalogo_unidades_medida`, `medios_verificacion` y `cremaa_validaciones` que componen la ficha técnica completa de un indicador MIR.

**Architecture:** Cada `MirNivel` puede tener uno o más `indicadores`. Cada indicador tiene variables, medios de verificación y validaciones CREMAA. Se usan Backed Enums para tipo, dimensión, frecuencia y sentido del indicador. El catálogo de unidades de medida sigue estándares CONAC.

**Tech Stack:** Laravel 12, PostgreSQL, Eloquent, PHP 8.1 Backed Enums, Seeders

---

## Contexto

La ficha técnica del indicador es un formulario extenso con campos normados por SHCP/CONEVAL. Los indicadores miden el cumplimiento de cada nivel de la MIR. Cada indicador tiene una fórmula con variables explícitas, medios de verificación que sustentan los datos, y una validación CREMAA (Claro, Relevante, Económico, Monitoreable, Adecuado, Aportante).

---

## Pre-requisitos

- S4-T2 completado (tabla `mir_niveles`)

---

## Pasos

### Task 1: Crear Enums de Indicador

**Files:**
- Create: `app/Enums/TipoIndicador.php`
- Create: `app/Enums/DimensionIndicador.php`
- Create: `app/Enums/FrecuenciaMedicion.php`
- Create: `app/Enums/SentidoIndicador.php`

**Step 1: Crear los 4 enums**

```php
// TipoIndicador: ESTRATEGICO = 'estrategico', GESTION = 'gestion'
// DimensionIndicador: EFICACIA = 'eficacia', EFICIENCIA = 'eficiencia',
//   CALIDAD = 'calidad', ECONOMIA = 'economia'
// FrecuenciaMedicion: MENSUAL = 'mensual', TRIMESTRAL = 'trimestral',
//   SEMESTRAL = 'semestral', ANUAL = 'anual', BIANUAL = 'bianual', SEXENAL = 'sexenal'
// SentidoIndicador: ASCENDENTE = 'ascendente', DESCENDENTE = 'descendente',
//   REGULAR = 'regular'
```

Cada enum con métodos `label()` y `values()`.

**Step 2: Commit**

```bash
git add app/Enums/
git commit -m "feat(S4-T3): add indicator enums (tipo, dimension, frecuencia, sentido)"
```

---

### Task 2: Crear migraciones

**Files:**
- Create: migración `create_indicadores_table`
- Create: migración `create_indicador_variables_table`
- Create: migración `create_catalogo_unidades_medida_table`
- Create: migración `create_medios_verificacion_table`
- Create: migración `create_cremaa_validaciones_table`

**Step 1: Migración indicadores**

```php
Schema::create('indicadores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('mir_nivel_id')
        ->constrained('mir_niveles')->cascadeOnDelete();
    $table->string('nombre');
    $table->text('formula_texto')->nullable();
    $table->string('tipo', 20); // TipoIndicador enum
    $table->string('dimension', 20); // DimensionIndicador enum
    $table->string('frecuencia', 20); // FrecuenciaMedicion enum
    $table->string('sentido', 20)->nullable(); // SentidoIndicador enum
    $table->decimal('linea_base', 12, 4)->nullable();
    $table->decimal('meta', 12, 4)->nullable();
    $table->decimal('rango_verde_min', 8, 2)->nullable();
    $table->decimal('rango_verde_max', 8, 2)->nullable();
    $table->decimal('rango_amarillo_min', 8, 2)->nullable();
    $table->decimal('rango_amarillo_max', 8, 2)->nullable();
    $table->decimal('rango_rojo_min', 8, 2)->nullable();
    $table->decimal('rango_rojo_max', 8, 2)->nullable();
    $table->foreignId('unidad_medida_id')
        ->nullable()->constrained('catalogo_unidades_medida')->nullOnDelete();
    $table->smallInteger('orden')->default(0);
    $table->timestamps();

    $table->index('mir_nivel_id');
});
```

**Step 2: Migración indicador_variables**

```php
Schema::create('indicador_variables', function (Blueprint $table) {
    $table->id();
    $table->foreignId('indicador_id')
        ->constrained('indicadores')->cascadeOnDelete();
    $table->string('simbolo', 5); // A, B, C...
    $table->string('nombre');
    $table->text('descripcion')->nullable();
    $table->string('comportamiento', 20)->nullable(); // acumulable, continua
    $table->foreignId('unidad_medida_id')
        ->nullable()->constrained('catalogo_unidades_medida')->nullOnDelete();
    $table->smallInteger('orden')->default(0);
    $table->timestamps();

    $table->index('indicador_id');
});
```

**Step 3: Migración catalogo_unidades_medida** (ANTES de indicadores)

```php
Schema::create('catalogo_unidades_medida', function (Blueprint $table) {
    $table->id();
    $table->string('clave', 10)->unique();
    $table->string('nombre');
    $table->timestamps();
});
```

**Step 4: Migración medios_verificacion**

```php
Schema::create('medios_verificacion', function (Blueprint $table) {
    $table->id();
    $table->foreignId('indicador_id')
        ->constrained('indicadores')->cascadeOnDelete();
    $table->string('nombre');
    $table->text('descripcion')->nullable();
    $table->string('fuente')->nullable();
    $table->string('frecuencia', 20)->nullable();
    $table->smallInteger('orden')->default(0);
    $table->timestamps();

    $table->index('indicador_id');
});
```

**Step 5: Migración cremaa_validaciones**

```php
Schema::create('cremaa_validaciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('indicador_id')
        ->constrained('indicadores')->cascadeOnDelete();
    $table->boolean('claro')->default(false);
    $table->text('claro_observacion')->nullable();
    $table->boolean('relevante')->default(false);
    $table->text('relevante_observacion')->nullable();
    $table->boolean('economico')->default(false);
    $table->text('economico_observacion')->nullable();
    $table->boolean('monitoreable')->default(false);
    $table->text('monitoreable_observacion')->nullable();
    $table->boolean('adecuado')->default(false);
    $table->text('adecuado_observacion')->nullable();
    $table->boolean('aportante')->default(false);
    $table->text('aportante_observacion')->nullable();
    $table->timestamps();

    $table->index('indicador_id');
});
```

> **IMPORTANTE:** Crear la migración de `catalogo_unidades_medida` con timestamp ANTERIOR a `indicadores` e `indicador_variables` para que las FKs funcionen.

**Step 6: Commit**

```bash
git add database/migrations/
git commit -m "feat(S4-T3): add indicadores, variables, medios, cremaa migrations"
```

---

### Task 3: Crear modelos

**Files:**
- Create: `app/Models/Mml/Indicador.php`
- Create: `app/Models/Mml/IndicadorVariable.php`
- Create: `app/Models/Mml/MedioVerificacion.php`
- Create: `app/Models/Mml/CremaaValidacion.php`
- Create: `app/Models/CatalogoUnidadMedida.php`

**Step 1: Crear modelo Indicador**

```php
<?php

namespace App\Models\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\SentidoIndicador;
use App\Enums\TipoIndicador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Indicador extends Model
{
    protected $table = 'indicadores';

    protected $fillable = [
        'mir_nivel_id', 'nombre', 'formula_texto', 'tipo', 'dimension',
        'frecuencia', 'sentido', 'linea_base', 'meta',
        'rango_verde_min', 'rango_verde_max',
        'rango_amarillo_min', 'rango_amarillo_max',
        'rango_rojo_min', 'rango_rojo_max',
        'unidad_medida_id', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoIndicador::class,
            'dimension' => DimensionIndicador::class,
            'frecuencia' => FrecuenciaMedicion::class,
            'sentido' => SentidoIndicador::class,
            'linea_base' => 'decimal:4',
            'meta' => 'decimal:4',
            'orden' => 'integer',
        ];
    }

    public function mirNivel(): BelongsTo
    {
        return $this->belongsTo(MirNivel::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(IndicadorVariable::class)->orderBy('orden');
    }

    public function mediosVerificacion(): HasMany
    {
        return $this->hasMany(MedioVerificacion::class)->orderBy('orden');
    }

    public function cremaaValidacion(): HasOne
    {
        return $this->hasOne(CremaaValidacion::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CatalogoUnidadMedida::class, 'unidad_medida_id');
    }
}
```

**Step 2: Crear modelos restantes** (IndicadorVariable, MedioVerificacion, CremaaValidacion, CatalogoUnidadMedida)

Cada uno con sus fillable, casts y relaciones correspondientes.

**Step 3: Agregar relación en MirNivel**

```php
public function indicadores(): HasMany
{
    return $this->hasMany(Indicador::class)->orderBy('orden');
}
```

**Step 4: Commit**

```bash
git add app/Models/
git commit -m "feat(S4-T3): add Indicador, Variable, MedioVerificacion, Cremaa models"
```

---

### Task 4: Crear seeder de unidades de medida

**Files:**
- Create: `database/seeders/Mml/UnidadesMedidaSeeder.php`

**Step 1: Crear seeder con catálogo CONAC básico**

Incluir al menos: Porcentaje, Tasa, Índice, Promedio, Número, Razón, Proporción, Monto.

**Step 2: Commit**

```bash
git add database/seeders/Mml/UnidadesMedidaSeeder.php
git commit -m "feat(S4-T3): add CONAC units of measurement seeder"
```

---

### Task 5: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/IndicadorTest.php`

**Tests a cubrir:**
1. Crear indicador con todos los campos
2. Cast de enums (tipo, dimensión, frecuencia, sentido)
3. Relación indicador → mir_nivel
4. Relación indicador → variables
5. Relación indicador → medios de verificación
6. Relación indicador → cremaa_validacion
7. Cascade delete mir_nivel elimina indicadores
8. Catálogo de unidades de medida

**Step 1: Escribir tests**
**Step 2: Ejecutar tests**

```bash
sail artisan test --filter=IndicadorTest
```

**Step 3: Suite completo**

```bash
sail artisan test
```

**Step 4: Commit**

```bash
git add tests/Feature/Mml/IndicadorTest.php
git commit -m "feat(S4-T3): add indicator model tests"
```

---

### Task 6: Actualizar documentación

**Files:**
- Create: `docs/schema/indicadores.md`

---

## Criterios de Aceptación

- [ ] `indicadores` con todos los campos de ficha técnica
- [ ] Backed Enums para tipo, dimensión, frecuencia y sentido
- [ ] `indicador_variables` con campo `simbolo` (string 5)
- [ ] `catalogo_unidades_medida` con seeder CONAC
- [ ] `medios_verificacion` vinculados a indicador
- [ ] `cremaa_validaciones` con 6 booleans + observación
- [ ] Relaciones Eloquent completas
- [ ] Documentación del esquema actualizada
- [ ] Tests pasan
