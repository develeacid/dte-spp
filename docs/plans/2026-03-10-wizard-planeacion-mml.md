# Wizard de Planeación MML — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Convertir los 5 pasos sueltos de planeación MML en un wizard guiado de 6 pasos con stepper visual, navegación progresiva, nuevo Paso 5 (Embudo de Poblaciones), nuevo Paso 6 (Alineación Estratégica extraída del MIR), y CTA de finalización.

**Architecture:** Componente Blade `x-mml.stepper` compartido en todas las etapas. Nuevo modelo `PoblacionPrograma` con restricción CHECK en PostgreSQL. Nuevo componente `AlineacionEstrategica` que reutiliza la búsqueda semántica ya existente en `SemanticSearchService`. Renumeración de rutas: MIR pasa de `/etapa/5/mir` a `/etapa/7/mir`. Campo `planeacion_completada_at` en `programas_presupuestarios` marca la transición planeación → MIR.

**Tech Stack:** Laravel 12, Livewire 3, PostgreSQL (CHECK constraints), Alpine.js, Tailwind CSS, pgvector (embeddings existentes)

---

### Task 1: Migraciones — `planeacion_completada_at` y `poblaciones_programa`

**Files:**
- Create: `database/migrations/2026_03_10_060000_add_planeacion_completada_at_to_programas.php`
- Create: `database/migrations/2026_03_10_060001_create_poblaciones_programa_table.php`

**Step 1: Crear migración para `planeacion_completada_at`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programas_presupuestarios', function (Blueprint $table) {
            $table->timestamp('planeacion_completada_at')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('programas_presupuestarios', function (Blueprint $table) {
            $table->dropColumn('planeacion_completada_at');
        });
    }
};
```

**Step 2: Crear migración para `poblaciones_programa`**

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
        Schema::create('poblaciones_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas_presupuestarios')->cascadeOnDelete();
            $table->string('unidad_medida', 100);
            $table->unsignedInteger('referencia_cantidad');
            $table->text('referencia_fuente')->nullable();
            $table->unsignedInteger('potencial_cantidad');
            $table->text('potencial_fuente')->nullable();
            $table->unsignedInteger('objetivo_cantidad');
            $table->text('objetivo_justificacion')->nullable();
            $table->smallInteger('anio_ejercicio');
            $table->timestamps();

            $table->unique(['programa_id', 'anio_ejercicio']);
        });

        DB::statement("
            ALTER TABLE poblaciones_programa
            ADD CONSTRAINT chk_embudo_logico CHECK (
                objetivo_cantidad <= potencial_cantidad AND
                potencial_cantidad <= referencia_cantidad AND
                referencia_cantidad > 0 AND
                potencial_cantidad > 0 AND
                objetivo_cantidad > 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('poblaciones_programa');
    }
};
```

**Step 3: Ejecutar migraciones**

```bash
./vendor/bin/sail artisan migrate
```

Expected: ambas migraciones corren sin errores.

**Step 4: Agregar `planeacion_completada_at` al modelo `ProgramaPresupuestario`**

En `app/Models/ProgramaPresupuestario.php`, agregar al array `$fillable`:
```php
'planeacion_completada_at',
```

Y al array `$casts`:
```php
'planeacion_completada_at' => 'datetime',
```

**Commit:**
```
feat(db): add planeacion_completada_at and poblaciones_programa table

planeacion_completada_at marks when planning wizard is complete.
poblaciones_programa stores the population funnel (referencia >= potencial >= objetivo)
with a PostgreSQL CHECK constraint for mathematical validation.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 2: Modelo `PoblacionPrograma`

**Files:**
- Create: `app/Models/Mml/PoblacionPrograma.php`
- Modify: `app/Models/ProgramaPresupuestario.php` (agregar relación)

**Step 1: Crear el modelo**

```php
<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoblacionPrograma extends Model
{
    protected $table = 'poblaciones_programa';

    protected $fillable = [
        'programa_id',
        'unidad_medida',
        'referencia_cantidad',
        'referencia_fuente',
        'potencial_cantidad',
        'potencial_fuente',
        'objetivo_cantidad',
        'objetivo_justificacion',
        'anio_ejercicio',
    ];

    protected $casts = [
        'referencia_cantidad' => 'integer',
        'potencial_cantidad' => 'integer',
        'objetivo_cantidad' => 'integer',
        'anio_ejercicio' => 'integer',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_id');
    }
}
```

**Step 2: Agregar relación en `ProgramaPresupuestario`**

En `app/Models/ProgramaPresupuestario.php`, agregar método:

```php
use App\Models\Mml\PoblacionPrograma;

public function poblacion(): HasOne
{
    return $this->hasOne(PoblacionPrograma::class, 'programa_id');
}
```

Y agregar `use Illuminate\Database\Eloquent\Relations\HasOne;` si no existe.

**Commit:**
```
feat(model): add PoblacionPrograma model with programa relationship

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 3: Stepper visual `x-mml.stepper`

**Files:**
- Create: `resources/views/components/mml/stepper.blade.php`

**Contexto:** Basado en el patrón existente de `resources/views/livewire/mml/partials/stepper-importacion.blade.php`, pero para los 6 pasos del wizard de planeación. Calcula el estado de cada paso consultando la DB.

**Step 1: Crear el componente stepper**

```blade
@props([
    'programa',
    'pasoActual' => 1,
])

@php
    $pasos = [
        1 => 'Problema',
        2 => 'Árbol −',
        3 => 'Árbol +',
        4 => 'Alternativa',
        5 => 'Poblaciones',
        6 => 'Alineación',
    ];

    // Calcular estado de cada paso consultando la DB
    $arbolProblema = $programa->arboles()->where('tipo', 'problema')->first();
    $problemaGuardado = $arbolProblema
        ? $arbolProblema->nodos()->where('tipo_nodo', 'problema_central')->whereNotNull('descripcion')->exists()
        : false;

    $tieneCausas = $arbolProblema
        ? $arbolProblema->nodos()->where('tipo_nodo', 'causa_directa')->exists()
        : false;
    $tieneEfectos = $arbolProblema
        ? $arbolProblema->nodos()->where('tipo_nodo', 'efecto_directo')->exists()
        : false;

    $arbolObjetivos = $programa->arboles()->where('tipo', 'objetivos')->first();
    $sinPendientes = $arbolObjetivos
        ? ! $arbolObjetivos->nodos()->where('descripcion', 'like', '[Pendiente%')->exists()
        : false;

    $tieneAlternativaSeleccionada = $programa->alternativas()->where('seleccionada', true)->exists();

    $tienePoblacion = $programa->poblacion()->exists();

    $tieneAlineacion = $programa->mirNiveles()
        ->where('tipo_nivel', 'fin')
        ->whereNotNull('ped_objetivo_estrategico_id')
        ->exists();

    $completado = [
        1 => $problemaGuardado,
        2 => $tieneCausas && $tieneEfectos,
        3 => $arbolObjetivos && $sinPendientes,
        4 => $tieneAlternativaSeleccionada,
        5 => $tienePoblacion,
        6 => $tieneAlineacion,
    ];

    // Un paso es accesible si el anterior está completado (o es el paso 1)
    $accesible = [1 => true];
    for ($i = 2; $i <= 6; $i++) {
        $accesible[$i] = $completado[$i - 1];
    }

    $rutaPaso = [
        1 => route('mml.etapa1', $programa),
        2 => route('mml.etapa2', $programa),
        3 => route('mml.etapa3', $programa),
        4 => route('mml.etapa4', $programa),
        5 => route('mml.etapa5', $programa),
        6 => route('mml.etapa6', $programa),
    ];
@endphp

<nav class="mb-8" aria-label="Progreso de planeación">
    <ol class="flex items-center w-full">
        @foreach ($pasos as $numero => $label)
            @php
                $esCompletado = $completado[$numero];
                $esActual = $numero === $pasoActual;
                $esAccesible = $accesible[$numero];

                $circleClass = match (true) {
                    $esCompletado => 'bg-green-600 text-white',
                    $esActual     => 'bg-blue-600 text-white',
                    $esAccesible  => 'bg-gray-200 text-gray-500',
                    default       => 'bg-gray-100 text-gray-400',
                };

                $labelClass = match (true) {
                    $esCompletado => 'text-green-700 font-medium',
                    $esActual     => 'text-blue-700 font-semibold',
                    $esAccesible  => 'text-gray-500',
                    default       => 'text-gray-400',
                };

                $clickable = ($esCompletado || $esAccesible) && ! $esActual;
            @endphp

            <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                <div class="flex flex-col items-center">
                    @if ($clickable)
                        <a href="{{ $rutaPaso[$numero] }}" class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium {{ $circleClass }} hover:ring-2 hover:ring-offset-1 hover:ring-indigo-300 transition-shadow">
                    @else
                        <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium {{ $circleClass }}">
                    @endif
                        @if ($esCompletado)
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @elseif (! $esAccesible && ! $esActual)
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        @else
                            {{ $numero }}
                        @endif
                    @if ($clickable)
                        </a>
                    @else
                        </span>
                    @endif
                    <span class="mt-1 text-xs {{ $labelClass }} whitespace-nowrap">{{ $label }}</span>
                </div>

                @unless ($loop->last)
                    <div class="flex-1 mx-2 h-0.5 {{ $esCompletado ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
```

**Verificación:**
```bash
./vendor/bin/sail artisan view:cache 2>&1 | head -5
./vendor/bin/sail artisan view:clear
```

**Commit:**
```
feat(mml): add x-mml.stepper component for 6-step planning wizard

Visual stepper with completed/active/pending/locked states.
Calculates step completion from DB. Clickable navigation for
completed and accessible steps.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 4: Rutas — agregar etapa/5, etapa/6 y renumerar MIR a etapa/7

**Files:**
- Modify: `routes/web/mml.php`
- Modify: `app/Livewire/Mml/MirEditor.php` (título)

**Step 1: Actualizar rutas en `routes/web/mml.php`**

En el grupo de rutas del programa `{programa}`, agregar las nuevas rutas para etapa 5 y 6, y cambiar la ruta del MIR:

Cambiar:
```php
Route::get('/etapa/5/mir', MirEditor::class)->name('mml.mir');
```

A:
```php
Route::get('/etapa/5', EmbudoPoblaciones::class)->name('mml.etapa5');
Route::get('/etapa/6', AlineacionEstrategica::class)->name('mml.etapa6');
Route::get('/etapa/7/mir', MirEditor::class)->name('mml.mir');
```

Y agregar los imports al inicio del archivo:
```php
use App\Livewire\Mml\EmbudoPoblaciones;
use App\Livewire\Mml\AlineacionEstrategica;
```

**Step 2: Actualizar título del MirEditor**

En `app/Livewire/Mml/MirEditor.php`, cambiar:
```php
#[Title('Etapa 5 — Matriz de Indicadores para Resultados')]
```
A:
```php
#[Title('Etapa 7 — Matriz de Indicadores para Resultados')]
```

**Step 3: Actualizar referencias a `mml.mir` en vistas existentes**

Buscar y actualizar todas las referencias que dicen "Etapa 5" o usan `route('mml.mir')` que necesiten cambio de número en textos visibles. La ruta nombrada `mml.mir` se mantiene igual, así que los `route()` calls no necesitan cambio.

**Verificación:**
```bash
./vendor/bin/sail artisan route:list --name=mml
```
Debe mostrar las 7 etapas correctamente.

**Commit:**
```
feat(routes): add etapa/5 and etapa/6, renumber MIR to etapa/7

New routes for EmbudoPoblaciones (etapa/5) and
AlineacionEstrategica (etapa/6). MIR editor moves from
etapa/5/mir to etapa/7/mir. Named route mml.mir unchanged.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 5: Integrar stepper y botón "Siguiente" en etapas 1–4

**Files:**
- Modify: `resources/views/livewire/mml/definicion-problema.blade.php`
- Modify: `resources/views/livewire/mml/arbol-problema-builder.blade.php`
- Modify: `resources/views/livewire/mml/arbol-objetivos-builder.blade.php`
- Modify: `resources/views/livewire/mml/seleccion-alternativas.blade.php`

**Step 1: Agregar stepper a Etapa 1 (`definicion-problema.blade.php`)**

Después de la apertura de `<x-page.container ...>` y antes del bloque de mensajes `@if (session('success'))`, agregar:

```blade
<x-mml.stepper :programa="$programa" :paso-actual="1" />
```

En el `<x-slot:footer>`, agregar botón "Siguiente" después del botón existente:

```blade
<a href="{{ route('mml.etapa2', $programa) }}"
   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
    Siguiente: Árbol de Problemas →
</a>
```

**Step 2: Agregar stepper y botón "Siguiente" a Etapa 2 (`arbol-problema-builder.blade.php`)**

Después de `<x-page.container ...>`, agregar:
```blade
<x-mml.stepper :programa="$programa" :paso-actual="2" />
```

En el `<x-slot:footer>`, agregar después del botón "Etapa anterior":
```blade
<a href="{{ route('mml.etapa3', $programa) }}"
   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
    Siguiente: Árbol de Objetivos →
</a>
```

**Step 3: Agregar stepper y botón "Siguiente" a Etapa 3 (`arbol-objetivos-builder.blade.php`)**

Después de `<x-page.container ...>`, agregar:
```blade
<x-mml.stepper :programa="$programa" :paso-actual="3" />
```

En el `<x-slot:footer>`, agregar después del botón "Etapa anterior":
```blade
<a href="{{ route('mml.etapa4', $programa) }}"
   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
    Siguiente: Selección de Alternativa →
</a>
```

**Step 4: Agregar stepper y botón "Siguiente" a Etapa 4 (`seleccion-alternativas.blade.php`)**

Después de `<x-page.container ...>`, agregar:
```blade
<x-mml.stepper :programa="$programa" :paso-actual="4" />
```

En el `<x-slot:footer>`, agregar después del botón "Etapa anterior":
```blade
<a href="{{ route('mml.etapa5', $programa) }}"
   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
    Siguiente: Poblaciones →
</a>
```

**Verificación:**
```bash
./vendor/bin/sail artisan view:cache 2>&1 | head -5
./vendor/bin/sail artisan view:clear
```

**Commit:**
```
feat(mml): integrate stepper and forward navigation in etapas 1-4

Add x-mml.stepper to all existing etapa views.
Add "Siguiente" buttons in footer for progressive navigation.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 6: Paso 5 — Componente `EmbudoPoblaciones`

**Files:**
- Create: `app/Livewire/Mml/EmbudoPoblaciones.php`
- Create: `resources/views/livewire/mml/embudo-poblaciones.blade.php`

**Step 1: Crear el componente Livewire**

```php
<?php

namespace App\Livewire\Mml;

use App\Models\Mml\PoblacionPrograma;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 5 — Embudo de Poblaciones')]
class EmbudoPoblaciones extends Component
{
    public ProgramaPresupuestario $programa;

    #[Validate('required|string|max:100')]
    public string $unidad_medida = '';

    #[Validate('required|integer|min:1')]
    public ?int $referencia_cantidad = null;

    #[Validate('nullable|string|max:500')]
    public string $referencia_fuente = '';

    #[Validate('required|integer|min:1')]
    public ?int $potencial_cantidad = null;

    #[Validate('nullable|string|max:500')]
    public string $potencial_fuente = '';

    #[Validate('required|integer|min:1')]
    public ?int $objetivo_cantidad = null;

    #[Validate('nullable|string|max:1000')]
    public string $objetivo_justificacion = '';

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        $poblacion = $programa->poblacion;

        if ($poblacion) {
            $this->unidad_medida = $poblacion->unidad_medida;
            $this->referencia_cantidad = $poblacion->referencia_cantidad;
            $this->referencia_fuente = $poblacion->referencia_fuente ?? '';
            $this->potencial_cantidad = $poblacion->potencial_cantidad;
            $this->potencial_fuente = $poblacion->potencial_fuente ?? '';
            $this->objetivo_cantidad = $poblacion->objetivo_cantidad;
            $this->objetivo_justificacion = $poblacion->objetivo_justificacion ?? '';
        }
    }

    public function guardar(): void
    {
        $this->validate();

        // Validación del embudo: objetivo <= potencial <= referencia
        if ($this->potencial_cantidad > $this->referencia_cantidad) {
            $this->addError('potencial_cantidad',
                "La Población Potencial ({$this->potencial_cantidad}) no puede ser mayor a la de Referencia ({$this->referencia_cantidad}).");
            return;
        }

        if ($this->objetivo_cantidad > $this->potencial_cantidad) {
            $this->addError('objetivo_cantidad',
                "La Población Objetivo ({$this->objetivo_cantidad}) no puede ser mayor a la Potencial ({$this->potencial_cantidad}). Revisa las cifras o ajusta la Población Objetivo.");
            return;
        }

        PoblacionPrograma::updateOrCreate(
            [
                'programa_id' => $this->programa->id,
                'anio_ejercicio' => $this->programa->ejercicio_fiscal,
            ],
            [
                'unidad_medida' => $this->unidad_medida,
                'referencia_cantidad' => $this->referencia_cantidad,
                'referencia_fuente' => $this->referencia_fuente ?: null,
                'potencial_cantidad' => $this->potencial_cantidad,
                'potencial_fuente' => $this->potencial_fuente ?: null,
                'objetivo_cantidad' => $this->objetivo_cantidad,
                'objetivo_justificacion' => $this->objetivo_justificacion ?: null,
            ]
        );

        session()->flash('success', 'Poblaciones guardadas correctamente.');
    }

    public function render()
    {
        return view('livewire.mml.embudo-poblaciones');
    }
}
```

**Step 2: Crear la vista**

```blade
<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 5 — Embudo de Poblaciones"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Programas', 'url' => route('mml.programas')],
        ['label' => $programa->nombre],
        ['label' => 'Etapa 5 — Poblaciones'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="5" />

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Visualización tipo embudo --}}
        @if ($referencia_cantidad && $potencial_cantidad && $objetivo_cantidad)
            <div class="mb-6 mx-auto max-w-xl">
                @php
                    $maxW = 100;
                    $potW = $referencia_cantidad > 0 ? round(($potencial_cantidad / $referencia_cantidad) * 100) : 80;
                    $objW = $referencia_cantidad > 0 ? round(($objetivo_cantidad / $referencia_cantidad) * 100) : 60;
                @endphp

                <div class="space-y-1">
                    <div class="rounded-t-lg bg-blue-100 border border-blue-200 px-4 py-3 text-center" style="width: {{ $maxW }}%">
                        <p class="text-xs font-semibold uppercase text-blue-600">Referencia</p>
                        <p class="text-lg font-bold text-blue-800">{{ number_format($referencia_cantidad) }} {{ $unidad_medida }}</p>
                    </div>
                    <div class="bg-amber-100 border border-amber-200 px-4 py-3 text-center mx-auto" style="width: {{ $potW }}%">
                        <p class="text-xs font-semibold uppercase text-amber-600">Potencial</p>
                        <p class="text-lg font-bold text-amber-800">{{ number_format($potencial_cantidad) }} {{ $unidad_medida }}</p>
                    </div>
                    <div class="rounded-b-lg bg-green-100 border border-green-200 px-4 py-3 text-center mx-auto" style="width: {{ $objW }}%">
                        <p class="text-xs font-semibold uppercase text-green-600">Objetivo</p>
                        <p class="text-lg font-bold text-green-800">{{ number_format($objetivo_cantidad) }} {{ $unidad_medida }}</p>
                    </div>
                </div>

                <p class="mt-2 text-center text-xs text-gray-500">
                    La Población Atendida se calculará desde el Padrón de Beneficiarios durante la operación del programa.
                </p>
            </div>
        @endif

        {{-- Formulario --}}
        <x-forms.section
            title="Poblaciones del programa"
            description="Define las poblaciones de referencia, potencial y objetivo. Las cantidades deben cumplir: Objetivo ≤ Potencial ≤ Referencia."
        >
            <div class="space-y-6">
                {{-- Unidad de medida --}}
                <div>
                    <label for="unidad_medida" class="block text-sm font-medium text-gray-700">Unidad de medida</label>
                    <input
                        type="text"
                        wire:model="unidad_medida"
                        id="unidad_medida"
                        placeholder="Ej: Niños, Familias, MIPYMES"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                    @error('unidad_medida') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Población de Referencia --}}
                <div class="rounded-md border border-blue-200 bg-blue-50 p-4 space-y-3">
                    <h4 class="text-sm font-semibold text-blue-800">Población de Referencia</h4>
                    <p class="text-xs text-blue-600">Población total del ámbito geográfico del programa.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="referencia_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                            <input type="number" wire:model="referencia_cantidad" id="referencia_cantidad" min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('referencia_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="referencia_fuente" class="block text-sm font-medium text-gray-700">Fuente (opcional)</label>
                            <input type="text" wire:model="referencia_fuente" id="referencia_fuente"
                                placeholder="Ej: INEGI Censo 2020"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                {{-- Población Potencial --}}
                <div class="rounded-md border border-amber-200 bg-amber-50 p-4 space-y-3">
                    <h4 class="text-sm font-semibold text-amber-800">Población Potencial</h4>
                    <p class="text-xs text-amber-600">Población que presenta el problema o necesidad que el programa busca atender.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="potencial_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                            <input type="number" wire:model="potencial_cantidad" id="potencial_cantidad" min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('potencial_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="potencial_fuente" class="block text-sm font-medium text-gray-700">Fuente (opcional)</label>
                            <input type="text" wire:model="potencial_fuente" id="potencial_fuente"
                                placeholder="Ej: CONEVAL 2023"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                {{-- Población Objetivo --}}
                <div class="rounded-md border border-green-200 bg-green-50 p-4 space-y-3">
                    <h4 class="text-sm font-semibold text-green-800">Población Objetivo</h4>
                    <p class="text-xs text-green-600">Subconjunto de la población potencial que el programa atenderá en este ejercicio fiscal (limitado por capacidad y presupuesto).</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="objetivo_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                            <input type="number" wire:model="objetivo_cantidad" id="objetivo_cantidad" min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('objetivo_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="objetivo_justificacion" class="block text-sm font-medium text-gray-700">Justificación (opcional)</label>
                            <input type="text" wire:model="objetivo_justificacion" id="objetivo_justificacion"
                                placeholder="¿Por qué este recorte respecto a la potencial?"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                {{-- Botón guardar --}}
                <div class="flex justify-end">
                    <button
                        wire:click="guardar"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="guardar">Guardar poblaciones</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </x-forms.section>

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa4', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
            <a href="{{ route('mml.etapa6', $programa) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Siguiente: Alineación Estratégica →
            </a>
        </x-slot:footer>
    </x-page.container>
</div>
```

**Verificación:**
```bash
./vendor/bin/sail artisan view:cache 2>&1 | head -5
./vendor/bin/sail artisan view:clear
```

**Commit:**
```
feat(mml): add EmbudoPoblaciones component (Paso 5)

New Livewire component with population funnel form (referencia >= potencial >= objetivo),
visual funnel chart, and Livewire validation matching the PostgreSQL CHECK constraint.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 7: Paso 6 — Componente `AlineacionEstrategica`

**Files:**
- Create: `app/Livewire/Mml/AlineacionEstrategica.php`
- Create: `resources/views/livewire/mml/alineacion-estrategica.blade.php`

**Contexto:** Este paso extrae la funcionalidad de alineación que hoy vive en el MIR Editor (búsqueda semántica de PED) y la presenta como formulario independiente. Usa la misma `SemanticSearchService` que ya usa `MirEditor::buscarAlineacion()`. También agrega selección de ODS y Anexos Transversales.

**Step 1: Crear el componente Livewire**

```php
<?php

namespace App\Livewire\Mml;

use App\Models\Evaluation\AnexoTransversal;
use App\Models\Mml\MirNivel;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedTema;
use App\Models\ProgramaPresupuestario;
use App\Services\Embeddings\SemanticSearchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 6 — Alineación Estratégica')]
class AlineacionEstrategica extends Component
{
    public ProgramaPresupuestario $programa;

    // PED selects dependientes
    public ?int $ejeId = null;
    public ?int $temaId = null;
    public ?int $objetivoEstrategicoId = null;

    // ODS
    public array $odsSeleccionados = [];

    // Anexos transversales
    public array $anexosSeleccionados = [];

    // IA suggestions
    public array $sugerenciasIa = [];
    public bool $buscandoConIa = false;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        // Cargar alineación existente desde el nivel FIN de la MIR
        $fin = $programa->mirNiveles()->where('tipo_nivel', 'fin')->first();

        if ($fin?->ped_objetivo_estrategico_id) {
            $objetivo = PedObjetivoEstrategico::with('tema.eje')->find($fin->ped_objetivo_estrategico_id);
            if ($objetivo) {
                $this->ejeId = $objetivo->tema->eje->id;
                $this->temaId = $objetivo->tema->id;
                $this->objetivoEstrategicoId = $objetivo->id;
            }
        }
    }

    public function updatedEjeId(): void
    {
        $this->temaId = null;
        $this->objetivoEstrategicoId = null;
    }

    public function updatedTemaId(): void
    {
        $this->objetivoEstrategicoId = null;
    }

    public function buscarConIa(): void
    {
        $this->buscandoConIa = true;
        $this->sugerenciasIa = [];

        try {
            // Obtener el problema central del programa
            $arbol = $this->programa->arboles()->where('tipo', 'problema')->first();
            $problema = $arbol?->nodos()->where('tipo_nodo', 'problema_central')->first();

            if (! $problema?->descripcion) {
                session()->flash('error', 'No se encontró el problema central para buscar alineación.');
                return;
            }

            $search = app(SemanticSearchService::class);
            $resultados = $search->search($problema->descripcion, PedObjetivoEstrategico::class, 5);

            $this->sugerenciasIa = collect($resultados)->map(fn ($r) => [
                'id' => $r['model']->id,
                'descripcion' => $r['model']->descripcion,
                'clave' => $r['model']->clave_completa,
                'tema' => $r['model']->tema?->nombre ?? '',
                'eje' => $r['model']->tema?->eje?->nombre ?? '',
                'score' => round($r['score'] * 100),
            ])->toArray();
        } catch (\Throwable $e) {
            session()->flash('error', 'Error al buscar alineación con IA: ' . $e->getMessage());
        } finally {
            $this->buscandoConIa = false;
        }
    }

    public function seleccionarSugerencia(int $objetivoId): void
    {
        $objetivo = PedObjetivoEstrategico::with('tema.eje')->find($objetivoId);

        if ($objetivo) {
            $this->ejeId = $objetivo->tema->eje->id;
            $this->temaId = $objetivo->tema->id;
            $this->objetivoEstrategicoId = $objetivo->id;
            $this->sugerenciasIa = [];
        }
    }

    public function guardar(): void
    {
        if (! $this->objetivoEstrategicoId) {
            $this->addError('objetivoEstrategicoId', 'Selecciona al menos un Objetivo Estratégico del PED.');
            return;
        }

        // Guardar alineación en el nivel FIN de la MIR (crear si no existe)
        $fin = $this->programa->mirNiveles()->where('tipo_nivel', 'fin')->first();

        if (! $fin) {
            $fin = $this->programa->mirNiveles()->create([
                'tipo_nivel' => 'fin',
                'orden' => 0,
            ]);
        }

        $fin->update([
            'ped_objetivo_estrategico_id' => $this->objetivoEstrategicoId,
        ]);

        session()->flash('success', 'Alineación estratégica guardada correctamente.');
    }

    public function render()
    {
        $ejes = PedEje::orderBy('numero')->get();
        $temas = $this->ejeId ? PedTema::where('ped_eje_id', $this->ejeId)->orderBy('numero')->get() : collect();
        $objetivos = $this->temaId ? PedObjetivoEstrategico::where('ped_tema_id', $this->temaId)->orderBy('clave')->get() : collect();
        $odsObjetivos = OdsObjetivo::orderBy('numero')->get();
        $anexos = AnexoTransversal::activos()->get();

        return view('livewire.mml.alineacion-estrategica', compact(
            'ejes', 'temas', 'objetivos', 'odsObjetivos', 'anexos'
        ));
    }
}
```

**Step 2: Crear la vista**

```blade
<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 6 — Alineación Estratégica"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Programas', 'url' => route('mml.programas')],
        ['label' => $programa->nombre],
        ['label' => 'Etapa 6 — Alineación'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="6" />

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Plan Estatal de Desarrollo --}}
        <x-forms.section
            title="Plan Estatal de Desarrollo (PED)"
            description="Selecciona el Eje, Tema y Objetivo Estratégico al que contribuye tu programa. Puedes usar la búsqueda con IA para obtener sugerencias basadas en tu problema central."
        >
            <div class="space-y-4">
                {{-- Búsqueda con IA --}}
                <div class="flex items-center gap-3">
                    <button
                        wire:click="buscarConIa"
                        wire:loading.attr="disabled"
                        wire:target="buscarConIa"
                        type="button"
                        class="inline-flex items-center rounded-md bg-purple-50 px-3 py-2 text-sm font-semibold text-purple-700 ring-1 ring-inset ring-purple-300 hover:bg-purple-100 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="buscarConIa">Sugerir alineación con IA</span>
                        <span wire:loading wire:target="buscarConIa">Buscando...</span>
                    </button>
                    <span class="text-xs text-gray-500">Busca el Objetivo Estratégico más afín a tu problema central</span>
                </div>

                @if (count($sugerenciasIa) > 0)
                    <div class="rounded-md border border-indigo-200 bg-indigo-50 p-3 space-y-2">
                        <p class="text-xs font-medium text-indigo-700">Sugerencias de alineación:</p>
                        @foreach ($sugerenciasIa as $sug)
                            <div class="flex items-center justify-between gap-2 rounded bg-white p-2 text-sm">
                                <div>
                                    <span class="font-medium text-gray-900">{{ $sug['clave'] }}</span>
                                    <span class="ml-1 text-gray-600">{{ $sug['descripcion'] }}</span>
                                    <span class="ml-1 text-xs text-gray-400">({{ $sug['score'] }}%)</span>
                                    <p class="text-xs text-gray-500">{{ $sug['eje'] }} → {{ $sug['tema'] }}</p>
                                </div>
                                <button
                                    wire:click="seleccionarSugerencia({{ $sug['id'] }})"
                                    class="shrink-0 rounded bg-indigo-600 px-3 py-1 text-xs text-white hover:bg-indigo-700"
                                >
                                    Seleccionar
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Selects dependientes --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="ejeId" class="block text-sm font-medium text-gray-700">Eje estratégico</label>
                        <select wire:model.live="ejeId" id="ejeId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Seleccionar eje...</option>
                            @foreach ($ejes as $eje)
                                <option value="{{ $eje->id }}">{{ $eje->numero }}. {{ $eje->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="temaId" class="block text-sm font-medium text-gray-700">Tema</label>
                        <select wire:model.live="temaId" id="temaId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            @if(! $ejeId) disabled @endif>
                            <option value="">Seleccionar tema...</option>
                            @foreach ($temas as $tema)
                                <option value="{{ $tema->id }}">{{ $tema->clave_completa }} {{ $tema->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="objetivoEstrategicoId" class="block text-sm font-medium text-gray-700">Objetivo estratégico</label>
                        <select wire:model="objetivoEstrategicoId" id="objetivoEstrategicoId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            @if(! $temaId) disabled @endif>
                            <option value="">Seleccionar objetivo...</option>
                            @foreach ($objetivos as $obj)
                                <option value="{{ $obj->id }}">{{ $obj->clave_completa }} {{ $obj->descripcion }}</option>
                            @endforeach
                        </select>
                        @error('objetivoEstrategicoId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Botón guardar --}}
                <div class="flex justify-end">
                    <button
                        wire:click="guardar"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="guardar">Guardar alineación</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </x-forms.section>

        {{-- CTA Finalizar Planeación --}}
        @php
            $todosCompletos = $programa->poblacion()->exists()
                && $programa->mirNiveles()->where('tipo_nivel', 'fin')->whereNotNull('ped_objetivo_estrategico_id')->exists();
        @endphp

        @if ($todosCompletos && ! $programa->planeacion_completada_at)
            <div class="mt-6 rounded-lg border-2 border-green-300 bg-green-50 p-6 text-center">
                <p class="text-sm font-semibold text-green-800 mb-2">Planeación completa (6/6 pasos)</p>
                <p class="text-xs text-green-700 mb-4">
                    Esto generará automáticamente la estructura Fin/Propósito/Componentes de tu MIR
                    a partir de la alternativa seleccionada en el Paso 4.
                </p>
                <button
                    wire:click="finalizarPlaneacion"
                    wire:loading.attr="disabled"
                    wire:confirm="¿Finalizar la planeación y generar la MIR? Podrás seguir editando la MIR después."
                    type="button"
                    class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="finalizarPlaneacion">Finalizar Planeación y Crear MIR →</span>
                    <span wire:loading wire:target="finalizarPlaneacion">Generando MIR...</span>
                </button>
            </div>
        @elseif ($programa->planeacion_completada_at)
            <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 text-center">
                <p class="text-sm text-gray-600">
                    Planeación finalizada el {{ $programa->planeacion_completada_at->format('d/m/Y H:i') }}.
                    <a href="{{ route('mml.mir', $programa) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                        Ir a la MIR →
                    </a>
                </p>
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa5', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
            @if ($programa->planeacion_completada_at)
                <a href="{{ route('mml.mir', $programa) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                    Ir a la MIR →
                </a>
            @endif
        </x-slot:footer>
    </x-page.container>
</div>
```

**Step 3: Agregar método `finalizarPlaneacion` al componente**

En `app/Livewire/Mml/AlineacionEstrategica.php`, agregar al final de la clase (antes del cierre `}`):

```php
public function finalizarPlaneacion(): void
{
    // Verificar que todos los pasos estén completos
    if (! $this->objetivoEstrategicoId) {
        session()->flash('error', 'Completa la alineación estratégica antes de finalizar.');
        return;
    }

    // Marcar planeación como completada
    $this->programa->update(['planeacion_completada_at' => now()]);

    // Pre-llenar MIR desde el EAP
    app(\App\Services\Mml\MirPrellenadoService::class)
        ->prellenarDesdeEAP($this->programa);

    // Redirigir a la MIR
    $this->redirect(route('mml.mir', $this->programa));
}
```

**Verificación:**
```bash
./vendor/bin/sail artisan view:cache 2>&1 | head -5
./vendor/bin/sail artisan view:clear
```

**Commit:**
```
feat(mml): add AlineacionEstrategica component (Paso 6) with CTA

New Livewire component with PED dependent selects (Eje > Tema > Objetivo),
semantic search via IA, and "Finalizar Planeación y Crear MIR" CTA that
marks planeacion_completada_at and calls MirPrellenadoService.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 8: Tests

**Files:**
- Create: `tests/Unit/PoblacionProgramaModelTest.php`
- Create: `tests/Feature/EmbudoPoblacionesTest.php`
- Create: `tests/Feature/AlineacionEstrategicaTest.php`
- Create: `tests/Feature/Components/StepperComponentTest.php`

**Step 1: Test unitario del modelo PoblacionPrograma**

```php
<?php

namespace Tests\Unit;

use App\Models\Mml\PoblacionPrograma;
use App\Models\ProgramaPresupuestario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoblacionProgramaModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_crear_poblacion_programa(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $poblacion = PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Niños',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 20000,
            'objetivo_cantidad' => 5000,
            'anio_ejercicio' => 2026,
        ]);

        $this->assertDatabaseHas('poblaciones_programa', [
            'programa_id' => $programa->id,
            'unidad_medida' => 'Niños',
            'referencia_cantidad' => 100000,
        ]);
    }

    /** @test */
    public function check_constraint_rechaza_objetivo_mayor_que_potencial(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Familias',
            'referencia_cantidad' => 10000,
            'potencial_cantidad' => 5000,
            'objetivo_cantidad' => 6000, // > potencial → viola CHECK
            'anio_ejercicio' => 2026,
        ]);
    }

    /** @test */
    public function check_constraint_rechaza_potencial_mayor_que_referencia(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Familias',
            'referencia_cantidad' => 5000,
            'potencial_cantidad' => 6000, // > referencia → viola CHECK
            'objetivo_cantidad' => 3000,
            'anio_ejercicio' => 2026,
        ]);
    }

    /** @test */
    public function check_constraint_rechaza_cantidades_cero(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Familias',
            'referencia_cantidad' => 10000,
            'potencial_cantidad' => 5000,
            'objetivo_cantidad' => 0, // viola CHECK > 0
            'anio_ejercicio' => 2026,
        ]);
    }

    /** @test */
    public function unique_constraint_por_programa_y_anio(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Niños',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 20000,
            'objetivo_cantidad' => 5000,
            'anio_ejercicio' => 2026,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'Familias',
            'referencia_cantidad' => 50000,
            'potencial_cantidad' => 10000,
            'objetivo_cantidad' => 3000,
            'anio_ejercicio' => 2026, // mismo programa + año → viola UNIQUE
        ]);
    }

    /** @test */
    public function relacion_programa(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $poblacion = PoblacionPrograma::create([
            'programa_id' => $programa->id,
            'unidad_medida' => 'MIPYMES',
            'referencia_cantidad' => 50000,
            'potencial_cantidad' => 10000,
            'objetivo_cantidad' => 2000,
            'anio_ejercicio' => 2026,
        ]);

        $this->assertEquals($programa->id, $poblacion->programa->id);
        $this->assertNotNull($programa->fresh()->poblacion);
    }
}
```

**Step 2: Test feature del EmbudoPoblaciones**

```php
<?php

namespace Tests\Feature;

use App\Livewire\Mml\EmbudoPoblaciones;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmbudoPoblacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    /** @test */
    public function puede_ver_pagina_embudo(): void
    {
        $this->actingAs($this->user)
            ->get(route('mml.etapa5', $this->programa))
            ->assertOk()
            ->assertSeeLivewire(EmbudoPoblaciones::class);
    }

    /** @test */
    public function puede_guardar_poblaciones_validas(): void
    {
        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->set('unidad_medida', 'Niños')
            ->set('referencia_cantidad', 100000)
            ->set('potencial_cantidad', 20000)
            ->set('objetivo_cantidad', 5000)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('poblaciones_programa', [
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Niños',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 20000,
            'objetivo_cantidad' => 5000,
        ]);
    }

    /** @test */
    public function valida_embudo_objetivo_menor_potencial(): void
    {
        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->set('unidad_medida', 'Familias')
            ->set('referencia_cantidad', 10000)
            ->set('potencial_cantidad', 5000)
            ->set('objetivo_cantidad', 6000) // > potencial
            ->call('guardar')
            ->assertHasErrors('objetivo_cantidad');
    }

    /** @test */
    public function valida_embudo_potencial_menor_referencia(): void
    {
        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->set('unidad_medida', 'Familias')
            ->set('referencia_cantidad', 5000)
            ->set('potencial_cantidad', 6000) // > referencia
            ->set('objetivo_cantidad', 3000)
            ->call('guardar')
            ->assertHasErrors('potencial_cantidad');
    }

    /** @test */
    public function carga_datos_existentes_al_montar(): void
    {
        $this->programa->poblacion()->create([
            'unidad_medida' => 'MIPYMES',
            'referencia_cantidad' => 50000,
            'potencial_cantidad' => 10000,
            'objetivo_cantidad' => 2000,
            'anio_ejercicio' => $this->programa->ejercicio_fiscal,
        ]);

        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->assertSet('unidad_medida', 'MIPYMES')
            ->assertSet('referencia_cantidad', 50000)
            ->assertSet('objetivo_cantidad', 2000);
    }
}
```

**Step 3: Test feature de AlineacionEstrategica**

```php
<?php

namespace Tests\Feature;

use App\Livewire\Mml\AlineacionEstrategica;
use App\Models\PedEje;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedTema;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlineacionEstrategicaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    /** @test */
    public function puede_ver_pagina_alineacion(): void
    {
        $this->actingAs($this->user)
            ->get(route('mml.etapa6', $this->programa))
            ->assertOk()
            ->assertSeeLivewire(AlineacionEstrategica::class);
    }

    /** @test */
    public function puede_guardar_alineacion_con_ped(): void
    {
        $eje = PedEje::factory()->create();
        $tema = PedTema::factory()->create(['ped_eje_id' => $eje->id]);
        $objetivo = PedObjetivoEstrategico::factory()->create(['ped_tema_id' => $tema->id]);

        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->set('ejeId', $eje->id)
            ->set('temaId', $tema->id)
            ->set('objetivoEstrategicoId', $objetivo->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $fin = $this->programa->mirNiveles()->where('tipo_nivel', 'fin')->first();
        $this->assertNotNull($fin);
        $this->assertEquals($objetivo->id, $fin->ped_objetivo_estrategico_id);
    }

    /** @test */
    public function no_permite_guardar_sin_objetivo(): void
    {
        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->call('guardar')
            ->assertHasErrors('objetivoEstrategicoId');
    }

    /** @test */
    public function selects_dependientes_se_limpian_al_cambiar_eje(): void
    {
        $eje1 = PedEje::factory()->create();
        $tema1 = PedTema::factory()->create(['ped_eje_id' => $eje1->id]);
        $obj1 = PedObjetivoEstrategico::factory()->create(['ped_tema_id' => $tema1->id]);

        Livewire::actingAs($this->user)
            ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
            ->set('ejeId', $eje1->id)
            ->set('temaId', $tema1->id)
            ->set('objetivoEstrategicoId', $obj1->id)
            ->set('ejeId', null) // cambiar eje limpia tema y objetivo
            ->assertSet('temaId', null)
            ->assertSet('objetivoEstrategicoId', null);
    }
}
```

**Step 4: Test del stepper component**

```php
<?php

namespace Tests\Feature\Components;

use App\Models\ProgramaPresupuestario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StepperComponentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function stepper_renders_six_steps(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $view = $this->blade(
            '<x-mml.stepper :programa="$programa" :paso-actual="1" />',
            ['programa' => $programa]
        );

        $view->assertSee('Problema');
        $view->assertSee('Poblaciones');
        $view->assertSee('Alineación');
    }

    /** @test */
    public function stepper_marks_current_step(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $view = $this->blade(
            '<x-mml.stepper :programa="$programa" :paso-actual="3" />',
            ['programa' => $programa]
        );

        // Paso 3 activo debe tener clase azul
        $view->assertSee('bg-blue-600');
    }
}
```

**Step 5: Ejecutar los tests**

```bash
./vendor/bin/sail artisan test tests/Unit/PoblacionProgramaModelTest.php
./vendor/bin/sail artisan test tests/Feature/EmbudoPoblacionesTest.php
./vendor/bin/sail artisan test tests/Feature/AlineacionEstrategicaTest.php
./vendor/bin/sail artisan test tests/Feature/Components/StepperComponentTest.php
```

**Step 6: Ejecutar suite completa para verificar no-regresión**

```bash
./vendor/bin/sail artisan test --parallel
```

**Commit:**
```
test(mml): add tests for EmbudoPoblaciones, AlineacionEstrategica, stepper

Tests cover: population funnel validation (CHECK constraint, embudo logic),
PED alignment saving, dependent selects, stepper rendering.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 9: Verificación final y limpieza

**Step 1: Verificar que todas las rutas existen**

```bash
./vendor/bin/sail artisan route:list --name=mml
```

Debe mostrar: `mml.etapa1` a `mml.etapa6` + `mml.mir`

**Step 2: Verificar que las vistas compilan**

```bash
./vendor/bin/sail artisan view:cache 2>&1 | head -10
./vendor/bin/sail artisan view:clear
```

**Step 3: Verificar que no hay factories faltantes**

Si los tests fallan por falta de factories para `PedEje`, `PedTema`, `PedObjetivoEstrategico`, crear factories mínimas:

`database/factories/PedEjeFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\PedEje;
use App\Models\PedPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedEjeFactory extends Factory
{
    protected $model = PedEje::class;

    public function definition(): array
    {
        return [
            'ped_plan_id' => PedPlan::factory(),
            'numero' => $this->faker->unique()->numberBetween(1, 10),
            'nombre' => $this->faker->sentence(3),
            'descripcion' => $this->faker->paragraph(),
        ];
    }
}
```

Crear factories equivalentes para `PedPlan`, `PedTema`, `PedObjetivoEstrategico` siguiendo el mismo patrón con los campos requeridos de cada tabla.

**Step 4: Suite completa**

```bash
./vendor/bin/sail artisan test --parallel
```

Expected: todos los tests pasan, incluyendo los ~426 existentes.

**Commit (si se crearon factories):**
```
test(factories): add PED model factories for testing

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```
