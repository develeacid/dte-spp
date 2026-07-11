# Página de Ayuda: Glosario + Marco Normativo (M01 req 7 + 27) — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Publicar una página `/ayuda` con dos secciones didácticas (Marco Normativo desde `CatalogoOrdenamiento` + Glosario desde markdown), accesible a todos los roles.

**Architecture:** Controlador invocable + vista Blade estática (no Livewire). El glosario se renderiza con `Str::markdown()` desde `resources/markdown/glosario-mir.md`; los ordenamientos se agrupan por `nivel_jerarquia`. Ítem en el sidebar.

**Tech Stack:** Laravel 12, Blade, Alpine, league/commonmark (`Str::markdown`), Sail/PHPUnit.

**Design doc:** `docs/plans/2026-07-11-ayuda-glosario-design.md`
**Rama:** `feat/ayuda-glosario` (creada, design doc commiteado)
**Comando de tests:** `./vendor/bin/sail artisan test --filter=<X>`

---

## Task 1: Ruta + controlador + contenido + vista

**Files:**
- Create: `resources/markdown/glosario-mir.md` (copia de `docs/sistema/fuente-de-verdad/glosario_MIR.md`)
- Create: `app/Http/Controllers/AyudaController.php`
- Create: `resources/views/ayuda/index.blade.php`
- Modify: `routes/web.php` (grupo `auth`, junto a `dashboard`)
- Test: `tests/Feature/AyudaPageTest.php`

**Step 1: Write the failing test**

Crear `tests/Feature/AyudaPageTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\SystemRole;
use App\Models\User;
use Database\Seeders\CatalogoOrdenamientosSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AyudaPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CatalogoOrdenamientosSeeder::class);
    }

    public function test_usuario_autenticado_ve_ayuda(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/ayuda');

        $response->assertOk()
            ->assertSee('Glosario')
            ->assertSee('Marco Normativo')
            ->assertSee('Administración Pública')
            ->assertSee('Ley Federal de Presupuesto y Responsabilidad Hacendaria');
    }

    public function test_operador_sin_permisos_especiales_ve_ayuda(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        $this->actingAs($user)->get('/ayuda')->assertOk();
    }

    public function test_guest_redirige_a_login(): void
    {
        $this->get('/ayuda')->assertRedirect('/login');
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=AyudaPageTest`
Expected: FAIL (ruta `/ayuda` no existe → 404).

**Step 3: Copiar el glosario a resources/**

```bash
mkdir -p resources/markdown
cp docs/sistema/fuente-de-verdad/glosario_MIR.md resources/markdown/glosario-mir.md
```

**Step 4: Crear el controlador**

`app/Http/Controllers/AyudaController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Juridico\CatalogoOrdenamiento;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AyudaController extends Controller
{
    public function __invoke(): View
    {
        $ordenamientos = CatalogoOrdenamiento::where('activo', true)
            ->orderBy('orden')
            ->get()
            ->groupBy(fn (CatalogoOrdenamiento $o) => $o->nivel_jerarquia->label());

        $glosarioHtml = Str::markdown(
            file_get_contents(resource_path('markdown/glosario-mir.md'))
        );

        return view('ayuda.index', compact('ordenamientos', 'glosarioHtml'));
    }
}
```

> Nota: si `CatalogoOrdenamiento` scope `activo` no existe como columna, revisar el fillable (`['nombre','nivel_jerarquia','abreviatura','activo','orden']` — sí existe). Si el seeder no setea `activo`, usar `->orderBy('orden')` sin el where.

**Step 5: Registrar la ruta**

En `routes/web.php`, dentro del grupo `auth:sanctum`, agregar (y `use App\Http\Controllers\AyudaController;` al head):

```php
Route::get('/ayuda', AyudaController::class)->name('ayuda');
```

**Step 6: Crear la vista**

`resources/views/ayuda/index.blade.php`:

```blade
<x-page.container title="Ayuda" subtitle="Glosario de términos y marco normativo del PbR-SED">
    <div x-data="{ tab: 'marco' }" class="space-y-4">
        <div class="flex gap-2 border-b border-gray-200">
            <button @click="tab = 'marco'"
                :class="tab === 'marco' ? 'border-brand text-brand-dark' : 'border-transparent text-gray-500'"
                class="border-b-2 px-3 py-2 text-sm font-medium">
                Marco Normativo
            </button>
            <button @click="tab = 'glosario'"
                :class="tab === 'glosario' ? 'border-brand text-brand-dark' : 'border-transparent text-gray-500'"
                class="border-b-2 px-3 py-2 text-sm font-medium">
                Glosario
            </button>
        </div>

        {{-- Marco Normativo (M01 req 7) --}}
        <div x-show="tab === 'marco'" class="space-y-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 space-y-2">
                <p>El sistema opera bajo el modelo de <strong>Presupuesto basado en Resultados (PbR)</strong> y el <strong>Sistema de Evaluación del Desempeño (SED)</strong>, sustentados en:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>LFPRH art. 111</strong> — obliga al SED y a los indicadores de desempeño del gasto federalizado.</li>
                    <li><strong>LGCG art. 46-III-C</strong> — exige la información programática con indicadores de resultados.</li>
                    <li><strong>Lineamientos SHCP-CONEVAL</strong> — metodología de la MIR y construcción de indicadores.</li>
                </ul>
            </div>

            @foreach ($ordenamientos as $jerarquia => $items)
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $jerarquia }}</h3>
                    <ul class="mt-2 space-y-1">
                        @foreach ($items as $o)
                            <li class="text-sm text-gray-800">
                                <span class="font-mono font-semibold text-gray-600">{{ $o->abreviatura }}</span>
                                — {{ $o->nombre }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        {{-- Glosario (M01 req 27) --}}
        <div x-show="tab === 'glosario'" x-cloak class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="prose prose-sm max-w-none">
                {!! $glosarioHtml !!}
            </div>
        </div>
    </div>
</x-page.container>
```

> Nota: el HTML del glosario es contenido propio (no input de usuario) → `{!! !!}` es seguro. Verificar que `x-page.container` acepte `title`/`subtitle` (patrón usado en `padron-programa.blade.php`).

**Step 7: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=AyudaPageTest`
Expected: PASS (3 tests).

**Step 8: Commit**

```bash
git add resources/markdown/glosario-mir.md app/Http/Controllers/AyudaController.php resources/views/ayuda/index.blade.php routes/web.php tests/Feature/AyudaPageTest.php
git commit -m "feat(ayuda): página Ayuda con glosario y marco normativo (M01 req 7+27)"
```

---

## Task 2: Ítem en el sidebar

**Files:**
- Modify: `resources/views/components/layout/sidebar-nav.blade.php` (al final)

**Step 1: Agregar el ítem**

Al final de `sidebar-nav.blade.php`, agregar un ítem standalone:

```blade
{{-- Ayuda --}}
<x-ui.sidebar-item href="{{ route('ayuda') }}" :active="request()->routeIs('ayuda')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/></svg>
    </x-slot:icon>
    Ayuda
</x-ui.sidebar-item>
```

**Step 2: Verificación visual rápida**

Run: `grep -n "Ayuda" resources/views/components/layout/sidebar-nav.blade.php`
Expected: aparece el ítem.

**Step 3: Commit**

```bash
git add resources/views/components/layout/sidebar-nav.blade.php
git commit -m "feat(ayuda): ítem Ayuda en el sidebar"
```

---

## Task 3: Regresión + verificación

**Step 1: Suite relevante**

Run: `./vendor/bin/sail artisan test --filter="Ayuda|SidebarNav|RouteAccess"`
Expected: PASS.

**Step 2: Pint**

Run: `./vendor/bin/sail bin pint --dirty`
Expected: passed / auto-fix commiteable.

**Step 3: Browser checkpoint**

Con Vite dev corriendo: login → click "Ayuda" en el sidebar → confirmar:
- Tab "Marco Normativo": intro con LFPRH art.111 / LGCG art.46-III-C + ordenamientos agrupados por jerarquía.
- Tab "Glosario": contenido renderizado (secciones del glosario_MIR.md) con estilo `prose`.

**Step 4: Suite completa (baseline)**

Run: `./vendor/bin/sail artisan test`
Expected: baseline + tests nuevos, 0 fallos nuevos.

---

## Notas de cierre

- Sin migraciones, sin BD pública. `CatalogoOrdenamiento` ya está seedeado (Fase catálogos).
- Actualizar `docs/sistema/brechas/README.md`: marcar **M01 req 7** (parcial, §3) y **M01 req 27** como ✅ RESUELTA + entrada en §6.
- PR a `desarrollo`.
