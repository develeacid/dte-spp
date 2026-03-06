# Plan: S2-T5 — Tablas Pivote para Matriz de Alineación

**Ticket:** S2-T5
**Tipo:** feat
**Rama:** `feat/S2-T5-matriz-alineacion`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T1, S2-T2, S2-T3, S2-T4 (Todas las tablas de la cascada)

---

## Contexto

La Matriz de Alineación es el conjunto de relaciones que permite vincular todos los niveles de la cascada de planeación, trazando la cadena completa desde una Línea de Acción del PED hasta los ODS de la Agenda 2030.

**Cadena de alineación:**

```
Línea de Acción PED
    ↓
Estrategia PED
    ↓
Objetivo Estratégico PED ──────────────┐
    ↓                                  │
    └──────→ Objetivo PND ──→ Meta ODS │
                                         │
Programa Derivado Objetivo ←────────────┘
        (vía Línea de Acción)
```

**3 Tablas Pivote (Muchos-a-Muchos):**

| Tabla Pivote                | Conecta                                   | Propósito                       |
| --------------------------- | ----------------------------------------- | ------------------------------- |
| `alineacion_ped_pnd`        | PedObjetivoEstratégico ↔ PndObjetivo      | Alineación PED con PND          |
| `alineacion_pnd_ods`        | PndObjetivo ↔ OdsMeta                     | Alineación PND con ODS          |
| `alineacion_linea_programa` | PedLineaAccion ↔ ProgramaDerivadoObjetivo | Alineación líneas con programas |

---

## Pre-requisitos

- S2-T1: Tablas `ods_objetivos`, `ods_metas`
- S2-T2: Tablas `pnd_ejes`, `pnd_objetivos`, `pnd_estrategias`
- S2-T3: Tablas PED completas (6 niveles)
- S2-T4: Tablas `programas_derivados`, `programas_derivados_objetivos`

---

## Pasos

### 1. Crear migración: Alineación PED ↔ PND

```bash
sail artisan make:migration create_alineacion_ped_pnd_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alineacion_ped_pnd', function (Blueprint $table) {
            $table->id();

            // FK a Objetivo Estratégico PED
            $table->foreignId('ped_objetivo_estrategico_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // FK a Objetivo PND
            $table->foreignId('pnd_objetivo_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamps();

            // Constraint único: una sola alineación por par
            $table->unique([
                'ped_objetivo_estrategico_id',
                'pnd_objetivo_id'
            ], 'alineacion_ped_pnd_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alineacion_ped_pnd');
    }
};
```

---

### 2. Crear migración: Alineación PND ↔ ODS

```bash
sail artisan make:migration create_alineacion_pnd_ods_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alineacion_pnd_ods', function (Blueprint $table) {
            $table->id();

            // FK a Objetivo PND
            $table->foreignId('pnd_objetivo_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // FK a Meta ODS
            $table->foreignId('ods_meta_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamps();

            // Constraint único
            $table->unique([
                'pnd_objetivo_id',
                'ods_meta_id'
            ], 'alineacion_pnd_ods_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alineacion_pnd_ods');
    }
};
```

---

### 3. Crear migración: Alineación Línea de Acción ↔ Programa Derivado

```bash
sail artisan make:migration create_alineacion_linea_programa_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alineacion_linea_programa', function (Blueprint $table) {
            $table->id();

            // FK a Línea de Acción PED
            $table->foreignId('ped_linea_accion_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // FK a Objetivo de Programa Derivado
            $table->foreignId('programa_derivado_objetivo_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->timestamps();

            // Constraint único
            $table->unique([
                'ped_linea_accion_id',
                'programa_derivado_objetivo_id'
            ], 'alineacion_linea_programa_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alineacion_linea_programa');
    }
};
```

---

### 4. Actualizar Modelo: PedObjetivoEstrategico

Editar `app/Models/PedObjetivoEstrategico.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    // ============================================
    // Relaciones hacia arriba (PED)
    // ============================================

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

    // ============================================
    // Relaciones hacia abajo (PED)
    // ============================================

    public function estrategias(): HasMany
    {
        return $this->hasMany(PedEstrategia::class, 'ped_objetivo_estrategico_id');
    }

    public function lineasAccion()
    {
        return $this->hasManyThrough(PedLineaAccion::class, PedEstrategia::class);
    }

    // ============================================
    // Relaciones transversales (Alineación)
    // ============================================

    /**
     * Objetivos del PND al que está alineado este objetivo PED.
     */
    public function pndObjetivos(): BelongsToMany
    {
        return $this->belongsToMany(
            PndObjetivo::class,
            'alineacion_ped_pnd',
            'ped_objetivo_estrategico_id',
            'pnd_objetivo_id'
        )->withTimestamps();
    }

    /**
     * Metas ODS heredadas a través de los objetivos PND alineados.
     */
    public function odsMetas(): BelongsToMany
    {
        return $this->belongsToMany(
            OdsMeta::class,
            'alineacion_ped_pnd_ods_view' // View en S2-T11, por ahora usar helper
        );
    }

    // ============================================
    // Accessors
    // ============================================

    public function getClaveCompletaAttribute(): string
    {
        return $this->tema->clave_completa . '.' . $this->clave;
    }
}
```

---

### 5. Actualizar Modelo: PndObjetivo

Editar `app/Models/PndObjetivo.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PndObjetivo extends Model
{
    protected $fillable = [
        'pnd_eje_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'pnd_eje_id' => 'integer',
        ];
    }

    // ============================================
    // Relaciones PND
    // ============================================

    public function eje(): BelongsTo
    {
        return $this->belongsTo(PndEje::class, 'pnd_eje_id');
    }

    public function estrategias(): HasMany
    {
        return $this->hasMany(PndEstrategia::class, 'pnd_objetivo_id');
    }

    // ============================================
    // Relaciones de Alineación
    // ============================================

    /**
     * Objetivos Estratégicos del PED alineados a este objetivo PND.
     */
    public function pedObjetivosEstrategicos(): BelongsToMany
    {
        return $this->belongsToMany(
            PedObjetivoEstrategico::class,
            'alineacion_ped_pnd',
            'pnd_objetivo_id',
            'ped_objetivo_estrategico_id'
        )->withTimestamps();
    }

    /**
     * Metas ODS alineadas a este objetivo PND.
     */
    public function odsMetas(): BelongsToMany
    {
        return $this->belongsToMany(
            OdsMeta::class,
            'alineacion_pnd_ods',
            'pnd_objetivo_id',
            'ods_meta_id'
        )->withTimestamps();
    }
}
```

---

### 6. Actualizar Modelo: OdsMeta

Editar `app/Models/OdsMeta.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OdsMeta extends Model
{
    protected $fillable = [
        'ods_objetivo_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'ods_objetivo_id' => 'integer',
        ];
    }

    // ============================================
    // Relaciones ODS
    // ============================================

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(OdsObjetivo::class, 'ods_objetivo_id');
    }

    // ============================================
    // Relaciones de Alineación (inversa)
    // ============================================

    /**
     * Objetivos del PND alineados a esta meta ODS.
     */
    public function pndObjetivos(): BelongsToMany
    {
        return $this->belongsToMany(
            PndObjetivo::class,
            'alineacion_pnd_ods',
            'ods_meta_id',
            'pnd_objetivo_id'
        )->withTimestamps();
    }

    /**
     * Objetivos Estratégicos del PED que contribuyen a esta meta ODS
     * (a través del PND).
     */
    public function pedObjetivosEstrategicos(): BelongsToMany
    {
        return $this->belongsToMany(
            PedObjetivoEstrategico::class,
            'alineacion_pnd_ods_ped_view' // Helper para navegación completa
        );
    }
}
```

---

### 7. Actualizar Modelo: PedLineaAccion

Editar `app/Models/PedLineaAccion.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    // ============================================
    // Relaciones hacia arriba (PED)
    // ============================================

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

    // ============================================
    // Relaciones de Alineación
    // ============================================

    /**
     * Objetivos de Programas Derivados alineados a esta Línea de Acción.
     */
    public function programasDerivadosObjetivos(): BelongsToMany
    {
        return $this->belongsToMany(
            ProgramaDerivadoObjetivo::class,
            'alineacion_linea_programa',
            'ped_linea_accion_id',
            'programa_derivado_objetivo_id'
        )->withTimestamps();
    }

    // ============================================
    // Accessors
    // ============================================

    public function getClaveCompletaAttribute(): string
    {
        return $this->estrategia->clave_completa . '.' . $this->clave;
    }

    // ============================================
    // Métodos de navegación de cadena completa
    // ============================================

    /**
     * Obtiene todos los ODS a los que contribuye esta Línea de Acción
     * navegando la cadena completa de alineación.
     */
    public function getOdsContribucion(): array
    {
        return $this->load([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas.objetivo'
        ])
        ->estrategia
        ->objetivoEstrategico
        ->pndObjetivos
        ->flatMap(fn ($pndObj) => $pndObj->odsMetas)
        ->unique('id')
        ->values()
        ->toArray();
    }
}
```

---

### 8. Actualizar Modelo: ProgramaDerivadoObjetivo

Editar `app/Models/ProgramaDerivadoObjetivo.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    // Relaciones de Alineación
    // ============================================

    /**
     * Líneas de Acción del PED alineadas a este objetivo.
     */
    public function lineasAccionPed(): BelongsToMany
    {
        return $this->belongsToMany(
            PedLineaAccion::class,
            'alineacion_linea_programa',
            'programa_derivado_objetivo_id',
            'ped_linea_accion_id'
        )->withTimestamps();
    }

    // ============================================
    // Accessors
    // ============================================

    public function getClaveCompletaAttribute(): string
    {
        return $this->programa->prefijoClave() . '.' . $this->clave;
    }
}
```

---

### 9. Crear Seeder: Alineaciones de Ejemplo

```bash
sail artisan make:seeder AlineacionSeeder
```

```php
<?php

namespace Database\Seeders;

use App\Models\OdsMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Database\Seeder;

class AlineacionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando alineaciones de ejemplo...');

        // ============================================
        // Alineación PED → PND → ODS
        // ============================================

        $pedObjetivo = PedObjetivoEstrategico::first();

        if (!$pedObjetivo) {
            $this->command->error('No hay objetivos estratégicos PED. Ejecuta PedSeeder primero.');
            return;
        }

        $pndObjetivo = PndObjetivo::first();

        if (!$pndObjetivo) {
            $this->command->error('No hay objetivos PND. Ejecuta PndSeeder primero.');
            return;
        }

        // Alinear PED → PND
        $pedObjetivo->pndObjetivos()->syncWithoutDetaching([$pndObjetivo->id]);
        $this->command->info("  PED {$pedObjetivo->clave_completa} → PND {$pndObjetivo->clave}");

        // Alinear PND → ODS (tomar 2 metas del ODS 1)
        $odsMetas = OdsMeta::where('clave', 'like', '1.%')->take(2)->get();

        foreach ($odsMetas as $odsMeta) {
            $pndObjetivo->odsMetas()->syncWithoutDetaching([$odsMeta->id]);
            $this->command->info("    PND {$pndObjetivo->clave} → ODS Meta {$odsMeta->clave}");
        }

        // ============================================
        // Alineación Línea de Acción ↔ Programa Derivado
        // ============================================

        $lineaAccion = PedLineaAccion::first();
        $progObjetivo = ProgramaDerivadoObjetivo::first();

        if ($lineaAccion && $progObjetivo) {
            $lineaAccion->programasDerivadosObjetivos()->syncWithoutDetaching([$progObjetivo->id]);
            $this->command->info("  Línea {$lineaAccion->clave_completa} → Programa {$progObjetivo->clave_completa}");
        }

        // ============================================
        // Resumen
        // ============================================

        $this->command->newLine();
        $this->command->table(
            ['Tipo de Alineación', 'Registros'],
            [
                ['PED ↔ PND', DB::table('alineacion_ped_pnd')->count()],
                ['PND ↔ ODS', DB::table('alineacion_pnd_ods')->count()],
                ['Línea ↔ Programa', DB::table('alineacion_linea_programa')->count()],
            ]
        );
    }
}
```

---

### 10. Registrar Seeder

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
        ProgramasDerivadosSeeder::class,
        AlineacionSeeder::class,     // Nuevo - debe ir al final
    ]);
}
```

---

### 11. Crear Test: Validación de Alineaciones

```bash
sail artisan make:test MatrizAlineacionTest
```

```php
<?php

namespace Tests\Feature;

use App\Models\OdsMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatrizAlineacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            \Database\Seeders\PedSeeder::class,
            \Database\Seeders\PndSeeder::class,
            \Database\Seeders\OdsSeeder::class,
            \Database\Seeders\ProgramasDerivadosSeeder::class,
        ]);
    }

    // ============================================
    // Tests de Unique Constraints
    // ============================================

    public function test_no_permite_duplicar_alineacion_ped_pnd(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        // Crear primera alineación
        $pedObj->pndObjetivos()->attach($pndObj->id);

        // Intentar duplicar debe fallar
        $this->expectException(QueryException::class);
        $pedObj->pndObjetivos()->attach($pndObj->id);
    }

    public function test_no_permite_duplicar_alineacion_pnd_ods(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        $pndObj->odsMetas()->attach($odsMeta->id);

        $this->expectException(QueryException::class);
        $pndObj->odsMetas()->attach($odsMeta->id);
    }

    public function test_no_permite_duplicar_alineacion_linea_programa(): void
    {
        $linea = PedLineaAccion::first();
        $progObj = ProgramaDerivadoObjetivo::first();

        $linea->programasDerivadosObjetivos()->attach($progObj->id);

        $this->expectException(QueryException::class);
        $linea->programasDerivadosObjetivos()->attach($progObj->id);
    }

    // ============================================
    // Tests de Navegación de Cadena
    // ============================================

    public function test_navegacion_ped_a_pnd(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        $pedObj->pndObjetivos()->attach($pndObj->id);

        $this->assertCount(1, $pedObj->fresh()->pndObjetivos);
        $this->assertEquals($pndObj->id, $pedObj->pndObjetivos->first()->id);
    }

    public function test_navegacion_pnd_a_ods(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        $pndObj->odsMetas()->attach($odsMeta->id);

        $this->assertCount(1, $pndObj->fresh()->odsMetas);
        $this->assertEquals($odsMeta->id, $pndObj->odsMetas->first()->id);
    }

    public function test_navegacion_inversa_ods_a_pnd(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        $pndObj->odsMetas()->attach($odsMeta->id);

        $this->assertCount(1, $odsMeta->fresh()->pndObjetivos);
        $this->assertEquals($pndObj->id, $odsMeta->pndObjetivos->first()->id);
    }

    public function test_cadena_completa_linea_accion_a_ods(): void
    {
        // Crear alineaciones
        $linea = PedLineaAccion::first();
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::where('clave', '1.1')->first();

        $pedObj->pndObjetivos()->attach($pndObj->id);
        $pndObj->odsMetas()->attach($odsMeta->id);

        // Navegar cadena completa con eager loading
        $linea = PedLineaAccion::with([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas'
        ])->find($linea->id);

        $cadena = $linea->estrategia->objetivoEstrategico->pndObjetivos->first()->odsMetas;

        $this->assertGreaterThanOrEqual(1, $cadena->count());
        $this->assertEquals('1.1', $cadena->first()->clave);
    }

    public function test_un_objetivo_ped_puede_alinearse_a_multiples_pnd(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObjetivos = PndObjetivo::take(2)->get();

        foreach ($pndObjetivos as $pndObj) {
            $pedObj->pndObjetivos()->syncWithoutDetaching([$pndObj->id]);
        }

        $this->assertCount(2, $pedObj->fresh()->pndObjetivos);
    }

    public function test_un_objetivo_pnd_puede_alinearse_a_multiples_ods(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMetas = OdsMeta::whereIn('clave', ['1.1', '1.2'])->get();

        foreach ($odsMetas as $meta) {
            $pndObj->odsMetas()->syncWithoutDetaching([$meta->id]);
        }

        $this->assertCount(2, $pndObj->fresh()->odsMetas);
    }

    // ============================================
    // Tests de Cascade Delete
    // ============================================

    public function test_eliminar_ped_objetivo_elimina_alineaciones(): void
    {
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        $pedObj->pndObjetivos()->attach($pndObj->id);

        $this->assertDatabaseHas('alineacion_ped_pnd', [
            'ped_objetivo_estrategico_id' => $pedObj->id,
        ]);

        $pedObj->delete();

        $this->assertDatabaseMissing('alineacion_ped_pnd', [
            'ped_objetivo_estrategico_id' => $pedObj->id,
        ]);
    }

    public function test_eliminar_pnd_objetivo_elimina_alineaciones(): void
    {
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        $pndObj->odsMetas()->attach($odsMeta->id);

        $this->assertDatabaseHas('alineacion_pnd_ods', [
            'pnd_objetivo_id' => $pndObj->id,
        ]);

        $pndObj->delete();

        $this->assertDatabaseMissing('alineacion_pnd_ods', [
            'pnd_objetivo_id' => $pndObj->id,
        ]);
    }
}
```

---

### 12. Ejecutar y Verificar

```bash
# Ejecutar migraciones
sail artisan migrate

# Ejecutar todos los seeders
sail artisan migrate:fresh --seed

# Ejecutar tests
sail artisan test --filter MatrizAlineacionTest

# Verificar rollback
sail artisan migrate:rollback --step=3
sail artisan migrate
```

Verificación en Tinker:

```php
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;

// Cargar una línea de acción con toda la cadena
$linea = PedLineaAccion::with([
    'estrategia.objetivoEstrategico.pndObjetivos.odsMetas.objetivo',
    'programasDerivadosObjetivos.programa'
])->first();

// Ver estructura
$linea->clave_completa;                                    // "1.1.1.1.1"
$linea->estrategia->objetivoEstrategico->pndObjetivos;     // Collection de PndObjetivo
$linea->estrategia->objetivoEstrategico->pndObjetivos->first()->odsMetas; // Collection de OdsMeta
$linea->programasDerivadosObjetivos;                       // Collection de ProgramaDerivadoObjetivo

// Navegación inversa
use App\Models\OdsMeta;
$meta = OdsMeta::with('pndObjetivos.pedObjetivosEstrategicos')->first();
$meta->pndObjetivos;  // A qué PND contribuye
$meta->pndObjetivos->first()->pedObjetivosEstrategicos; // A qué PED contribuye

// Verificar conteos
DB::table('alineacion_ped_pnd')->count();
DB::table('alineacion_pnd_ods')->count();
DB::table('alineacion_linea_programa')->count();
```

---

### 13. Documentar Esquema

Crear `docs/schema/matriz-alineacion.md`:

```markdown
# Esquema: Matriz de Alineación

Conjunto de tablas pivote que conectan todos los niveles de la cascada de planeación.

## Diagrama de Relaciones
```

┌─────────────────────────────────────────────────────────────────────────────┐
│ AGENDA 2030 │
│ ODS Objetivo → ODS Meta │
└─────────────────────────────────────────────────────────────────────────────┘
↑
│ alineacion_pnd_ods
┌─────────────────────────────────────────────────────────────────────────────┐
│ PND │
│ Eje → Objetivo → Estrategia │
└─────────────────────────────────────────────────────────────────────────────┘
↑
│ alineacion_ped_pnd
┌─────────────────────────────────────────────────────────────────────────────┐
│ PED │
│ Plan → Eje → Tema → Objetivo Estratégico → Estrategia → Línea Acción │
└─────────────────────────────────────────────────────────────────────────────┘
│
│ alineacion_linea_programa
↓
┌─────────────────────────────────────────────────────────────────────────────┐
│ PROGRAMAS DERIVADOS │
│ Programa → Objetivo │
└─────────────────────────────────────────────────────────────────────────────┘

````

## Tablas Pivote

### alineacion_ped_pnd

Conecta Objetivos Estratégicos del PED con Objetivos del PND.

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| ped_objetivo_estrategico_id | FK | Cascade delete |
| pnd_objetivo_id | FK | Cascade delete |
| timestamps | — | created_at, updated_at |

**Constraint:** `UNIQUE(ped_objetivo_estrategico_id, pnd_objetivo_id)`

### alineacion_pnd_ods

Conecta Objetivos del PND con Metas de los ODS.

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| pnd_objetivo_id | FK | Cascade delete |
| ods_meta_id | FK | Cascade delete |
| timestamps | — | created_at, updated_at |

**Constraint:** `UNIQUE(pnd_objetivo_id, ods_meta_id)`

### alineacion_linea_programa

Conecta Líneas de Acción del PED con Objetivos de Programas Derivados.

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| ped_linea_accion_id | FK | Cascade delete |
| programa_derivado_objetivo_id | FK | Cascade delete |
| timestamps | — | created_at, updated_at |

**Constraint:** `UNIQUE(ped_linea_accion_id, programa_derivado_objetivo_id)`

## Relaciones Eloquent

```php
// PED → PND
$pedObjetivo->pndObjetivos;           // BelongsToMany
$pndObjetivo->pedObjetivosEstrategicos; // BelongsToMany (inversa)

// PND → ODS
$pndObjetivo->odsMetas;               // BelongsToMany
$odsMeta->pndObjetivos;               // BelongsToMany (inversa)

// Línea de Acción ↔ Programa Derivado
$lineaAccion->programasDerivadosObjetivos;  // BelongsToMany
$progObjetivo->lineasAccionPed;             // BelongsToMany (inversa)
````

## Navegación de Cadena Completa

```php
// Desde Línea de Acción hasta ODS
$linea = PedLineaAccion::with([
    'estrategia.objetivoEstrategico.pndObjetivos.odsMetas.objetivo'
])->first();

$pedObjetivo = $linea->estrategia->objetivoEstrategico;
$pndObjetivos = $pedObjetivo->pndObjetivos;
$odsMetas = $pndObjetivos->flatMap->odsMetas;

// Desde ODS hasta PED (inversa)
$meta = OdsMeta::with([
    'pndObjetivos.pedObjetivosEstrategicos.tema.eje.plan'
])->first();

$pndObjetivos = $meta->pndObjetivos;
$pedObjetivos = $pndObjetivos->flatMap->pedObjetivosEstrategicos;
```

## Cardinalidad

| Relación                      | Cardinalidad | Notas                                            |
| ----------------------------- | ------------ | ------------------------------------------------ |
| PED Objetivo ↔ PND Objetivo   | N:N          | Un objetivo PED puede alinearse a múltiples PND  |
| PND Objetivo ↔ ODS Meta       | N:N          | Un objetivo PND puede contribuir a múltiples ODS |
| Línea Acción ↔ Prog. Derivado | N:N          | Una línea puede vincularse a múltiples programas |

## Uso en MIR (Sprint 4)

Cuando se crea una MIR:

1. El **Fin** se alinea a un Objetivo Estratégico PED
2. El sistema **hereda automáticamente** las alineaciones PND y ODS
3. Esto garantiza coherencia metodológica (PbR)

````

---

## Criterios de Aceptación

- [ ] 3 migraciones creadas con constraints UNIQUE compuestos
- [ ] FK con `cascadeOnDelete()` en ambas columnas de cada pivote
- [ ] Modelo `PedObjetivoEstrategico` con relación `pndObjetivos()`
- [ ] Modelo `PndObjetivo` con relaciones `pedObjetivosEstrategicos()` y `odsMetas()`
- [ ] Modelo `OdsMeta` con relación `pndObjetivos()` (inversa)
- [ ] Modelo `PedLineaAccion` con relación `programasDerivadosObjetivos()`
- [ ] Modelo `ProgramaDerivadoObjetivo` con relación `lineasAccionPed()` (inversa)
- [ ] Seeder crea alineaciones de ejemplo sin errores
- [ ] Test `MatrizAlineacionTest` pasa (12 assertions)
- [ ] Test de unique constraint valida que duplicados lanzan `QueryException`
- [ ] Test de cadena completa navega Línea → ODS correctamente
- [ ] Test de cascade delete verifica eliminación automática
- [ ] `sail artisan migrate:fresh --seed` ejecuta sin errores
- [ ] `sail artisan migrate:rollback --step=3` revierte sin errores
- [ ] Documentación `docs/schema/matriz-alineacion.md` creada

---

## Notas Importantes

### Eager Loading (Evitar N+1)

```php
// ❌ Malo - N+1 queries
$lineas = PedLineaAccion::all();
foreach ($lineas as $linea) {
    echo $linea->estrategia->objetivoEstrategico->pndObjetivos->count();
}

// ✅ Bueno - Eager loading
$lineas = PedLineaAccion::with([
    'estrategia.objetivoEstrategico.pndObjetivos.odsMetas'
])->get();
````

### syncWithoutDetaching vs attach

```php
// attach() - Lanza error si ya existe (violates unique constraint)
$pedObj->pndObjetivos()->attach($pndObj->id);

// syncWithoutDetaching() - Ignora si ya existe (idempotente)
$pedObj->pndObjetivos()->syncWithoutDetaching([$pndObj->id]);
```

### Relación con MIR (Sprint 4)

La Matriz de Alineación es fundamental para:

- **S4-T8**: Al asignar una Línea de Acción a un programa presupuestario, el sistema hereda automáticamente toda la cadena de alineación hacia arriba (PND → ODS)
- **Reportes transversales**: Agrupar indicadores por Eje PED, ODS, etc.

---
