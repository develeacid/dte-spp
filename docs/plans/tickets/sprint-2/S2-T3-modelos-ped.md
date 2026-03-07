# Plan: S2-T3 — Migraciones y Modelos para PED

**Ticket:** S2-T3
**Tipo:** feat
**Rama:** `feat/S2-T3-modelos-ped`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S0-T2, S2-T1, S2-T2 (Patrones validados)

---

## Contexto

El Plan Estatal de Desarrollo (PED) es la estructura central del sistema y el primer nivel **editable** de la cascada. Tiene una jerarquía de 6 niveles: **Plan → Ejes → Temas → Objetivos Estratégicos → Estrategias → Líneas de Acción**.

**Diferencias clave con ODS/PND:**

| Aspecto             | ODS/PND            | PED                    |
| ------------------- | ------------------ | ---------------------- |
| Naturaleza          | Catálogo inmutable | Editable por planeador |
| Niveles             | 2-3                | 6                      |
| Constraint especial | Ninguno            | Solo 1 plan activo     |
| CRUD                | Solo lectura       | Completo (S2-T6)       |

**Decisiones técnicas:**

- 6 migraciones independientes para rollback granular
- Constraint parcial `WHERE activo = true` en PostgreSQL para garantizar un solo plan activo
- Columnas `embedding vector(1536)` en niveles 2-6 (no en `ped_planes`)
- Claves como `string` para numeración compuesta ("1.2.3")

---

## Pre-requisitos

- S0-T2 completado (extensión pgvector habilitada)
- Patrones de migración validados en S2-T1 y S2-T2

---

## Pasos

### 1. Crear migración: PED Planes

```bash
sail artisan make:migration create_ped_planes_table
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
        Schema::create('ped_planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('nivel_gobierno')->default('estatal'); // estatal, municipal
            $table->unsignedSmallInteger('periodo_inicio'); // Ej: 2025
            $table->unsignedSmallInteger('periodo_fin');    // Ej: 2030
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });

        // Constraint parcial: solo un plan activo a la vez
        // PostgreSQL nativo - Laravel Blueprint no lo soporta
        DB::statement(
            'CREATE UNIQUE INDEX ped_planes_activo_unique ON ped_planes (activo) WHERE activo = true'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_planes');
    }
};
```

### 2. Crear migración: PED Ejes

```bash
sail artisan make:migration create_ped_ejes_table
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
        Schema::create('ped_ejes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_plan_id')->constrained()->cascadeOnDelete();
            $table->string('numero', 10); // "1", "2", "3"
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['ped_plan_id', 'numero']);
        });

        DB::statement('ALTER TABLE ped_ejes ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_ejes');
    }
};
```

### 3. Crear migración: PED Temas

```bash
sail artisan make:migration create_ped_temas_table
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
        Schema::create('ped_temas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_eje_id')->constrained()->cascadeOnDelete();
            $table->string('numero', 10); // "1.1", "1.2"
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['ped_eje_id', 'numero']);
        });

        DB::statement('ALTER TABLE ped_temas ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_temas');
    }
};
```

### 4. Crear migración: PED Objetivos Estratégicos

```bash
sail artisan make:migration create_ped_objetivos_estrategicos_table
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
        Schema::create('ped_objetivos_estrategicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_tema_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 20); // "1.1.1", "1.1.2"
            $table->text('descripcion');
            $table->timestamps();

            $table->unique(['ped_tema_id', 'clave']);
        });

        DB::statement('ALTER TABLE ped_objetivos_estrategicos ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_objetivos_estrategicos');
    }
};
```

### 5. Crear migración: PED Estrategias

```bash
sail artisan make:migration create_ped_estrategias_table
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
        Schema::create('ped_estrategias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_objetivo_estrategico_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 30); // "1.1.1.1", "1.1.1.2"
            $table->text('descripcion');
            $table->timestamps();

            $table->unique(['ped_objetivo_estrategico_id', 'clave']);
        });

        DB::statement('ALTER TABLE ped_estrategias ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_estrategias');
    }
};
```

### 6. Crear migración: PED Líneas de Acción

```bash
sail artisan make:migration create_ped_lineas_accion_table
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
        Schema::create('ped_lineas_accion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_estrategia_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 40); // "1.1.1.1.1", "1.1.1.1.2"
            $table->text('descripcion');
            $table->timestamps();

            $table->unique(['ped_estrategia_id', 'clave']);
        });

        DB::statement('ALTER TABLE ped_lineas_accion ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_lineas_accion');
    }
};
```

---

### 7. Crear modelo: PedPlan

```bash
sail artisan make:model PedPlan
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedPlan extends Model
{
    protected $fillable = [
        'nombre',
        'nivel_gobierno',
        'periodo_inicio',
        'periodo_fin',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'periodo_inicio' => 'integer',
            'periodo_fin' => 'integer',
            'activo' => 'boolean',
        ];
    }

    // ============================================
    // Relaciones
    // ============================================

    public function ejes(): HasMany
    {
        return $this->hasMany(PedEje::class, 'ped_plan_id');
    }

    // Relaciones through (acceso directo a niveles profundos)
    public function temas()
    {
        return $this->hasManyThrough(PedTema::class, PedEje::class);
    }

    public function objetivosEstrategicos()
    {
        return $this->hasManyThrough(
            PedObjetivoEstrategico::class,
            PedEje::class,
            'ped_plan_id',       // FK en ped_ejes
            'id',                // PK en ped_ejes
            'id',                // PK en ped_planes
            'id'                 // FK en ped_temas (se usa join intermedio)
        );
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    // ============================================
    // Métodos auxiliares
    // ============================================

    /**
     * Activa este plan y desactiva todos los demás.
     * Respeta el constraint único parcial de PostgreSQL.
     */
    public function activar(): void
    {
        // Desactivar todos los planes primero
        static::query()->update(['activo' => false]);

        // Activar este plan
        $this->update(['activo' => true]);
    }

    /**
     * Obtiene el plan activo actual.
     */
    public static function planActivo(): ?self
    {
        return static::where('activo', true)->first();
    }
}
```

### 8. Crear modelo: PedEje

```bash
sail artisan make:model PedEje
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedEje extends Model
{
    protected $fillable = [
        'ped_plan_id',
        'numero',
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_plan_id' => 'integer',
        ];
    }

    // ============================================
    // Relaciones
    // ============================================

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PedPlan::class, 'ped_plan_id');
    }

    public function temas(): HasMany
    {
        return $this->hasMany(PedTema::class, 'ped_eje_id');
    }

    public function objetivosEstrategicos()
    {
        return $this->hasManyThrough(PedObjetivoEstrategico::class, PedTema::class);
    }

    public function estrategias()
    {
        return $this->hasManyThrough(
            PedEstrategia::class,
            PedObjetivoEstrategico::class,
            'id', // FK en objetivos (intermedio)
            'ped_objetivo_estrategico_id', // FK en estrategias
            'id', // PK local
            'id'  // PK intermedio
        )->join('ped_temas', 'ped_objetivos_estrategicos.ped_tema_id', '=', 'ped_temas.id')
         ->where('ped_temas.ped_eje_id', $this->id);
    }

    public function lineasAccion()
    {
        // 4 niveles de profundidad - mejor usar queries separados
        return $this->hasManyThrough(PedLineaAccion::class, PedEstrategia::class);
    }
}
```

### 9. Crear modelo: PedTema

```bash
sail artisan make:model PedTema
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedTema extends Model
{
    protected $fillable = [
        'ped_eje_id',
        'numero',
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_eje_id' => 'integer',
        ];
    }

    public function eje(): BelongsTo
    {
        return $this->belongsTo(PedEje::class, 'ped_eje_id');
    }

    public function objetivosEstrategicos(): HasMany
    {
        return $this->hasMany(PedObjetivoEstrategico::class, 'ped_tema_id');
    }

    public function estrategias()
    {
        return $this->hasManyThrough(PedEstrategia::class, PedObjetivoEstrategico::class);
    }

    public function lineasAccion()
    {
        return $this->hasManyThrough(
            PedLineaAccion::class,
            PedEstrategia::class,
            'id', // FK en objetivos (intermedio 1)
            'ped_estrategia_id', // FK en líneas
            'id', // PK local
            'id'  // PK intermedio 2
        )->join('ped_objetivos_estrategicos', 'ped_estrategias.ped_objetivo_estrategico_id', '=', 'ped_objetivos_estrategicos.id')
         ->where('ped_objetivos_estrategicos.ped_tema_id', $this->id);
    }

    // Accessor para clave completa
    public function getClaveCompletaAttribute(): string
    {
        return $this->eje->numero . '.' . $this->numero;
    }
}
```

### 10. Crear modelo: PedObjetivoEstrategico

```bash
sail artisan make:model PedObjetivoEstrategico
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedObjetivoEstrategico extends Model
{
    protected $fillable = [
        'ped_tema_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_tema_id' => 'integer',
        ];
    }

    public function tema(): BelongsTo
    {
        return $this->belongsTo(PedTema::class, 'ped_tema_id');
    }

    public function eje()
    {
        return $this->tema->eje;
    }

    public function plan()
    {
        return $this->tema->eje->plan;
    }

    public function estrategias(): HasMany
    {
        return $this->hasMany(PedEstrategia::class, 'ped_objetivo_estrategico_id');
    }

    public function lineasAccion()
    {
        return $this->hasManyThrough(PedLineaAccion::class, PedEstrategia::class);
    }

    public function getClaveCompletaAttribute(): string
    {
        return $this->tema->clave_completa . '.' . $this->clave;
    }
}
```

### 11. Crear modelo: PedEstrategia

```bash
sail artisan make:model PedEstrategia
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedEstrategia extends Model
{
    protected $fillable = [
        'ped_objetivo_estrategico_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_objetivo_estrategico_id' => 'integer',
        ];
    }

    public function objetivoEstrategico(): BelongsTo
    {
        return $this->belongsTo(PedObjetivoEstrategico::class, 'ped_objetivo_estrategico_id');
    }

    public function tema()
    {
        return $this->objetivoEstrategico->tema;
    }

    public function eje()
    {
        return $this->objetivoEstrategico->tema->eje;
    }

    public function plan()
    {
        return $this->objetivoEstrategico->tema->eje->plan;
    }

    public function lineasAccion(): HasMany
    {
        return $this->hasMany(PedLineaAccion::class, 'ped_estrategia_id');
    }

    public function getClaveCompletaAttribute(): string
    {
        return $this->objetivoEstrategico->clave_completa . '.' . $this->clave;
    }
}
```

### 12. Crear modelo: PedLineaAccion

```bash
sail artisan make:model PedLineaAccion
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedLineaAccion extends Model
{
    protected $fillable = [
        'ped_estrategia_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ped_estrategia_id' => 'integer',
        ];
    }

    public function estrategia(): BelongsTo
    {
        return $this->belongsTo(PedEstrategia::class, 'ped_estrategia_id');
    }

    public function objetivoEstrategico()
    {
        return $this->estrategia->objetivoEstrategico;
    }

    public function tema()
    {
        return $this->estrategia->objetivoEstrategico->tema;
    }

    public function eje()
    {
        return $this->estrategia->objetivoEstrategico->tema->eje;
    }

    public function plan()
    {
        return $this->estrategia->objetivoEstrategico->tema->eje->plan;
    }

    public function getClaveCompletaAttribute(): string
    {
        return $this->estrategia->clave_completa . '.' . $this->clave;
    }
}
```

---

### 13. Crear Seeder con datos ficticios

```bash
sail artisan make:seeder PedSeeder
```

```php
<?php

namespace Database\Seeders;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Illuminate\Database\Seeder;

class PedSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando estructura PED de prueba...');

        // 1. Crear Plan (activo)
        $plan = PedPlan::updateOrCreate(
            ['nombre' => 'Plan Estatal de Desarrollo 2025-2030'],
            [
                'nivel_gobierno' => 'estatal',
                'periodo_inicio' => 2025,
                'periodo_fin' => 2030,
                'activo' => true,
            ]
        );
        $this->command->info("Plan: {$plan->nombre}");

        // 2. Crear 3 Ejes
        $ejesData = [
            ['numero' => '1', 'nombre' => 'Desarrollo Económico y Empleo'],
            ['numero' => '2', 'nombre' => 'Desarrollo Social y Humano'],
            ['numero' => '3', 'nombre' => 'Gobierno Eficiente y Transparente'],
        ];

        foreach ($ejesData as $ejeData) {
            $eje = PedEje::updateOrCreate(
                ['ped_plan_id' => $plan->id, 'numero' => $ejeData['numero']],
                ['nombre' => $ejeData['nombre']]
            );
            $this->command->info("  Eje {$eje->numero}: {$eje->nombre}");

            // 3. Crear 2 Temas por Eje
            for ($t = 1; $t <= 2; $t++) {
                $temaNumero = $eje->numero . '.' . $t;
                $tema = PedTema::updateOrCreate(
                    ['ped_eje_id' => $eje->id, 'numero' => (string) $t],
                    ['nombre' => "Tema {$temaNumero} del Eje {$eje->numero}"]
                );

                // 4. Crear 2 Objetivos Estratégicos por Tema
                for ($o = 1; $o <= 2; $o++) {
                    $objClave = (string) $o;
                    $objetivo = PedObjetivoEstrategico::updateOrCreate(
                        ['ped_tema_id' => $tema->id, 'clave' => $objClave],
                        ['descripcion' => "Objetivo Estratégico {$temaNumero}.{$o}"]
                    );

                    // 5. Crear 2 Estrategias por Objetivo
                    for ($e = 1; $e <= 2; $e++) {
                        $estClave = (string) $e;
                        $estrategia = PedEstrategia::updateOrCreate(
                            ['ped_objetivo_estrategico_id' => $objetivo->id, 'clave' => $estClave],
                            ['descripcion' => "Estrategia {$temaNumero}.{$o}.{$e}"]
                        );

                        // 6. Crear 2 Líneas de Acción por Estrategia
                        for ($l = 1; $l <= 2; $l++) {
                            $lineaClave = (string) $l;
                            PedLineaAccion::updateOrCreate(
                                ['ped_estrategia_id' => $estrategia->id, 'clave' => $lineaClave],
                                ['descripcion' => "Línea de Acción {$temaNumero}.{$o}.{$e}.{$l}"]
                            );
                        }
                    }
                }
            }
        }

        // Resumen
        $this->command->newLine();
        $this->command->table(
            ['Entidad', 'Cantidad'],
            [
                ['Planes', PedPlan::count()],
                ['Ejes', PedEje::count()],
                ['Temas', PedTema::count()],
                ['Objetivos Estratégicos', PedObjetivoEstrategico::count()],
                ['Estrategias', PedEstrategia::count()],
                ['Líneas de Acción', PedLineaAccion::count()],
            ]
        );
    }
}
```

---

### 14. Registrar Seeder

Editar `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([
        RolesAndPermissionsSeeder::class,
        DesarrolloSeeder::class,
        OdsSeeder::class,
        PndSeeder::class,
        PedSeeder::class,     // Nuevo
    ]);
}
```

---

### 15. Crear Test: Constraint de Plan Único Activo

```bash
sail artisan make:test PedPlanActivoConstraintTest
```

```php
<?php

namespace Tests\Feature;

use App\Models\PedPlan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedPlanActivoConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_pueden_existir_dos_planes_activos(): void
    {
        // Crear primer plan activo
        $plan1 = PedPlan::create([
            'nombre' => 'Plan 1',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        // Intentar crear segundo plan activo debe fallar
        $this->expectException(QueryException::class);

        PedPlan::create([
            'nombre' => 'Plan 2',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);
    }

    public function test_si_pueden_existir_multiples_planes_inactivos(): void
    {
        PedPlan::create([
            'nombre' => 'Plan Inactivo 1',
            'periodo_inicio' => 2019,
            'periodo_fin' => 2024,
            'activo' => false,
        ]);

        PedPlan::create([
            'nombre' => 'Plan Inactivo 2',
            'periodo_inicio' => 2013,
            'periodo_fin' => 2018,
            'activo' => false,
        ]);

        $this->assertEquals(2, PedPlan::where('activo', false)->count());
    }

    public function test_metodo_activar_desactiva_los_demas(): void
    {
        $plan1 = PedPlan::create([
            'nombre' => 'Plan 1',
            'periodo_inicio' => 2019,
            'periodo_fin' => 2024,
            'activo' => true,
        ]);

        $plan2 = PedPlan::create([
            'nombre' => 'Plan 2',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => false,
        ]);

        // Activar plan2
        $plan2->activar();

        // Verificar
        $this->assertFalse($plan1->fresh()->activo);
        $this->assertTrue($plan2->fresh()->activo);
        $this->assertEquals($plan2->id, PedPlan::planActivo()->id);
    }
}
```

---

### 16. Ejecutar y Verificar

```bash
sail artisan migrate
sail artisan db:seed --class=PedSeeder
sail artisan test --filter PedPlanActivoConstraintTest
```

Verificación en Tinker:

```php
use App\Models\PedPlan;

// Verificar plan activo
PedPlan::planActivo()->nombre;

// Verificar estructura completa
$plan = PedPlan::with('ejes.temas.objetivosEstrategicos.estrategias.lineasAccion')->first();
$plan->ejes->count();                                    // 3
$plan->ejes->first()->temas->count();                    // 2
$plan->ejes->first()->temas->first()->objetivosEstrategicos->count(); // 2

// Verificar clave completa
$linea = App\Models\PedLineaAccion::first();
$linea->clave_completa; // "1.1.1.1.1"
$linea->plan->nombre;   // "Plan Estatal de Desarrollo 2025-2030"
```

---

### 17. Documentar Esquema

Crear `docs/schema/ped.md`:

```markdown
# Esquema: Plan Estatal de Desarrollo (PED)

Estructura central editable del sistema. Jerarquía de 6 niveles.

## Estructura Jerárquica
```

PedPlan (Plan)
└── PedEje (Eje)
└── PedTema (Tema/Sub-eje)
└── PedObjetivoEstrategico (Objetivo)
└── PedEstrategia (Estrategia)
└── PedLineaAccion (Línea de Acción)

````

## Tablas

### ped_planes

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| nombre | string | Nombre del plan |
| nivel_gobierno | string | `estatal` o `municipal` |
| periodo_inicio | smallint | Año inicio (ej: 2025) |
| periodo_fin | smallint | Año fin (ej: 2030) |
| activo | boolean | Constraint: solo 1 activo |
| timestamps | — | — |

**Constraint parcial:** `CREATE UNIQUE INDEX ... WHERE activo = true`

### ped_ejes, ped_temas, ped_objetivos_estrategicos, ped_estrategias, ped_lineas_accion

Todas comparten estructura similar:
- `id` (PK)
- `[nivel_padre]_id` (FK con cascadeOnDelete)
- `numero` o `clave` (string)
- `nombre` o `descripcion` (text)
- `embedding` (vector 1536)
- `timestamps`

## Relaciones Eloquent

```php
// Navegación hacia abajo
$plan->ejes;
$eje->temas;
$tema->objetivosEstrategicos;
$objetivo->estrategias;
$estrategia->lineasAccion;

// Navegación hacia arriba
$lineaAccion->estrategia;
$lineaAccion->objetivoEstrategico;
$lineaAccion->tema;
$lineaAccion->eje;
$lineaAccion->plan;

// Accessor de clave completa
$lineaAccion->clave_completa; // "1.2.3.4.5"
````

## Métodos Auxiliares

```php
// Obtener plan activo
PedPlan::planActivo();

// Activar un plan (desactiva los demás)
$plan->activar();
```

```

---

## Criterios de Aceptación

- [ ] 6 migraciones creadas con `up()` y `down()` completos
- [ ] Constraint parcial único en `ped_planes` funciona (test pasa)
- [ ] Orden de rollback inverso sin errores de FK
- [ ] Columnas `embedding vector(1536)` en tablas ped_ejes a ped_lineas_accion
- [ ] 6 modelos con `$fillable`, `casts()`, relaciones completas
- [ ] Accessor `clave_completa` funciona en todos los niveles
- [ ] Método `activar()` desactiva otros planes automáticamente
- [ ] Método estático `planActivo()` retorna el plan activo
- [ ] Seeder crea estructura jerárquica completa
- [ ] Test de constraint pasa (`PedPlanActivoConstraintTest`)
- [ ] Documentación `docs/schema/ped.md` creada

---

## Notas

- Las relaciones `hasManyThrough` complejas (4+ niveles) se simplifican accediendo por niveles (`$linea->estrategia->objetivo->tema->eje->plan`)
- El seeder será reemplazado por el importador Markdown en S2-T9
- El CRUD completo se implementa en S2-T6

---
```
