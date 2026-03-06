# Plan de Implementación: S2-T1 — Migraciones y Modelos para Catálogos ODS

**Ticket:** S2-T1
**Tipo:** feat
**Rama:** `feat/S2-T1-catalogos-ods`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S0-T2 (Extensión pgvector habilitada)

---

## Contexto

Los Objetivos de Desarrollo Sostenible (ODS) son el nivel más alto de la cascada de planeación. Son catálogos inmutables que sirven como referencia para la Matriz de Alineación. Se requieren dos tablas: `ods_objetivos` (17 registros) y `ods_metas` (169 registros).

---

## Pre-requisitos

- Extensión `vector` habilitada en PostgreSQL (verificado en S0-T2)
- Archivo fuente de datos: `docs/data/ods-agenda-2030.md`

---

## Pasos

### 1. Crear migración: ODS Objetivos

```bash
sail artisan make:migration create_ods_objetivos_table
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
        Schema::create('ods_objetivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero')->unique(); // 1-17
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // Columna vectorial via raw SQL (Laravel no soporta nativo)
        DB::statement('ALTER TABLE ods_objetivos ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ods_objetivos');
    }
};
```

### 2. Crear migración: ODS Metas

```bash
sail artisan make:migration create_ods_metas_table
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
        Schema::create('ods_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ods_objetivo_id')->constrained()->cascadeOnDelete();
            $table->string('clave', 10)->unique(); // Ej: "1.1", "13.2"
            $table->text('descripcion');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE ods_metas ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ods_metas');
    }
};
```

### 3. Crear modelo: OdsObjetivo

```bash
sail artisan make:model OdsObjetivo
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OdsObjetivo extends Model
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

    public function metas(): HasMany
    {
        return $this->hasMany(OdsMeta::class, 'ods_objetivo_id');
    }
}
```

### 4. Crear modelo: OdsMeta

```bash
sail artisan make:model OdsMeta
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(OdsObjetivo::class, 'ods_objetivo_id');
    }
}
```

### 5. Crear archivo de datos fuente

Crear `docs/data/ods-agenda-2030.md` con estructura:

```markdown
# Objetivos de Desarrollo Sostenible

## ODS 1: Fin de la Pobreza

Poner fin a la pobreza en todas sus formas en todo el mundo.

### Metas

- **1.1** Para 2030, erradicar la pobreza extrema para todas las personas...
- **1.2** Para 2030, reducir al menos a la mitad la proporción de hombres...

## ODS 2: Hambre Cero

Poner fin al hambre, lograr la seguridad alimentaria...

### Metas

- **2.1** Para 2030, poner fin al hambre...
- **2.2** Para 2030, poner fin a todas las formas de malnutrición...
```

### 6. Crear Seeder con Parser Markdown

```bash
sail artisan make:seeder OdsSeeder
```

```php
<?php

namespace Database\Seeders;

use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class OdsSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('docs/data/ods-agenda-2030.md');

        if (!file_exists($path)) {
            $this->command->error("Archivo fuente no encontrado: {$path}");
            return;
        }

        $content = file_get_contents($path);
        $lines = explode("\n", $content);

        $currentObjetivo = null;
        $numeroObjetivo = null;

        foreach ($lines as $line) {
            // Detectar ODS: "## ODS 1: Fin de la Pobreza"
            if (preg_match('/^## ODS (\d+): (.+)/', $line, $matches)) {
                $numeroObjetivo = (int) $matches[1];
                $nombre = trim($matches[2]);

                $currentObjetivo = OdsObjetivo::updateOrCreate(
                    ['numero' => $numeroObjetivo],
                    ['nombre' => $nombre]
                );

                $this->command->info("ODS {$numeroObjetivo}: {$nombre}");
            }

            // Detectar descripción del ODS (línea siguiente al título)
            elseif ($currentObjetivo && !str_starts_with($line, '#') && !str_starts_with($line, '-') && trim($line)) {
                $currentObjetivo->update(['descripcion' => trim($line)]);
            }

            // Detectar Meta: "- **1.1** Descripción..."
            elseif ($currentObjetivo && preg_match('/^- \*\*(\d+\.\d+)\*\* (.+)/', $line, $matches)) {
                OdsMeta::updateOrCreate(
                    ['clave' => $matches[1]],
                    [
                        'ods_objetivo_id' => $currentObjetivo->id,
                        'descripcion' => trim($matches[2]),
                    ]
                );
            }
        }

        $this->command->info("✓ ODS cargados: " . OdsObjetivo::count() . " objetivos, " . OdsMeta::count() . " metas");
    }
}
```

### 7. Ejecutar migraciones y seeder

```bash
sail artisan migrate
sail artisan db:seed --class=OdsSeeder
```

### 8. Verificación

```bash
sail artisan tinker
```

```php
App\Models\OdsObjetivo::count();        // => 17
App\Models\OdsMeta::count();            // => 169
App\Models\OdsObjetivo::find(1)->metas->count(); // Ejemplo: metas del ODS 1
Schema::getColumnType('ods_objetivos', 'embedding'); // Verificar columna
exit
```

### 9. Documentar esquema

Crear `docs/schema/ods.md`:

```markdown
# Esquema: Catálogo ODS (Agenda 2030)

Catálogo inmutable de referencia para la Matriz de Alineación.

## Tabla: ods_objetivos

| Columna     | Tipo         | Notas                                       |
| ----------- | ------------ | ------------------------------------------- |
| id          | bigint       | PK                                          |
| numero      | tinyint      | 1-17, UNIQUE                                |
| nombre      | string       | Nombre corto                                |
| descripcion | text         | Descripción oficial                         |
| embedding   | vector(1536) | Para búsqueda semántica (null hasta S2-T10) |
| timestamps  | —            | created_at, updated_at                      |

## Tabla: ods_metas

| Columna         | Tipo         | Notas                                       |
| --------------- | ------------ | ------------------------------------------- |
| id              | bigint       | PK                                          |
| ods_objetivo_id | FK           | Cascade on delete                           |
| clave           | string(10)   | "1.1", "13.2", etc. UNIQUE                  |
| descripcion     | text         | Texto oficial de la meta                    |
| embedding       | vector(1536) | Para búsqueda semántica (null hasta S2-T10) |
| timestamps      | —            | created_at, updated_at                      |

## Relaciones

- `OdsObjetivo::metas()` → HasMany
- `OdsMeta::objetivo()` → BelongsTo
```

---

## Criterios de Aceptación

- [ ] Migraciones creadas con `up()` y `down()` completos
- [ ] Columna `embedding` creada via `DB::statement` como `vector(1536)`
- [ ] Modelos con `$fillable`, `casts()` y relaciones Eloquent definidas
- [ ] Seeder parsea archivo Markdown y carga datos con `updateOrCreate()`
- [ ] `sail artisan migrate` ejecuta sin errores
- [ ] `sail artisan migrate:rollback` revierte sin errores
- [ ] Verificación: 17 ODS y 169 metas cargados
- [ ] Documentación `docs/schema/ods.md` creada

---

## Notas

- Los embeddings se generarán en S2-T10 (pipeline de IA)
- Los índices HNSW se crearán en S2-T11 (optimización de búsqueda)
- El archivo Markdown debe contener los datos oficiales de ONU/Agenda 2030

```

---

## Veredicto

| Aspecto | Estado |
|---------|--------|
| Estructura de tablas | ✅ Correcta |
| Tipo de columna vectorial | ✅ Solucionado con raw SQL |
| Seeder con parser | ✅ Implementado |
| Relaciones Eloquent | ✅ Definidas |
| Documentación | ✅ Incluida |

**¿Procedemos con la ejecución de este plan mejorado?**
```
