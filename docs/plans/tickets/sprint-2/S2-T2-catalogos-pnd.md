# Plan: S2-T2 — Migraciones y Modelos para Catálogos PND

**Ticket:** S2-T2
**Tipo:** feat
**Rama:** `feat/S2-T2-catalogos-pnd`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S0-T2, S2-T1 (Patrón de migraciones validado)

---

## Contexto

El Plan Nacional de Desarrollo (PND) es el segundo nivel de la cascada de planeación. Tiene una estructura jerárquica de 3 niveles: **Ejes → Objetivos → Estrategias**. Como los ODS, es un catálogo inmutable que sirve como referencia para la Matriz de Alineación.

**Decisiones técnicas:**

- Misma estructura de `embedding vector(1536)` que ODS para consistencia
- Seeder con parser Markdown jerárquico (3 niveles de profundidad)
- Migraciones ejecutadas en orden de dependencia (ejes → objetivos → estrategias)

---

## Pre-requisitos

- S0-T2 completado (extensión pgvector habilitada)
- S2-T1 completado (patrón de `DB::statement` para columnas vectoriales validado)
- Archivo fuente: `docs/data/pnd-vigente.md`

---

## Pasos

### 1. Crear migración: PND Ejes

```bash
sail artisan make:migration create_pnd_ejes_table
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
        Schema::create('pnd_ejes', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pnd_ejes ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pnd_ejes');
    }
};
```

### 2. Crear migración: PND Objetivos

```bash
sail artisan make:migration create_pnd_objetivos_table
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
        Schema::create('pnd_objetivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnd_eje_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 20)->unique(); // Ej: "1.1", "2.3"
            $table->text('descripcion');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pnd_objetivos ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pnd_objetivos');
    }
};
```

### 3. Crear migración: PND Estrategias

```bash
sail artisan make:migration create_pnd_estrategias_table
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
        Schema::create('pnd_estrategias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pnd_objetivo_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 30)->unique(); // Ej: "1.1.1", "2.3.4"
            $table->text('descripcion');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pnd_estrategias ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pnd_estrategias');
    }
};
```

### 4. Crear modelo: PndEje

```bash
sail artisan make:model PndEje
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PndEje extends Model
{
    protected $fillable = [
        'numero',
        'nombre',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
        ];
    }

    public function objetivos(): HasMany
    {
        return $this->hasMany(PndObjetivo::class, 'pnd_eje_id');
    }

    public function estrategias(): HasMany
    {
        // Relación hasManyThrough: Eje → Objetivos → Estrategias
        return $this->hasManyThrough(PndEstrategia::class, PndObjetivo::class);
    }
}
```

### 5. Crear modelo: PndObjetivo

```bash
sail artisan make:model PndObjetivo
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function eje(): BelongsTo
    {
        return $this->belongsTo(PndEje::class, 'pnd_eje_id');
    }

    public function estrategias(): HasMany
    {
        return $this->hasMany(PndEstrategia::class, 'pnd_objetivo_id');
    }
}
```

### 6. Crear modelo: PndEstrategia

```bash
sail artisan make:model PndEstrategia
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PndEstrategia extends Model
{
    protected $fillable = [
        'pnd_objetivo_id',
        'clave',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'pnd_objetivo_id' => 'integer',
        ];
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(PndObjetivo::class, 'pnd_objetivo_id');
    }

    public function eje(): BelongsTo
    {
        // Relación hasOneThrough inversa (conveniencia)
        return $this->objetivo->eje();
    }
}
```

### 7. Crear archivo de datos fuente

Crear `docs/data/pnd-vigente.md` con estructura:

```markdown
# Plan Nacional de Desarrollo 2019-2024

## Eje 1: Política y Gobierno

Fortalecer el Estado Mexicano para que garantice el bienestar de la población.

### Objetivo 1.1

Garantizar los derechos de la población mediante el fortalecimiento del Estado de Derecho.

#### Estrategia 1.1.1

Fortalecer las instituciones de seguridad pública para garantizar la paz.

#### Estrategia 1.1.2

Impulsar la profesionalización de los cuerpos de seguridad.

### Objetivo 1.2

Garantizar la justicia para todas las personas.

#### Estrategia 1.2.1

Promover la impartición de justicia de manera pronta y expedita.

## Eje 2: Bienestar

Asegurar el ejercicio efectivo de los derechos sociales de toda la población.

### Objetivo 2.1

Garantizar el ejercicio efectivo de los derechos sociales.

#### Estrategia 2.1.1

Ampliar la cobertura de los servicios de salud.
```

### 8. Crear Seeder con Parser Jerárquico

```bash
sail artisan make:seeder PndSeeder
```

```php
<?php

namespace Database\Seeders;

use App\Models\PndEje;
use App\Models\PndEstrategia;
use App\Models\PndObjetivo;
use Illuminate\Database\Seeder;

class PndSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('docs/data/pnd-vigente.md');

        if (!file_exists($path)) {
            $this->command->error("Archivo fuente no encontrado: {$path}");
            return;
        }

        $content = file_get_contents($path);
        $lines = explode("\n", $content);

        $currentEje = null;
        $currentObjetivo = null;
        $ejeDescripcion = null;
        $ejeNumero = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            // Detectar Eje: "## Eje 1: Política y Gobierno"
            if (preg_match('/^## Eje (\d+): (.+)/', $line, $matches)) {
                $ejeNumero = (int) $matches[1];
                $currentEje = PndEje::updateOrCreate(
                    ['numero' => $ejeNumero],
                    ['nombre' => trim($matches[2])]
                );
                $currentObjetivo = null;
                $this->command->info("Eje {$ejeNumero}: {$matches[2]}");
            }

            // Descripción del Eje (línea no-heading después del título)
            elseif ($currentEje && !$currentObjetivo && !preg_match('/^#/', $line) && $line) {
                $currentEje->update(['descripcion' => $line]);
            }

            // Detectar Objetivo: "### Objetivo 1.1" o "### Objetivo 1.1: Título"
            elseif ($currentEje && preg_match('/^### Objetivo (\d+\.\d+)(?::\s*(.+))?/', $line, $matches)) {
                $currentObjetivo = PndObjetivo::updateOrCreate(
                    ['clave' => $matches[1]],
                    [
                        'pnd_eje_id' => $currentEje->id,
                        'descripcion' => isset($matches[2]) ? trim($matches[2]) : null,
                    ]
                );
                $this->command->info("  Objetivo {$matches[1]}");
            }

            // Descripción del Objetivo (línea siguiente si no tenía descripción en el título)
            elseif ($currentObjetivo && !$currentObjetivo->descripcion && !preg_match('/^#/', $line) && $line) {
                $currentObjetivo->update(['descripcion' => $line]);
            }

            // Detectar Estrategia: "#### Estrategia 1.1.1"
            elseif ($currentObjetivo && preg_match('/^#### Estrategia (\d+\.\d+\.\d+)(?::\s*(.+))?/', $line, $matches)) {
                PndEstrategia::updateOrCreate(
                    ['clave' => $matches[1]],
                    [
                        'pnd_objetivo_id' => $currentObjetivo->id,
                        'descripcion' => isset($matches[2]) ? trim($matches[2]) : null,
                    ]
                );
                $this->command->info("    Estrategia {$matches[1]}");
            }
        }

        $this->command->newLine();
        $this->command->table(
            ['Entidad', 'Cantidad'],
            [
                ['Ejes', PndEje::count()],
                ['Objetivos', PndObjetivo::count()],
                ['Estrategias', PndEstrategia::count()],
            ]
        );
    }
}
```

### 9. Registrar Seeder

Editar `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([
        RolesAndPermissionsSeeder::class,
        DesarrolloSeeder::class,
        OdsSeeder::class,    // Agregado en S2-T1
        PndSeeder::class,    // Nuevo
    ]);
}
```

### 10. Ejecutar y Verificar

```bash
sail artisan migrate
sail artisan db:seed --class=PndSeeder
```

Verificación en Tinker:

```php
App\Models\PndEje::count();
App\Models\PndObjetivo::count();
App\Models\PndEstrategia::count();

// Probar relaciones
$eje = App\Models\PndEje::first();
$eje->objetivos->count();
$eje->estrategias->count();

$objetivo = App\Models\PndObjetivo::first();
$objetivo->eje->nombre;
$objetivo->estrategias->count();
```

### 11. Documentar Esquema

Crear `docs/schema/pnd.md`:

```markdown
# Esquema: Plan Nacional de Desarrollo (PND)

Catálogo inmutable de referencia para la Matriz de Alineación.

## Estructura Jerárquica
```

PndEje (Ejes)
└── PndObjetivo (Objetivos)
└── PndEstrategia (Estrategias)

````

## Tabla: pnd_ejes

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| numero | tinyint | 1, 2, 3... UNIQUE |
| nombre | string | Nombre del eje |
| descripcion | text | Descripción general |
| embedding | vector(1536) | Búsqueda semántica (null hasta S2-T10) |
| timestamps | — | — |

## Tabla: pnd_objetivos

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| pnd_eje_id | FK | Cascade on delete |
| clave | string(20) | "1.1", "2.3" UNIQUE |
| descripcion | text | Texto del objetivo |
| embedding | vector(1536) | Búsqueda semántica |
| timestamps | — | — |

## Tabla: pnd_estrategias

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| pnd_objetivo_id | FK | Cascade on delete |
| clave | string(30) | "1.1.1", "2.3.4" UNIQUE |
| descripcion | text | Texto de la estrategia |
| embedding | vector(1536) | Búsqueda semántica |
| timestamps | — | — |

## Relaciones Eloquent

```php
// Eje
$eje->objetivos;      // HasMany
$eje->estrategias;    // HasManyThrough

// Objetivo
$objetivo->eje;           // BelongsTo
$objetivo->estrategias;   // HasMany

// Estrategia
$estrategia->objetivo;    // BelongsTo
````

```

---

## Criterios de Aceptación

- [ ] 3 migraciones creadas con `up()` y `down()` completos
- [ ] Orden de ejecución correcto: ejes → objetivos → estrategias
- [ ] Rollback elimina en orden inverso sin errores de FK
- [ ] Columnas `embedding vector(1536)` creadas en las 3 tablas via raw SQL
- [ ] 3 modelos con `$fillable`, `casts()` y relaciones Eloquent
- [ ] Relación `hasManyThrough` de `PndEje` a `PndEstrategia` funciona
- [ ] Seeder parsea Markdown jerárquico de 3 niveles con `updateOrCreate()`
- [ ] `sail artisan migrate:fresh --seed` ejecuta sin errores
- [ ] `sail artisan migrate:rollback --step=3` revierte las 3 tablas
- [ ] Documentación `docs/schema/pnd.md` creada

---

## Notas

- El archivo `docs/data/pnd-vigente.md` debe actualizarse con el PND del sexenio actual
- Los embeddings se generarán en S2-T10
- El patrón de parsing es extensible para otros documentos jerárquicos (PED, programas derivados)

---

## Diferencias con S2-T1 (ODS)

| Aspecto | ODS (S2-T1) | PND (S2-T2) |
|---------|-------------|-------------|
| Tablas | 2 | 3 |
| Niveles jerárquicos | 2 (objetivo → meta) | 3 (eje → objetivo → estrategia) |
| Relación compleja | No | `hasManyThrough` de Eje a Estrategias |
| Formato de clave | "1.1" | "1.1.1" (3 niveles) |

---

¿Aprobamos este plan para proceder con la ejecución?
```
