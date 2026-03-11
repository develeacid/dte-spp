# Responsive UX/UI (Notion-Inspired) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make the application mobile-friendly for planeador+ roles, with Notion-inspired bottom navigation, contextual action bars, and responsive components.

**Architecture:** Replace sidebar navigation on mobile with a bottom nav bar. Add a contextual bottom action bar for page-level actions. Adapt dashboard KPIs to 2-col grid, tables to priority columns, and MML wizard stepper to compact mobile format. All changes use Tailwind responsive breakpoints with Alpine.js for interactivity.

**Tech Stack:** Laravel Blade, Tailwind CSS, Alpine.js, Livewire 3

---

## Phase 1: Infrastructure — Bottom Nav + Action Bar + Page Headers

### Task 1: Create Bottom Navigation Bar Component

**Files:**
- Create: `resources/views/components/ui/bottom-nav.blade.php`
- Modify: `resources/views/layouts/app.blade.php:35-56`
- Modify: `resources/css/app.css:20-21`
- Reference: `resources/views/components/layout/sidebar-nav.blade.php` (permission patterns)

**Step 1: Create the bottom-nav component**

```blade
{{-- resources/views/components/ui/bottom-nav.blade.php --}}
<nav class="fixed bottom-0 inset-x-0 z-40 bg-white border-t border-gray-200 lg:hidden"
     aria-label="Navegación principal móvil">
    <div class="flex items-center justify-around h-14">
        {{-- Inicio - visible para todos --}}
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center justify-center w-full h-full space-y-0.5 {{ request()->routeIs('dashboard') ? 'text-brand' : 'text-gray-400' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <span class="text-[10px] font-medium">Inicio</span>
        </a>

        {{-- Programas - visible para todos --}}
        <a href="{{ route('mml.programas') }}"
           class="flex flex-col items-center justify-center w-full h-full space-y-0.5 {{ request()->routeIs('mml.*') ? 'text-brand' : 'text-gray-400' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
            </svg>
            <span class="text-[10px] font-medium">Programas</span>
        </a>

        {{-- Seguimiento - permission gated --}}
        @canany(['revisar_avance', 'capturar_avance'])
            <a href="{{ route('tracking.panel') }}"
               class="flex flex-col items-center justify-center w-full h-full space-y-0.5 {{ request()->routeIs('tracking.*') ? 'text-brand' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                <span class="text-[10px] font-medium">Seguimiento</span>
            </a>
        @endcanany

        {{-- Reportes - permission gated --}}
        @can('exportar_reportes')
            <a href="{{ route('evaluation.transversal') }}"
               class="flex flex-col items-center justify-center w-full h-full space-y-0.5 {{ request()->routeIs('evaluation.*') || request()->routeIs('datos-abiertos.*') ? 'text-brand' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <span class="text-[10px] font-medium">Reportes</span>
            </a>
        @endcan

        {{-- Admin - only admin role --}}
        @canany(['administrar_usuarios', 'invitar_usuarios'])
            <a href="{{ route('admin.users') }}"
               class="flex flex-col items-center justify-center w-full h-full space-y-0.5 {{ request()->routeIs('admin.*') ? 'text-brand' : 'text-gray-400' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-[10px] font-medium">Admin</span>
            </a>
        @endcanany
    </div>
</nav>
```

**Step 2: Add bottom-nav to layout and add CSS variable**

In `resources/views/layouts/app.blade.php`, add the bottom-nav component after the `</main>` tag (line 55), inside the main content `<div>`:

```blade
                {{-- Bottom Navigation (mobile only) --}}
                <x-ui.bottom-nav />
```

In `resources/css/app.css`, add after the sidebar variables (line 21):

```css
    --bottom-nav-height: 56px;
```

**Step 3: Add bottom padding to main content on mobile**

In `resources/views/layouts/app.blade.php`, change the `<main>` tag (line 53) to add bottom padding on mobile so content isn't hidden behind the bottom nav:

```blade
                <main id="main-content" class="pb-[var(--bottom-nav-height)] lg:pb-0">
```

**Step 4: Verify locally**

Run: `./vendor/bin/sail npm run dev`

Open browser, resize to mobile width (< 1024px). Verify:
- Bottom nav appears on mobile
- Bottom nav hides on desktop (lg+)
- Active item is highlighted
- Permission-gated items only show for correct roles
- Sidebar still works on desktop

**Step 5: Commit**

```bash
git add resources/views/components/ui/bottom-nav.blade.php resources/views/layouts/app.blade.php resources/css/app.css
git commit -m "feat: add mobile bottom navigation bar (Notion-inspired)"
```

---

### Task 2: Update Form Footer for Bottom Nav Coexistence

**Files:**
- Modify: `resources/views/components/page/form-footer.blade.php:1-10`

**Step 1: Update form-footer to sit above bottom nav on mobile**

Replace the entire content of `resources/views/components/page/form-footer.blade.php`:

```blade
<div class="fixed right-0 bg-white border-t border-gray-200 py-3 px-4 sm:px-6 z-20 shadow-md transition-all duration-300 ease-in-out"
     :class="collapsed ? 'left-[var(--sidebar-collapsed-width)]' : 'left-[var(--sidebar-width)]'"
     x-bind:style="window.innerWidth < 1024 ? 'left: 0' : ''"
     style="bottom: var(--bottom-nav-height);"
     class="lg:bottom-0"
     x-data
     :style="window.innerWidth < 1024 ? 'left: 0; bottom: var(--bottom-nav-height)' : `bottom: 0`">
    <div class="max-w-7xl mx-auto flex justify-end space-x-3">
        {{ $slot }}
    </div>
</div>

{{-- Spacer: taller on mobile to account for both footer + bottom nav --}}
<div class="h-32 lg:h-20"></div>
```

**Step 2: Verify locally**

Open a page with form-footer (e.g., MML wizard step). Verify:
- On mobile: footer sits above bottom nav, no overlap
- On desktop: footer at bottom as before
- Spacer prevents content from hiding

**Step 3: Commit**

```bash
git add resources/views/components/page/form-footer.blade.php
git commit -m "feat: adjust form-footer to coexist with bottom nav on mobile"
```

---

### Task 3: Make Page Header Responsive

**Files:**
- Modify: `resources/views/components/page/header.blade.php:1-18`

**Step 1: Update page header for mobile**

On mobile, the header actions should be hidden (they'll move to the bottom action bar). Update `resources/views/components/page/header.blade.php`:

```blade
@props(['title', 'subtitle' => null])

<div class="md:flex md:items-center md:justify-between">
    <div class="min-w-0 flex-1">
        <h2 class="text-xl font-bold leading-7 text-gray-900 sm:text-2xl sm:truncate">
            {{ $title }}
        </h2>
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-500 hidden sm:block">{{ $subtitle }}</p>
        @endif
    </div>

    @if(isset($actions))
        {{-- Desktop: inline actions --}}
        <div class="hidden md:flex md:mt-0 md:ml-4 space-x-3">
            {{ $actions }}
        </div>
    @endif
</div>
```

**Step 2: Verify locally**

Check any page with header actions. On mobile:
- Title shows, smaller font
- Subtitle hidden
- Action buttons hidden (will be in bottom action bar)

On desktop: everything as before.

**Step 3: Commit**

```bash
git add resources/views/components/page/header.blade.php
git commit -m "feat: make page header responsive, hide actions on mobile"
```

---

### Task 4: Create Bottom Action Bar Component

**Files:**
- Create: `resources/views/components/ui/bottom-action-bar.blade.php`

**Step 1: Create the component**

This is a mobile-only bar that sits above the bottom nav and shows page-level actions. Pages opt-in by filling the slot.

```blade
{{-- resources/views/components/ui/bottom-action-bar.blade.php --}}
@aware(['mobileActions' => null])

@if(isset($slot) && $slot->isNotEmpty())
    <div class="fixed inset-x-0 z-30 bg-white border-t border-gray-200 px-4 py-2.5 shadow-sm lg:hidden"
         style="bottom: var(--bottom-nav-height);">
        <div class="flex items-center justify-end space-x-2">
            {{ $slot }}
        </div>
    </div>
@endif
```

**Step 2: Integrate into layout**

In `resources/views/layouts/app.blade.php`, add a named slot for mobile actions. Add before the `<x-ui.bottom-nav />` line:

```blade
                {{-- Bottom Action Bar (mobile only) --}}
                @if(isset($mobileActions))
                    <x-ui.bottom-action-bar>
                        {{ $mobileActions }}
                    </x-ui.bottom-action-bar>
                @endif
```

**Step 3: Verify**

The bar should not appear until a page provides the `mobileActions` slot. Verify no regressions.

**Step 4: Commit**

```bash
git add resources/views/components/ui/bottom-action-bar.blade.php resources/views/layouts/app.blade.php
git commit -m "feat: add bottom action bar component for mobile contextual actions"
```

---

## Phase 2: MML Wizard Responsive

### Task 5: Make MML Stepper Compact on Mobile

**Files:**
- Modify: `resources/views/components/mml/stepper.blade.php:68-125`

**Step 1: Update stepper for mobile**

The current stepper is a horizontal flex with full labels. On mobile, show only the step number/icon with the current step label expanded. Replace the `<nav>` section (lines 68-125):

The key changes:
- Step circles: `w-8 h-8` on mobile → `sm:w-10 sm:h-10` on desktop
- Step labels: `hidden sm:block` (only show on desktop), except the current step which always shows
- Connector lines: `hidden sm:flex` for long connectors, show small dots on mobile
- Wrap in `overflow-x-auto` for safety

```blade
<nav aria-label="Progreso de planeación" class="mb-6">
    <ol class="flex items-center justify-between sm:justify-start sm:space-x-0 w-full">
        @foreach($etapas as $i => $etapa)
            <li class="flex items-center {{ $i < count($etapas) - 1 ? 'flex-1' : '' }}">
                @if($accesible[$i + 1])
                    <a href="{{ route('mml.etapa', ['programa' => $programa, 'etapa' => $i + 1]) }}"
                       class="flex flex-col items-center group">
                @else
                    <span class="flex flex-col items-center opacity-50 cursor-not-allowed">
                @endif

                    <span class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full text-xs sm:text-sm font-semibold transition-all
                        {{ $completado[$i + 1] ? 'bg-green-600 text-white' : '' }}
                        {{ $etapaActual === $i + 1 && !$completado[$i + 1] ? 'bg-blue-600 text-white ring-2 ring-blue-300' : '' }}
                        {{ !$completado[$i + 1] && $etapaActual !== $i + 1 && $accesible[$i + 1] ? 'bg-gray-200 text-gray-600' : '' }}
                        {{ !$accesible[$i + 1] ? 'bg-gray-100 text-gray-400' : '' }}
                        {{ $accesible[$i + 1] ? 'group-hover:ring-2 group-hover:ring-offset-1 group-hover:ring-indigo-300' : '' }}">
                        @if($completado[$i + 1])
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        @elseif(!$accesible[$i + 1])
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </span>

                    {{-- Label: always show on sm+, on mobile only show for current step --}}
                    <span class="mt-1 text-[10px] sm:text-xs whitespace-nowrap {{ $etapaActual === $i + 1 ? '' : 'hidden sm:block' }} {{ $completado[$i + 1] ? 'text-green-700 font-medium' : 'text-gray-500' }}">
                        {{ $etapa }}
                    </span>

                @if($accesible[$i + 1])
                    </a>
                @else
                    </span>
                @endif

                {{-- Connector --}}
                @if($i < count($etapas) - 1)
                    <div class="flex-1 mx-1 sm:mx-2 h-0.5 {{ $completado[$i + 1] ? 'bg-green-300' : 'bg-gray-200' }}"></div>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
```

**Step 2: Verify locally**

Navigate to any MML wizard step. On mobile:
- Steps show as small circles
- Only current step label visible
- Connector lines thin but visible
- All steps touchable (min 32px target)

**Step 3: Commit**

```bash
git add resources/views/components/mml/stepper.blade.php
git commit -m "feat: make MML stepper responsive with compact mobile view"
```

---

### Task 6: Make Lista Programas Table Responsive (Priority Columns)

**Files:**
- Modify: `resources/views/livewire/mml/lista-programas.blade.php:44-111`

**Step 1: Add priority column visibility**

Key columns (high priority): Clave, Nombre, Estado, Acciones
Hidden on mobile (medium priority): Ejercicio, Origen

Update the table section. The pattern: add `hidden md:table-cell` to medium-priority `<th>` and `<td>`:

For the `<thead>` columns Ejercicio and Origen, and their corresponding `<td>` cells:

```blade
{{-- In thead --}}
<th class="hidden md:table-cell px-4 py-3 ...">Ejercicio</th>
<th class="hidden md:table-cell px-4 py-3 ...">Origen</th>

{{-- In each tbody row --}}
<td class="hidden md:table-cell px-4 py-3 ...">{{ $programa->ejercicio_fiscal }}</td>
<td class="hidden md:table-cell px-4 py-3 ...">...</td>
```

Also make the action buttons stack vertically on mobile:

```blade
<td class="px-4 py-3 text-sm text-right">
    <div class="flex flex-col sm:flex-row sm:justify-end gap-1 sm:space-x-2">
        ...links...
    </div>
</td>
```

**Step 2: Verify locally**

On mobile: table shows Clave, Nombre, Estado, Acciones (4 columns, fits well).
On desktop: all 6 columns as before.

**Step 3: Commit**

```bash
git add resources/views/livewire/mml/lista-programas.blade.php
git commit -m "feat: responsive priority columns for programs list table"
```

---

### Task 7: Make DefinicionProblema (Step 1) Mobile Friendly

**Files:**
- Modify: `resources/views/livewire/mml/definicion-problema.blade.php:1-117`

**Step 1: Add mobile actions slot**

The footer buttons (Cancel, Save, Next) should appear in the bottom action bar on mobile. Add a `mobileActions` slot to the page:

At the top of the file, add within the Livewire component root:

```blade
<x-slot:mobileActions>
    <button wire:click="guardar" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700">
        Guardar
    </button>
    <button wire:click="guardarYContinuar" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 rounded-md text-xs font-semibold text-white">
        Siguiente
    </button>
</x-slot:mobileActions>
```

**Step 2: Verify locally**

On mobile: bottom action bar shows "Guardar" and "Siguiente" buttons above bottom nav.
On desktop: original footer buttons remain.

**Step 3: Commit**

```bash
git add resources/views/livewire/mml/definicion-problema.blade.php
git commit -m "feat: add mobile action bar to MML step 1"
```

---

## Phase 3: Dashboard Mobile

### Task 8: Dashboard KPIs — 2-Column Grid on Mobile

**Files:**
- Modify: `resources/views/livewire/dashboard/partials/_planeador.blade.php:3`
- Modify: `resources/views/livewire/dashboard/partials/_admin.blade.php:3`

**Step 1: Update KPI grid breakpoints**

In `_planeador.blade.php` line 3, change:
```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
```
to:
```blade
<div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
```

In `_admin.blade.php` line 3, change:
```blade
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
```
to:
```blade
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4 mb-6">
```

**Step 2: Verify locally**

On mobile: KPIs show in 2-column grid (compact, app-like).
On desktop: 4 columns as before.

**Step 3: Commit**

```bash
git add resources/views/livewire/dashboard/partials/_planeador.blade.php resources/views/livewire/dashboard/partials/_admin.blade.php
git commit -m "feat: dashboard KPIs 2-column grid on mobile"
```

---

### Task 9: Dashboard Charts Stack on Mobile

**Files:**
- Modify: `resources/views/livewire/dashboard/partials/_planeador.blade.php:40`

**Step 1: Charts already stack on mobile**

The chart grid is `grid-cols-1 lg:grid-cols-2` (line 40), which already stacks on mobile. No change needed.

**Step 2: Reduce chart height on mobile**

If chart components accept responsive heights, pass a smaller height for mobile. Check if `x-charts.donut`, `x-charts.bar-horizontal`, `x-charts.line` support this. If they use ApexCharts, the height is fixed in JS — skip this for now and note as follow-up.

**Step 3: Commit (skip if no changes)**

---

### Task 10: Dashboard Table — Priority Columns

**Files:**
- Modify: `resources/views/livewire/dashboard/partials/_planeador.blade.php:93-116`

**Step 1: Hide low-priority columns on mobile**

The "Avances por Revisar" table has 5 columns: Indicador, Periodo, Operador, Enviado, Acción.

High priority: Indicador, Acción
Medium: Periodo
Low: Operador, Enviado

```blade
{{-- In thead --}}
<th class="px-4 py-2 ...">Indicador</th>
<th class="hidden sm:table-cell px-4 py-2 ...">Periodo</th>
<th class="hidden md:table-cell px-4 py-2 ...">Operador</th>
<th class="hidden md:table-cell px-4 py-2 ...">Enviado</th>
<th class="px-4 py-2 ...">Acción</th>

{{-- In tbody cells --}}
<td class="px-4 py-3 text-sm text-gray-900">{{ Str::limit($avance->indicador->nombre, 40) }}</td>
<td class="hidden sm:table-cell px-4 py-3 text-sm text-gray-500">{{ $avance->metaPeriodo->periodo }}</td>
<td class="hidden md:table-cell px-4 py-3 text-sm text-gray-500">{{ $avance->capturador?->name ?? '—' }}</td>
<td class="hidden md:table-cell px-4 py-3 text-sm text-gray-400">{{ $avance->updated_at->diffForHumans() }}</td>
<td class="px-4 py-3 text-sm">
    <a href="{{ route('tracking.flujo', $avance) }}" class="text-brand hover:underline">Revisar</a>
</td>
```

**Step 2: Verify locally**

On mobile: only Indicador + Acción shown.
On sm+: adds Periodo.
On md+: all columns.

**Step 3: Commit**

```bash
git add resources/views/livewire/dashboard/partials/_planeador.blade.php
git commit -m "feat: dashboard review table with priority columns for mobile"
```

---

### Task 11: Quick Links — Responsive Grid

**Files:**
- Modify: `resources/views/livewire/dashboard/partials/_admin.blade.php:30`

**Step 1: Update admin quick links grid**

Change line 30 from:
```blade
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
```
to:
```blade
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-6">
```

This gives 2 columns on mobile (compact cards) instead of stacked.

**Step 2: Commit**

```bash
git add resources/views/livewire/dashboard/partials/_admin.blade.php
git commit -m "feat: admin quick links 2-col grid on mobile"
```

---

## Phase 4: Seguimiento Tables (Priority Columns)

### Task 12: Identify and Update Seguimiento Tables

**Files:**
- Explore: `resources/views/livewire/tracking/` (all table views)

**Step 1: Identify all tables in seguimiento views**

Run: `grep -r '<table' resources/views/livewire/tracking/`

**Step 2: Apply same priority column pattern from Tasks 6 and 10**

For each table:
1. Identify high/medium/low priority columns
2. Add `hidden sm:table-cell` or `hidden md:table-cell` to medium/low columns
3. Ensure action column always visible

**Step 3: Test each view on mobile**

**Step 4: Commit**

```bash
git add resources/views/livewire/tracking/
git commit -m "feat: responsive priority columns for seguimiento tables"
```

---

## Phase 5: General Forms and Cards

### Task 13: Audit and Fix Form Layouts

**Step 1: Find all form grids that don't stack on mobile**

Run: `grep -rn 'grid-cols-2' resources/views/livewire/ --include="*.blade.php" | grep -v 'grid-cols-1'`

Any `grid-cols-2` without a preceding `grid-cols-1` at a smaller breakpoint needs fixing to `grid-cols-1 sm:grid-cols-2`.

**Step 2: Fix each occurrence**

**Step 3: Commit**

```bash
git commit -m "fix: ensure all form grids stack to single column on mobile"
```

---

### Task 14: Hide Sidebar Hamburger on Mobile (Use Bottom Nav Instead)

**Files:**
- Modify: `resources/views/components/ui/topbar.blade.php:7-11`

**Step 1: Remove mobile hamburger from topbar**

Since we now have bottom nav on mobile, the hamburger is redundant. Remove it:

Change line 7 from:
```blade
        <button @click="mobileOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700" aria-label="Abrir menu de navegacion">
```
to:
```blade
        <button @click="mobileOpen = true" class="hidden md:block lg:hidden text-gray-500 hover:text-gray-700" aria-label="Abrir menu de navegacion">
```

This keeps the hamburger visible only on tablet (md-lg) where the bottom nav might not be sufficient for deep menu access, while hiding it on phones where bottom nav is primary.

**Step 2: Verify**

- Phone (< md): no hamburger, bottom nav is primary navigation
- Tablet (md-lg): hamburger available for full sidebar access
- Desktop (lg+): no hamburger, sidebar always visible

**Step 3: Commit**

```bash
git add resources/views/components/ui/topbar.blade.php
git commit -m "feat: hide hamburger on mobile, keep for tablet, bottom nav replaces it"
```

---

## Future Sprint: Árboles de Marco Lógico (Mobile)

> **Nota:** Los árboles de problema/objetivos requieren un diseño dedicado.
> Son visualizaciones jerárquicas complejas (causa → problema → efecto)
> que no se pueden simplemente "apilar". Opciones a explorar:
>
> - Navegación por niveles (drill-down): tocar un nodo muestra sus hijos
> - Vista de lista anidada (indented tree) con expand/collapse
> - Swipe horizontal entre niveles (causas ← problema → efectos)
>
> Esto será un brainstorming separado en el siguiente sprint.

---

## Testing Checklist

After all tasks, verify on real device or Chrome DevTools:

- [ ] Bottom nav visible on mobile, hidden on desktop
- [ ] Bottom nav respects role permissions
- [ ] Active nav item highlighted correctly
- [ ] Form footer sits above bottom nav
- [ ] Page header hides actions on mobile
- [ ] MML stepper compact on mobile, full on desktop
- [ ] Programs table hides low-priority columns on mobile
- [ ] Dashboard KPIs in 2-col grid on mobile
- [ ] Dashboard review table priority columns work
- [ ] No horizontal overflow on any mobile view
- [ ] Touch targets minimum 44px
- [ ] All interactive elements reachable without scrolling to unreachable areas
