# Refactor: Vistas PED a Arquitectura Frontend v2

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Migrar todas las vistas del módulo PED a la arquitectura de componentes Blade definida (Atomic Design con Slots), eliminando el uso de modales para flujos CRUD y estandarizando los elementos visuales.

**Architecture:** Jetstream es el shell (`x-app-layout`). `x-slot name="header"` recibe título + acciones vía `x-page.header`. El `$slot` principal se envuelve con `x-page.container` (breadcrumbs + contenido). Los formularios usan `x-page.form-footer` (barra sticky) en lugar de footer slot. Se crean los componentes de contenido faltantes (`x-page.container`, `x-page.header`, `x-page.form-footer`, `x-ui.button.*`, `x-modals.confirm`, `x-forms.section`). Los formularios de crear/editar Plan y Nodo se convierten de modales Livewire a páginas completas. Los partiales del árbol reemplazan sus `$dispatch` events por enlaces de navegación directa.

**Tech Stack:** Laravel 12, Livewire 3, Alpine.js, Blade Components, Tailwind CSS

---

## Diagnóstico de violaciones actuales

| Archivo | Violación | Regla |
|---|---|---|
| `cascade/ped/index.blade.php` | Flash message HTML crudo | `x-page.container` lo maneja |
| `ped-plan-form.blade.php` | Crear/Editar Plan usa `x-dialog-modal` | Regla 1: forms = páginas completas |
| `ped-nodo-form.blade.php` | Crear/Editar Nodo usa `x-dialog-modal` | Regla 1: forms = páginas completas |
| `ped-nodo-form.blade.php` | Eliminar nodo sin confirmación | Regla 1: eliminación = `x-modals.confirm` |
| Partiales del árbol | `<button class="text-xs text-indigo-600...">` inline | Usar `x-ui.button.*` |
| Partiales del árbol | `<span class="bg-blue-100 rounded...">` inline | Usar `x-ui.badge` |

---

## Tarea 1: Componentes base — Estructura de Página

**Files:**
- Crear: `resources/views/components/page/header.blade.php`
- Crear: `resources/views/components/page/container.blade.php`
- Crear: `resources/views/components/page/form-footer.blade.php`

> **Filosofía:** Jetstream es el shell. `x-page.header` va dentro de `x-slot name="header"` (lleva título + botones de acción). `x-page.container` envuelve el cuerpo (breadcrumbs + contenido). `x-page.form-footer` es la barra sticky de Guardar/Cancelar dentro de formularios.

**Paso 1: Crear `components/page/header.blade.php`**

Diseñado para ir dentro del `<x-slot name="header">` de Jetstream.

```blade
@props(['title', 'subtitle' => null])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $title }}
        </h2>
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if(!empty($slot->toHtml()))
        <div class="mt-4 sm:mt-0 sm:ml-4 flex items-center space-x-3">
            {{ $slot }}
        </div>
    @endif
</div>
```

**Paso 2: Crear `components/page/container.blade.php`**

Envuelve el cuerpo de la página: padding, max-width, breadcrumbs y flash message.

```blade
@props(['breadcrumbs' => []])

<div {{ $attributes->merge(['class' => 'py-6']) }}>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        {{-- 1. Breadcrumb --}}
        @if(!empty($breadcrumbs))
            <nav class="text-sm text-gray-500 flex space-x-1 mb-4">
                @foreach($breadcrumbs as $crumb)
                    @if(!$loop->last)
                        <a href="{{ $crumb['url'] }}" class="hover:text-gray-700">{{ $crumb['label'] }}</a>
                        <span>/</span>
                    @else
                        <span class="text-gray-700 font-medium">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        {{-- 2. Flash message --}}
        @if(session('message') || session('status'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('message') ?? session('status') }}
            </div>
        @endif

        {{-- 3. Contenido principal --}}
        <div class="space-y-6">
            {{ $slot }}
        </div>

    </div>
</div>
```

**Paso 3: Crear `components/page/form-footer.blade.php`**

Barra sticky fija al pie de la pantalla para botones Guardar/Cancelar en formularios. El `lg:left-64` compensa el ancho del sidebar de Jetstream.

```blade
<div class="fixed bottom-0 left-0 right-0 lg:left-64 bg-white border-t border-gray-200 py-4 px-6 z-10 shadow-md">
    <div class="max-w-7xl mx-auto flex justify-end space-x-3">
        {{ $slot }}
    </div>
</div>

{{-- Espaciador para que el footer no tape el contenido al final del form --}}
<div class="h-20"></div>
```

**Paso 4: Verificar caché**

```bash
./vendor/bin/sail php artisan view:clear
./vendor/bin/sail php artisan cache:clear
```

Esperado: sin errores.

---

## Tarea 2: Componentes base — Botones y Formularios

**Files:**
- Crear: `resources/views/components/ui/button/primary.blade.php`
- Crear: `resources/views/components/ui/button/secondary.blade.php`
- Crear: `resources/views/components/ui/button/danger.blade.php`
- Crear: `resources/views/components/ui/badge.blade.php`
- Crear: `resources/views/components/forms/section.blade.php`

**Paso 1: Crear `components/ui/button/primary.blade.php`**

Acepta `href` (para links) o se usa como botón de submit.

```blade
@props(['href' => null, 'type' => 'button'])

@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </button>
@endif
```

**Paso 2: Crear `components/ui/button/secondary.blade.php`**

```blade
@props(['href' => null, 'type' => 'button'])

@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </button>
@endif
```

**Paso 3: Crear `components/ui/button/danger.blade.php`**

```blade
@props(['href' => null, 'type' => 'button'])

@if($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
        {{ $slot }}
    </button>
@endif
```

**Paso 4: Crear `components/ui/badge.blade.php`**

```blade
@props(['color' => 'blue'])

@php
$colors = [
    'blue'   => 'bg-blue-100 text-blue-800',
    'green'  => 'bg-green-100 text-green-800',
    'yellow' => 'bg-yellow-100 text-yellow-800',
    'red'    => 'bg-red-100 text-red-800',
    'gray'   => 'bg-gray-100 text-gray-800',
    'purple' => 'bg-purple-100 text-purple-800',
];
$colorClass = $colors[$color] ?? $colors['blue'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$colorClass}"]) }}>
    {{ $slot }}
</span>
```

**Paso 5: Crear `components/forms/section.blade.php`**

```blade
@props(['title' => null, 'description' => null])

<div class="bg-white shadow sm:rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        @if($title)
            <div class="mb-4 border-b border-gray-200 pb-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $title }}</h3>
                @if($description)
                    <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-6 gap-6">
            {{ $slot }}
        </div>
    </div>
</div>
```

**Paso 6: Limpiar caché de vistas**

```bash
./vendor/bin/sail php artisan view:clear
```

---

## Tarea 3: Componente `x-modals.confirm`

**Files:**
- Crear: `resources/views/components/modals/confirm.blade.php`

**Paso 1: Crear `components/modals/confirm.blade.php`**

Usa `x-dialog-modal` de Jetstream internamente. Requiere Alpine.js y que Livewire esté en la página.

```blade
@props([
    'id',
    'title'       => '¿Confirmar acción?',
    'message'     => 'Esta acción no se puede deshacer.',
    'confirmText' => 'Confirmar',
    'cancelText'  => 'Cancelar',
    'confirmUrl'  => null,
    'method'      => 'DELETE',
])

<div x-data="{ open: false }"
     x-on:open-confirm-{{ $id }}.window="open = true">

    <x-dialog-modal maxWidth="sm" x-bind:show="open" @close="open = false">
        <x-slot name="title">{{ $title }}</x-slot>

        <x-slot name="content">
            <p class="text-sm text-gray-600">{{ $message }}</p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button @click="open = false" class="mr-3">
                {{ $cancelText }}
            </x-secondary-button>

            @if($confirmUrl)
                <form method="POST" action="{{ $confirmUrl }}">
                    @csrf
                    @method($method)
                    <x-danger-button type="submit">
                        {{ $confirmText }}
                    </x-danger-button>
                </form>
            @else
                {{-- Para uso con Livewire: pasar wire:click al trigger, no aquí --}}
                <x-danger-button @click="$dispatch('confirmed-{{ $id }}'); open = false">
                    {{ $confirmText }}
                </x-danger-button>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>
```

> **Uso con Livewire:** El botón de eliminación despacha `open-confirm-{id}`. El modal escucha `confirmed-{id}` y el componente Livewire lo captura con `x-on:confirmed-{id}.window="$wire.delete()"`.

**Paso 2: Verificar caché**

```bash
./vendor/bin/sail php artisan view:clear
```

---

## Tarea 4: Refactorizar `cascade/ped/index.blade.php`

**Files:**
- Modificar: `resources/views/cascade/ped/index.blade.php`

**Paso 1: Reemplazar el contenido completo**

Antes: usa `x-app-layout` + `x-slot name="header"` + HTML manual.
Después: usa `x-layout.app` + `x-page.container` con slot `actions`.

El botón "Nuevo Plan" deja de abrir un modal y pasa a ser un link a la ruta `cascade.ped.plan.create` (que se crea en Tarea 5).

```blade
<x-app-layout>
    <x-slot name="header">
        <x-page.header title="Plan Estatal de Desarrollo"
                       subtitle="Árbol de ejes, temas, objetivos, estrategias y líneas de acción">
            <x-ui.button.primary href="{{ route('cascade.ped.plan.create') }}">
                Nuevo Plan
            </x-ui.button.primary>
        </x-page.header>
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'PED'],
    ]">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <livewire:cascade.ped-tree />
            </div>
        </div>
    </x-page.container>

</x-app-layout>
```

> **Nota:** `ped-nodo-form` se mantiene en el índice porque es un componente reactivo que escucha eventos del árbol. Se refactorizará en Tarea 7.

**Paso 2: Verificar que la página carga sin errores**

Acceder a `/cascade/ped` con un usuario con permiso `gestionar_catalogos`.
Esperado: página carga, el árbol se muestra, sin errores 500.

---

## Tarea 5: Rutas y Controlador — Páginas de Plan

**Files:**
- Modificar: `routes/web/cascade.php`
- Modificar: `app/Http/Controllers/Cascade/PedController.php`

**Paso 1: Agregar rutas de páginas de Plan en `cascade.php`**

Dentro del grupo `ped.`, agregar después de las rutas existentes:

```php
// Páginas de formulario (GET) — Plan
Route::get('/plan/create', [PedController::class, 'createPlan'])->name('plan.create');
Route::get('/plan/{plan}/edit', [PedController::class, 'editPlan'])->name('plan.edit');
```

**Paso 2: Agregar métodos al controlador `PedController`**

```php
public function createPlan(): View
{
    return view('cascade.ped.plan.create');
}

public function editPlan(PedPlan $plan): View
{
    return view('cascade.ped.plan.edit', compact('plan'));
}
```

Agregar `use Illuminate\View\View;` al inicio si no existe.

**Paso 3: Verificar rutas**

```bash
./vendor/bin/sail artisan route:list --path=cascade/ped/plan
```

Esperado: rutas `cascade.ped.plan.create` (GET) y `cascade.ped.plan.edit` (GET).

---

## Tarea 6: Refactorizar `PedPlanForm` Livewire (modal → form inline)

**Files:**
- Modificar: `app/Livewire/Cascade/PedPlanForm.php`
- Modificar: `resources/views/livewire/cascade/ped-plan-form.blade.php`
- Crear: `resources/views/cascade/ped/plan/create.blade.php`
- Crear: `resources/views/cascade/ped/plan/edit.blade.php`

**Paso 1: Refactorizar `PedPlanForm.php`**

Remover toda la lógica de modal (`$showModal`, `create()`, listener de `edit-plan`).
Agregar `mount()` que acepta un plan opcional.
El `save()` redirige al índice tras guardar.

```php
<?php

namespace App\Livewire\Cascade;

use App\Models\PedPlan;
use Livewire\Component;

class PedPlanForm extends Component
{
    public ?PedPlan $plan = null;

    public string $nombre = '';
    public string $nivel_gobierno = 'estatal';
    public int $periodo_inicio;
    public int $periodo_fin;
    public bool $activo = false;

    public function mount(?PedPlan $plan = null): void
    {
        if ($plan && $plan->exists) {
            $this->plan = $plan;
            $this->nombre = $plan->nombre;
            $this->nivel_gobierno = $plan->nivel_gobierno;
            $this->periodo_inicio = $plan->periodo_inicio;
            $this->periodo_fin = $plan->periodo_fin;
            $this->activo = $plan->activo;
        } else {
            $this->periodo_inicio = (int) date('Y');
            $this->periodo_fin = (int) date('Y') + 6;
        }
    }

    public function save(): void
    {
        $this->validate([
            'nombre'         => ['required', 'string', 'max:255'],
            'nivel_gobierno' => ['required', 'in:estatal,municipal'],
            'periodo_inicio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo_fin'    => ['required', 'integer', 'min:2000', 'max:2100', 'gt:periodo_inicio'],
            'activo'         => ['boolean'],
        ]);

        if ($this->plan && $this->plan->exists) {
            $this->plan->update([
                'nombre'         => $this->nombre,
                'nivel_gobierno' => $this->nivel_gobierno,
                'periodo_inicio' => $this->periodo_inicio,
                'periodo_fin'    => $this->periodo_fin,
                'activo'         => $this->activo,
            ]);
            session()->flash('message', 'Plan actualizado correctamente.');
        } else {
            PedPlan::create([
                'nombre'         => $this->nombre,
                'nivel_gobierno' => $this->nivel_gobierno,
                'periodo_inicio' => $this->periodo_inicio,
                'periodo_fin'    => $this->periodo_fin,
                'activo'         => $this->activo,
            ]);
            session()->flash('message', 'Plan creado correctamente.');
        }

        $this->redirect(route('cascade.ped.index'));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.cascade.ped-plan-form');
    }
}
```

**Paso 2: Refactorizar la vista `ped-plan-form.blade.php`**

Reemplazar completamente. Sin `x-dialog-modal`. Renderiza solo los campos del formulario.

```blade
<div>
    <x-forms.section title="Datos del Plan">

        <div class="col-span-6">
            <x-label for="nombre" value="Nombre del Plan" />
            <x-input id="nombre" type="text" class="mt-1 block w-full" wire:model="nombre" />
            @error('nombre')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-3">
            <x-label for="periodo_inicio" value="Año Inicio" />
            <x-input id="periodo_inicio" type="number" class="mt-1 block w-full" wire:model="periodo_inicio" />
            @error('periodo_inicio')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-3">
            <x-label for="periodo_fin" value="Año Fin" />
            <x-input id="periodo_fin" type="number" class="mt-1 block w-full" wire:model="periodo_fin" />
            @error('periodo_fin')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-3">
            <x-label for="nivel_gobierno" value="Nivel de Gobierno" />
            <select id="nivel_gobierno"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    wire:model="nivel_gobierno">
                <option value="estatal">Estatal</option>
                <option value="municipal">Municipal</option>
            </select>
            @error('nivel_gobierno')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-6 flex items-center">
            <x-checkbox id="activo" wire:model="activo" />
            <x-label for="activo" class="ml-2" value="Marcar como plan activo" />
        </div>

    </x-forms.section>

    <x-page.form-footer>
        <x-ui.button.secondary href="{{ route('cascade.ped.index') }}" type="button">
            Cancelar
        </x-ui.button.secondary>
        <x-ui.button.primary wire:click="save" type="button">
            {{ $plan && $plan->exists ? 'Guardar Cambios' : 'Crear Plan' }}
        </x-ui.button.primary>
    </x-page.form-footer>
</div>
```

**Paso 3: Crear `cascade/ped/plan/create.blade.php`**

```blade
<x-app-layout>
    <x-slot name="header">
        <x-page.header title="Nuevo Plan Estatal de Desarrollo" />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'PED', 'url' => route('cascade.ped.index')],
        ['label' => 'Nuevo Plan'],
    ]">
        <livewire:cascade.ped-plan-form />
    </x-page.container>

</x-app-layout>
```

> **Nota sobre los botones Guardar/Cancelar:** El componente Livewire `ped-plan-form` renderiza su propio `x-page.form-footer` con los botones. De esta forma el formulario es autocontenido: la página solo provee el layout y el header.

**Paso 4: Crear `cascade/ped/plan/edit.blade.php`**

```blade
<x-app-layout>
    <x-slot name="header">
        <x-page.header title="Editar Plan" :subtitle="$plan->nombre" />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'PED', 'url' => route('cascade.ped.index')],
        ['label' => 'Editar Plan'],
    ]">
        <livewire:cascade.ped-plan-form :plan="$plan" />
    </x-page.container>

</x-app-layout>
```

**Paso 5: Actualizar `ped-tree.blade.php` — botón "Editar" del plan**

En `ped-tree.blade.php`, reemplazar el `@click.stop="$dispatch('edit-plan', ...)"` por un link a la ruta de edición:

```blade
{{-- Antes: --}}
<button @click.stop="$dispatch('edit-plan', { id: {{ $plan->id }} })"
        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
    Editar
</button>

{{-- Después: --}}
<x-ui.button.secondary href="{{ route('cascade.ped.plan.edit', $plan) }}" class="text-xs py-1 px-2">
    Editar
</x-ui.button.secondary>
```

**Paso 6: Verificar páginas funcionan**

Acceder a `/cascade/ped/plan/create` → debe mostrar el formulario.
Llenar los campos y guardar → redirige al índice con mensaje flash.

---

## Tarea 7: Rutas y Controlador — Páginas de Nodo

**Files:**
- Modificar: `routes/web/cascade.php`
- Modificar: `app/Http/Controllers/Cascade/PedController.php`

**Paso 1: Agregar rutas de páginas de Nodo en `cascade.php`**

```php
// Páginas de formulario (GET) — Nodo (eje, tema, objetivo, estrategia, linea)
Route::get('/nodo/create', [PedController::class, 'createNodo'])->name('nodo.create');
Route::get('/nodo/{tipo}/{id}/edit', [PedController::class, 'editNodo'])->name('nodo.edit');
```

**Paso 2: Agregar métodos al controlador**

```php
public function createNodo(Request $request): View
{
    $tipo = $request->query('tipo');
    $parentId = $request->query('parent_id');

    abort_if(!in_array($tipo, ['eje', 'tema', 'objetivo', 'estrategia', 'linea']), 400);

    return view('cascade.ped.nodo.create', compact('tipo', 'parentId'));
}

public function editNodo(string $tipo, int $id): View
{
    abort_if(!in_array($tipo, ['eje', 'tema', 'objetivo', 'estrategia', 'linea']), 400);

    return view('cascade.ped.nodo.edit', compact('tipo', 'id'));
}
```

Agregar `use Illuminate\Http\Request;` al inicio si no existe.

**Paso 3: Verificar rutas**

```bash
./vendor/bin/sail artisan route:list --path=cascade/ped/nodo
```

Esperado: rutas `cascade.ped.nodo.create` y `cascade.ped.nodo.edit`.

---

## Tarea 8: Refactorizar `PedNodoForm` Livewire (modal → form inline)

**Files:**
- Modificar: `app/Livewire/Cascade/PedNodoForm.php`
- Modificar: `resources/views/livewire/cascade/ped-nodo-form.blade.php`
- Crear: `resources/views/cascade/ped/nodo/create.blade.php`
- Crear: `resources/views/cascade/ped/nodo/edit.blade.php`

**Paso 1: Refactorizar `PedNodoForm.php`**

```php
<?php

namespace App\Livewire\Cascade;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedTema;
use Livewire\Component;

class PedNodoForm extends Component
{
    public string $tipo = 'eje';
    public ?int $parentId = null;
    public ?int $nodoId = null;

    // Campos comunes
    public string $clave = '';
    public string $descripcion = '';
    // Campos para eje/tema
    public string $numero = '';
    public string $nombre = '';

    public function mount(string $tipo, ?int $parentId = null, ?int $nodoId = null): void
    {
        $this->tipo = $tipo;
        $this->parentId = $parentId;
        $this->nodoId = $nodoId;

        if ($nodoId) {
            $this->loadNodo($nodoId);
        }
    }

    protected function loadNodo(int $id): void
    {
        $model = $this->getModel()->find($id);
        if (!$model) return;

        if (in_array($this->tipo, ['eje', 'tema'])) {
            $this->numero = $model->numero;
            $this->nombre = $model->nombre;
            $this->descripcion = $model->descripcion ?? '';
        } else {
            $this->clave = $model->clave;
            $this->descripcion = $model->descripcion;
        }
    }

    public function save(): void
    {
        $this->validate($this->getValidationRules());

        $data = in_array($this->tipo, ['eje', 'tema'])
            ? ['numero' => $this->numero, 'nombre' => $this->nombre, 'descripcion' => $this->descripcion]
            : ['clave' => $this->clave, 'descripcion' => $this->descripcion];

        $data = array_merge($data, $this->getParentData());

        if ($this->nodoId) {
            $this->getModel()->find($this->nodoId)?->update($data);
            session()->flash('message', ucfirst($this->getTipoLabel()) . ' actualizado.');
        } else {
            $this->getModel()->create($data);
            session()->flash('message', ucfirst($this->getTipoLabel()) . ' creado.');
        }

        $this->redirect(route('cascade.ped.index'));
    }

    public function delete(): void
    {
        $this->getModel()->find($this->nodoId)?->delete();
        session()->flash('message', ucfirst($this->getTipoLabel()) . ' eliminado.');
        $this->redirect(route('cascade.ped.index'));
    }

    protected function getModel(): \Illuminate\Database\Eloquent\Builder
    {
        return match($this->tipo) {
            'eje'       => PedEje::query(),
            'tema'      => PedTema::query(),
            'objetivo'  => PedObjetivoEstrategico::query(),
            'estrategia'=> PedEstrategia::query(),
            'linea'     => PedLineaAccion::query(),
        };
    }

    protected function getParentData(): array
    {
        return match($this->tipo) {
            'eje'       => ['ped_plan_id' => $this->parentId],
            'tema'      => ['ped_eje_id' => $this->parentId],
            'objetivo'  => ['ped_tema_id' => $this->parentId],
            'estrategia'=> ['ped_objetivo_estrategico_id' => $this->parentId],
            'linea'     => ['ped_estrategia_id' => $this->parentId],
            default     => [],
        };
    }

    protected function getValidationRules(): array
    {
        if (in_array($this->tipo, ['eje', 'tema'])) {
            return [
                'numero' => ['required', 'string', 'max:10'],
                'nombre' => ['required', 'string', 'max:255'],
                'descripcion' => ['nullable', 'string', 'max:500'],
            ];
        }
        return [
            'clave'       => ['required', 'string', 'max:40'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }

    public function getTipoLabel(): string
    {
        return match($this->tipo) {
            'eje'        => 'Eje',
            'tema'       => 'Tema',
            'objetivo'   => 'Objetivo estratégico',
            'estrategia' => 'Estrategia',
            'linea'      => 'Línea de acción',
            default      => $this->tipo,
        };
    }

    public function getDependientes(): array
    {
        if (!$this->nodoId) return [];

        $model = $this->getModel()->find($this->nodoId);
        if (!$model) return [];

        $map = [
            'eje'        => fn($m) => ['temas' => $m->temas()->count()],
            'tema'       => fn($m) => ['objetivos' => $m->objetivos()->count()],
            'objetivo'   => fn($m) => ['estrategias' => $m->estrategias()->count()],
            'estrategia' => fn($m) => ['lineas' => $m->lineas()->count()],
            'linea'      => fn($m) => [],
        ];

        $counts = ($map[$this->tipo] ?? fn($m) => [])($model);
        return array_filter($counts, fn($c) => $c > 0);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.cascade.ped-nodo-form');
    }
}
```

**Paso 2: Refactorizar `ped-nodo-form.blade.php`**

```blade
<div>
    <x-forms.section :title="($nodoId ? 'Editar ' : 'Nuevo ') . $this->getTipoLabel()">

        @if(in_array($tipo, ['eje', 'tema']))
            <div class="col-span-2">
                <x-label for="numero" value="Número" />
                <x-input id="numero" type="text" class="mt-1 block w-full"
                         wire:model="numero" placeholder="Ej: 1, 1.1" />
                @error('numero')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="col-span-4">
                <x-label for="nombre" value="Nombre" />
                <x-input id="nombre" type="text" class="mt-1 block w-full" wire:model="nombre" />
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @else
            <div class="col-span-2">
                <x-label for="clave" value="Clave" />
                <x-input id="clave" type="text" class="mt-1 block w-full"
                         wire:model="clave" placeholder="Ej: 1, 1.1" />
                @error('clave')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div class="col-span-6">
            <x-label for="descripcion" value="Descripción" />
            <textarea id="descripcion"
                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                      rows="3"
                      wire:model="descripcion"
                      maxlength="500"></textarea>
            <p class="mt-1 text-xs text-gray-500">{{ strlen($descripcion) }}/500</p>
            @error('descripcion')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Advertencia de dependientes --}}
        @php($dependientes = $this->getDependientes())
        @if(!empty($dependientes) && $nodoId)
            <div class="col-span-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-700">
                    <strong>Advertencia:</strong> Este elemento tiene:
                    @foreach($dependientes as $tipo => $cantidad)
                        {{ $cantidad }} {{ $tipo }}{{ !$loop->last ? ',' : '' }}
                    @endforeach.
                    Al eliminarlo, todos sus dependientes serán eliminados también.
                </p>
            </div>
        @endif

    </x-forms.section>

    {{-- Modal de confirmación de eliminación (solo en edición) --}}
    @if($nodoId)
        <x-modals.confirm
            id="nodo-{{ $nodoId }}"
            :title="'¿Eliminar ' . $this->getTipoLabel() . '?'"
            message="Esta acción no se puede deshacer. Se eliminarán todos los elementos dependientes."
            confirmText="Sí, eliminar"
        />
        <div x-data x-on:confirmed-nodo-{{ $nodoId }}.window="$wire.delete()"></div>
    @endif

    <x-page.form-footer>
        @if($nodoId)
            <x-ui.button.danger x-data @click="$dispatch('open-confirm-nodo-{{ $nodoId }}')" type="button">
                Eliminar
            </x-ui.button.danger>
        @endif
        <x-ui.button.secondary href="{{ route('cascade.ped.index') }}" type="button">
            Cancelar
        </x-ui.button.secondary>
        <x-ui.button.primary wire:click="save" type="button">
            {{ $nodoId ? 'Guardar Cambios' : 'Crear ' . $this->getTipoLabel() }}
        </x-ui.button.primary>
    </x-page.form-footer>
</div>
```

**Paso 3: Crear `cascade/ped/nodo/create.blade.php`**

```blade
<x-app-layout>
    <x-slot name="header">
        <x-page.header :title="'Nuevo ' . ucfirst($tipo)" />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'PED', 'url' => route('cascade.ped.index')],
        ['label' => 'Nuevo ' . ucfirst($tipo)],
    ]">
        <livewire:cascade.ped-nodo-form :tipo="$tipo" :parent-id="(int) $parentId" />
    </x-page.container>

</x-app-layout>
```

**Paso 4: Crear `cascade/ped/nodo/edit.blade.php`**

```blade
<x-app-layout>
    <x-slot name="header">
        <x-page.header :title="'Editar ' . ucfirst($tipo)" />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'PED', 'url' => route('cascade.ped.index')],
        ['label' => 'Editar'],
    ]">
        <livewire:cascade.ped-nodo-form :tipo="$tipo" :nodo-id="$id" />
    </x-page.container>

</x-app-layout>
```

---

## Tarea 9: Actualizar partiales del árbol — Links en lugar de dispatch

**Files:**
- Modificar: `resources/views/livewire/cascade/ped-tree.blade.php`
- Modificar: `resources/views/livewire/cascade/partials/ped-eje-node.blade.php`
- Modificar: `resources/views/livewire/cascade/partials/ped-tema-node.blade.php`
- Modificar: `resources/views/livewire/cascade/partials/ped-objetivo-node.blade.php`
- Modificar: `resources/views/livewire/cascade/partials/ped-estrategia-node.blade.php`
- Modificar: `resources/views/livewire/cascade/partials/ped-linea-node.blade.php`

**Paso 1: Actualizar `ped-tree.blade.php` — botón "Agregar Eje"**

```blade
{{-- Antes: --}}
<button @click="$dispatch('create-nodo', { tipo: 'eje', parentId: {{ $plan->id }} })"
        class="inline-flex items-center px-2 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800">
    ...
    Agregar Eje
</button>

{{-- Después: --}}
<x-ui.button.secondary href="{{ route('cascade.ped.nodo.create', ['tipo' => 'eje', 'parent_id' => $plan->id]) }}"
                        class="text-xs py-1 px-2">
    + Eje
</x-ui.button.secondary>
```

También reemplazar el badge inline del número de ejes:

```blade
{{-- Antes: --}}
<span class="text-sm font-medium text-gray-700">Ejes ({{ $plan->ejes->count() }})</span>

{{-- Después: --}}
<span class="text-sm font-medium text-gray-700">
    Ejes <x-ui.badge color="gray">{{ $plan->ejes->count() }}</x-ui.badge>
</span>
```

**Paso 2: Patrón para cada partial (eje-node, tema-node, objetivo-node, estrategia-node)**

Aplicar el mismo patrón en cada partial: reemplazar `$dispatch('edit-nodo', ...)` y `$dispatch('create-nodo', ...)` por links:

```blade
{{-- Botón Editar (en cada partial): --}}
{{-- Antes: --}}
<button @click.stop="$dispatch('edit-nodo', { tipo: 'eje', nodoId: {{ $eje->id }} })"
        class="text-xs text-indigo-600 hover:text-indigo-900">
    Editar
</button>

{{-- Después: --}}
<a href="{{ route('cascade.ped.nodo.edit', ['tipo' => 'eje', 'id' => $eje->id]) }}"
   class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
    Editar
</a>

{{-- Botón Agregar hijo (en cada partial): --}}
{{-- Antes: --}}
<button @click="$dispatch('create-nodo', { tipo: 'tema', parentId: {{ $eje->id }} })"
        class="text-xs text-indigo-600 hover:text-indigo-800">
    + Tema
</button>

{{-- Después: --}}
<a href="{{ route('cascade.ped.nodo.create', ['tipo' => 'tema', 'parent_id' => $eje->id]) }}"
   class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
    + Tema
</a>
```

Reemplazar los badges de número:

```blade
{{-- Antes: --}}
<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
    {{ $eje->numero }}
</span>

{{-- Después: --}}
<x-ui.badge color="blue">{{ $eje->numero }}</x-ui.badge>
```

Mapa de colores por nivel:
- Eje → `blue`
- Tema → `purple`
- Objetivo → `green`
- Estrategia → `yellow`
- Línea → `gray`

**Paso 3: Eliminar `x-on:edit-nodo` y `x-on:create-nodo` de `ped-nodo-form`**

En `ped-nodo-form.blade.php` (el componente que sigue en el índice), ya no se necesitan los listeners de Alpine:

```blade
{{-- Eliminar estas líneas del componente wrapper: --}}
x-on:edit-nodo.window="$wire.editByParams($event.detail.tipo, $event.detail.nodoId)"
x-on:create-nodo.window="$wire.createByParams($event.detail.tipo, $event.detail.parentId)"
```

> **Nota:** `livewire:cascade.ped-nodo-form` ya NO se incluye en `index.blade.php` con esta refactorización, ya que el formulario es ahora una página completa. Remover su instancia del índice.

**Paso 4: Actualizar `cascade/ped/index.blade.php` — remover ped-nodo-form**

```blade
{{-- Eliminar esta línea del index: --}}
<livewire:cascade.ped-nodo-form />
```

---

## Tarea 10: Actualizar tests

**Files:**
- Modificar: `tests/Feature/PedCrudTest.php`

**Paso 1: Agregar tests para las nuevas páginas GET**

```php
/** @test */
public function it_can_access_create_plan_page(): void
{
    $response = $this->actingAs($this->adminUser)
        ->get(route('cascade.ped.plan.create'));

    $response->assertStatus(200)
        ->assertSeeLivewire('cascade.ped-plan-form');
}

/** @test */
public function it_can_access_edit_plan_page(): void
{
    $plan = PedPlan::factory()->create();

    $response = $this->actingAs($this->adminUser)
        ->get(route('cascade.ped.plan.edit', $plan));

    $response->assertStatus(200)
        ->assertSeeLivewire('cascade.ped-plan-form');
}

/** @test */
public function it_can_access_create_nodo_page(): void
{
    $plan = PedPlan::factory()->create();

    $response = $this->actingAs($this->adminUser)
        ->get(route('cascade.ped.nodo.create', ['tipo' => 'eje', 'parent_id' => $plan->id]));

    $response->assertStatus(200)
        ->assertSeeLivewire('cascade.ped-nodo-form');
}
```

**Paso 2: Verificar que los tests de CRUD siguen pasando**

```bash
./vendor/bin/sail artisan test tests/Feature/PedCrudTest.php -v
```

Esperado: todos los tests pasan.

---

## Tarea 11: Verificación Final

**Paso 1: Suite completa**

```bash
./vendor/bin/sail artisan test
```

Esperado: igual o más tests que el baseline (`60 passed, 7 skipped`).

**Paso 2: Verificar flujo completo en navegador**

1. Ir a `/cascade/ped` → árbol visible, botón "Nuevo Plan" presente
2. Click "Nuevo Plan" → navega a página `/cascade/ped/plan/create` (no modal)
3. Crear un plan → redirige al índice con flash
4. En el árbol, click "+ Eje" en un plan → navega a `/cascade/ped/nodo/create?tipo=eje&parent_id=X`
5. Crear un eje → redirige al índice
6. Click "Editar" en un eje → navega a `/cascade/ped/nodo/eje/{id}/edit`
7. Click "Eliminar" → aparece `x-modals.confirm` (modal de confirmación)
8. Confirmar eliminación → redirige al índice

**Paso 3: Verificar que no quedan `x-dialog-modal` en vistas PED**

```bash
grep -r "dialog-modal" resources/views/livewire/cascade/
```

Esperado: sin resultados.

**Paso 4: Commit**

```bash
git add resources/views/components/ \
        resources/views/cascade/ped/ \
        resources/views/livewire/cascade/ \
        app/Livewire/Cascade/ \
        app/Http/Controllers/Cascade/PedController.php \
        routes/web/cascade.php \
        tests/Feature/PedCrudTest.php

git commit -m "refactor(ped): migrar vistas a arquitectura frontend v2 (Atomic Design + Slots)"
```

---

## Criterios de Aceptación

- [ ] No existen `x-dialog-modal` en ninguna vista del módulo PED
- [ ] Formularios de Plan y Nodo son páginas completas accesibles via URL
- [ ] Botones "Editar" y "+ [hijo]" en el árbol son links de navegación
- [ ] Eliminación de nodo tiene confirmación via `x-modals.confirm`
- [ ] Todos los botones usan `x-ui.button.*` o links semánticos
- [ ] Los badges usan `x-ui.badge`
- [ ] Flash messages son manejados por `x-page.container`
- [ ] `./vendor/bin/sail artisan test` pasa igual que el baseline

---

## Componentes creados en este plan

| Componente | Archivo |
|---|---|
| `x-page.container` | `components/page/container.blade.php` |
| `x-page.header` | `components/page/header.blade.php` |
| `x-page.form-footer` | `components/page/form-footer.blade.php` |
| `x-forms.section` | `components/forms/section.blade.php` |
| `x-ui.button.primary` | `components/ui/button/primary.blade.php` |
| `x-ui.button.secondary` | `components/ui/button/secondary.blade.php` |
| `x-ui.button.danger` | `components/ui/button/danger.blade.php` |
| `x-ui.badge` | `components/ui/badge.blade.php` |
| `x-modals.confirm` | `components/modals/confirm.blade.php` |
