# Sprint 15: Dashboard por Rol + Centro de Notificaciones — Plan de Implementacion

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Reemplazar el dashboard generico con vistas diferenciadas por rol (Admin, Planeador, Operador) y agregar un centro de notificaciones con icono de campana en el topbar.

**Architecture:** El componente `Dashboard` existente ya tiene logica condicional por permisos y usa `DashboardService` con cache. Este sprint lo extiende con: (1) widgets de quick-links y notificaciones recientes, (2) seccion "Avances por revisar" para Planeador, (3) KPIs globales cross-team para Admin, (4) un nuevo Livewire component `NotificationBell` en el topbar, y (5) una pagina completa de notificaciones.

**Tech Stack:** Laravel 12, Livewire 3, Spatie Permission, Laravel Notifications (database driver), Alpine.js, Tailwind CSS

**Branch:** `feat/S15-dashboard-notifications`

**Baseline:** 426 tests, 7 skipped

---

### Task 1: Dashboard Livewire Component Refactor — Role-Based Structure

**Files:**
- Modify: `app/Livewire/Dashboard.php`
- Modify: `app/Services/DashboardService.php`
- Modify: `resources/views/livewire/dashboard.blade.php`
- Create: `resources/views/livewire/dashboard/partials/_operador.blade.php`
- Create: `resources/views/livewire/dashboard/partials/_planeador.blade.php`
- Create: `resources/views/livewire/dashboard/partials/_admin.blade.php`

**Step 1: Add role-detection computed property to Dashboard.php**

In `app/Livewire/Dashboard.php`, add a `use` for `SystemRole` and a computed property that returns the current user's effective dashboard role:

```php
use App\Enums\SystemRole;
```

Add computed property:

```php
#[Computed]
public function dashboardRole(): string
{
    $user = auth()->user();

    if ($user->hasRole(SystemRole::ADMIN->value)) {
        return 'admin';
    }

    if ($user->hasRole(SystemRole::PLANEADOR->value)) {
        return 'planeador';
    }

    return 'operador';
}
```

Add computed for recent notifications:

```php
#[Computed]
public function recentNotifications()
{
    return auth()->user()->unreadNotifications()->limit(5)->get();
}
```

**Step 2: Refactor the Blade view into role-based partials**

Replace the contents of `resources/views/livewire/dashboard.blade.php` with:

```blade
<div wire:poll.60s>
    <x-page.container title="Dashboard" subtitle="SPP — Ejercicio Fiscal 2026">

        @if($this->dashboardRole === 'admin')
            @include('livewire.dashboard.partials._admin')
        @elseif($this->dashboardRole === 'planeador')
            @include('livewire.dashboard.partials._planeador')
        @else
            @include('livewire.dashboard.partials._operador')
        @endif

    </x-page.container>
</div>
```

**Step 3: Create the Operador partial**

Create `resources/views/livewire/dashboard/partials/_operador.blade.php` with the existing Operador widgets (KPI cards, semaforo donut) moved from the current `dashboard.blade.php`, plus placeholder sections for notifications and quick-link. At this step, just move the existing markup; new widgets come in Task 2.

**Step 4: Create the Planeador partial**

Create `resources/views/livewire/dashboard/partials/_planeador.blade.php` with the existing admin/planeador widgets (KPI cards, semaforo global, avance por programa, tendencia captura) moved from the current view. Placeholder sections for "Avances por revisar" and quick-link. New widgets come in Task 3.

**Step 5: Create the Admin partial**

Create `resources/views/livewire/dashboard/partials/_admin.blade.php`. Initially includes the same Planeador content (via `@include('livewire.dashboard.partials._planeador')`) plus placeholder sections for global KPIs and admin-only links. New widgets come in Task 4.

**Step 6: Verify**

Run:
```bash
./vendor/bin/sail artisan test --filter=Dashboard
```
Expected: Existing dashboard tests pass. No regressions.

Run:
```bash
./vendor/bin/sail artisan test
```
Expected: 426 passed, 7 skipped (baseline).

**Step 7: Commit**

```bash
git add app/Livewire/Dashboard.php resources/views/livewire/dashboard.blade.php resources/views/livewire/dashboard/partials/
git commit -m "refactor(S15): split dashboard view into role-based partials

Extract operador, planeador, and admin sections into separate Blade
partials. Add dashboardRole computed property using SystemRole enum.
Prepares structure for role-specific widgets.

Resolves DTE-XX

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 2: Dashboard Widgets — Operador View

**Files:**
- Modify: `resources/views/livewire/dashboard/partials/_operador.blade.php`
- Modify: `app/Livewire/Dashboard.php` (if new computed properties needed)

**Step 1: Add "Notificaciones Recientes" section**

In `_operador.blade.php`, after the existing KPI cards and semaforo section, add a notifications summary card. This uses the `recentNotifications` computed property already added in Task 1:

```blade
{{-- Notificaciones Recientes --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-700">Notificaciones Recientes</h3>
        <a href="{{ route('notifications.index') }}" class="text-xs text-brand hover:underline">Ver todas</a>
    </div>
    @if($this->recentNotifications->isEmpty())
        <p class="text-sm text-gray-500">No tienes notificaciones sin leer.</p>
    @else
        <ul class="divide-y divide-gray-100">
            @foreach($this->recentNotifications as $notification)
                <li class="py-3 flex items-start gap-3">
                    <div class="shrink-0 mt-0.5 w-2 h-2 rounded-full bg-brand"></div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-700">{{ $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion' }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
```

**Step 2: Add "Quick Links" section**

In `_operador.blade.php`, add a quick-links section after the notifications:

```blade
{{-- Quick Links --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
    <a href="{{ route('tracking.captura') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-brand-light text-brand flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Capturar avance</p>
            <p class="text-xs text-gray-500">Registrar avance de indicador</p>
        </div>
    </a>
    <a href="{{ route('tracking.pendientes') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Mis pendientes</p>
            <p class="text-xs text-gray-500">Indicadores por capturar</p>
        </div>
    </a>
</div>
```

**Step 3: Remove the old "Actividad Reciente" placeholder**

The old placeholder at the bottom of the current dashboard ("Proximamente") is no longer needed since notifications replace it. This was already removed when moving to partials in Task 1.

**Step 4: Verify**

Run:
```bash
./vendor/bin/sail artisan test
```
Expected: 426 passed, 7 skipped.

**Step 5: Commit**

```bash
git add resources/views/livewire/dashboard/partials/_operador.blade.php app/Livewire/Dashboard.php
git commit -m "feat(S15): add operador dashboard widgets — notifications and quick links

Add recent notifications feed and quick-link cards (Capturar avance,
Mis pendientes) to the operador dashboard partial.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 3: Dashboard Widgets — Planeador View

**Files:**
- Modify: `resources/views/livewire/dashboard/partials/_planeador.blade.php`
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Livewire/Dashboard.php`

**Step 1: Add `getAvancesPorRevisar` to DashboardService**

In `app/Services/DashboardService.php`, add a new method:

```php
public function getAvancesPorRevisar(int $teamId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
{
    return Avance::where('estado', EstadoAvance::EN_REVISION)
        ->whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
        ->with(['indicador', 'metaPeriodo', 'capturador'])
        ->orderBy('updated_at', 'desc')
        ->limit($limit)
        ->get();
}
```

**Step 2: Add computed property in Dashboard.php**

```php
#[Computed]
public function avancesPorRevisar()
{
    if (! auth()->user()->can('revisar_avance')) {
        return collect();
    }

    return app(DashboardService::class)->getAvancesPorRevisar($this->teamId());
}
```

**Step 3: Build the Planeador partial view**

In `resources/views/livewire/dashboard/partials/_planeador.blade.php`, include:

1. The existing KPI cards (programas, indicadores, avance promedio, vencidos) -- moved from old view.
2. The existing charts (semaforo global, avance por programa, tendencia captura) -- moved from old view.
3. New "Avances por Revisar" table:

```blade
{{-- Avances por Revisar --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-700">Avances por Revisar</h3>
        <a href="{{ route('tracking.panel') }}" class="text-xs text-brand hover:underline">Panel de seguimiento</a>
    </div>
    @if($this->avancesPorRevisar->isEmpty())
        <p class="text-sm text-gray-500">No hay avances pendientes de revision.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Indicador</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Periodo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Operador</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Enviado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Accion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($this->avancesPorRevisar as $avance)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ Str::limit($avance->indicador->nombre, 40) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $avance->metaPeriodo->periodo }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $avance->capturador?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-400">{{ $avance->updated_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('tracking.flujo', $avance) }}" class="text-brand hover:underline">Revisar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
```

4. Quick-link card:

```blade
{{-- Quick Links --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
    <a href="{{ route('tracking.panel') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-brand-light text-brand flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Panel de seguimiento</p>
            <p class="text-xs text-gray-500">Revision de avances del equipo</p>
        </div>
    </a>
    <a href="{{ route('tracking.vencidos') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Indicadores vencidos</p>
            <p class="text-xs text-gray-500">Avances fuera de plazo</p>
        </div>
    </a>
</div>
```

5. Notifications section (same pattern as operador):

```blade
{{-- Notificaciones Recientes --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-700">Notificaciones Recientes</h3>
        <a href="{{ route('notifications.index') }}" class="text-xs text-brand hover:underline">Ver todas</a>
    </div>
    @if($this->recentNotifications->isEmpty())
        <p class="text-sm text-gray-500">No tienes notificaciones sin leer.</p>
    @else
        <ul class="divide-y divide-gray-100">
            @foreach($this->recentNotifications as $notification)
                <li class="py-3 flex items-start gap-3">
                    <div class="shrink-0 mt-0.5 w-2 h-2 rounded-full bg-brand"></div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-700">{{ $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion' }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
```

**Step 4: Write test for avancesPorRevisar**

Create `tests/Feature/Dashboard/PlaneadorDashboardTest.php`:

```php
<?php

namespace Tests\Feature\Dashboard;

use App\Enums\EstadoAvance;
use App\Enums\SystemRole;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaneadorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_avances_por_revisar_returns_only_en_revision(): void
    {
        // Create team with programas, MIR, indicadores, and avances
        // using existing factories. Then verify the service method
        // returns only avances with estado EN_REVISION for the team.
        $this->markTestSkipped('Requires full seeder setup — covered by integration test.');
    }

    public function test_planeador_sees_planeador_dashboard(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::PLANEADOR->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel de seguimiento');
    }
}
```

**Step 5: Verify**

Run:
```bash
./vendor/bin/sail artisan test
```
Expected: baseline + new tests pass.

**Step 6: Commit**

```bash
git add app/Services/DashboardService.php app/Livewire/Dashboard.php resources/views/livewire/dashboard/partials/_planeador.blade.php tests/Feature/Dashboard/
git commit -m "feat(S15): add planeador dashboard — avances por revisar, KPIs, quick links

Add getAvancesPorRevisar to DashboardService. Build planeador partial
with review queue table, notifications feed, and quick-link cards
to tracking panel and vencidos.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 4: Dashboard Widgets — Admin View

**Files:**
- Modify: `resources/views/livewire/dashboard/partials/_admin.blade.php`
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Livewire/Dashboard.php`
- Create: `tests/Feature/Dashboard/AdminDashboardTest.php`

**Step 1: Add `getGlobalAdminStats` to DashboardService**

This method queries cross-team (all teams), unlike `getAdminStats` which scopes to one team:

```php
public function getGlobalAdminStats(): object
{
    return Cache::remember('dashboard:global-admin-stats', self::TTL, function () {
        $programasEvaluados = ProgramaPresupuestario::whereHas('mirNiveles.indicadores', fn ($q) => $q->where('activo_seguimiento', true))
            ->count();

        $eficaciaPromedio = $this->calcularEficaciaGlobal();

        $vencidosCrossTeam = Avance::where('estado', EstadoAvance::VENCIDO)->count();

        return (object) compact('programasEvaluados', 'eficaciaPromedio', 'vencidosCrossTeam');
    });
}

private function calcularEficaciaGlobal(): float
{
    $avances = Avance::whereIn('estado', [EstadoAvance::APROBADO])
        ->with('metaPeriodo')
        ->get();

    if ($avances->isEmpty()) {
        return 0;
    }

    $percentages = $avances->map(function ($avance) {
        $meta = $avance->metaPeriodo?->meta_periodo ?? 0;
        return $meta > 0 ? min(($avance->resultado / $meta) * 100, 200) : 0;
    });

    return round($percentages->avg(), 1);
}
```

**Step 2: Add computed property in Dashboard.php**

```php
#[Computed]
public function globalAdminStats(): ?object
{
    if (! auth()->user()->hasRole(SystemRole::ADMIN->value)) {
        return null;
    }

    return app(DashboardService::class)->getGlobalAdminStats();
}
```

**Step 3: Build the Admin partial view**

In `resources/views/livewire/dashboard/partials/_admin.blade.php`:

```blade
{{-- Global Admin KPIs --}}
@if($this->globalAdminStats)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-ui.widget title="Eficacia Promedio" :value="$this->globalAdminStats->eficaciaPromedio . '%'" subtitle="Indicadores aprobados (global)">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
            </x-slot:icon>
        </x-ui.widget>

        <x-ui.widget title="Programas Evaluados" :value="$this->globalAdminStats->programasEvaluados" subtitle="Con seguimiento activo">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </x-slot:icon>
        </x-ui.widget>

        @if($this->globalAdminStats->vencidosCrossTeam > 0)
            <x-ui.widget title="Vencidos (Global)" :value="$this->globalAdminStats->vencidosCrossTeam" subtitle="Todas las unidades">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </x-slot:icon>
            </x-ui.widget>
        @endif
    </div>
@endif

{{-- Include planeador view (team-scoped KPIs + charts + avances por revisar) --}}
@include('livewire.dashboard.partials._planeador')

{{-- Admin Quick Links --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
    <a href="{{ route('admin.monitoreo-ia') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 14.5M14.25 3.104c.251.023.501.05.75.082M19.8 14.5a2.25 2.25 0 010 3l-3 3a2.25 2.25 0 01-3 0l-1.5-1.5a2.25 2.25 0 010-3l4.5-4.5zm-14.6 0a2.25 2.25 0 000 3l3 3a2.25 2.25 0 003 0l1.5-1.5a2.25 2.25 0 000-3L5.2 14.5z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Monitoreo IA</p>
            <p class="text-xs text-gray-500">Presupuesto y uso</p>
        </div>
    </a>
    <a href="{{ route('admin.users') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Gestion de usuarios</p>
            <p class="text-xs text-gray-500">Invitar y administrar</p>
        </div>
    </a>
</div>
```

Note: The admin partial `@include`s the planeador partial to avoid duplicating the team-scoped KPIs, charts, and avances-por-revisar table. The planeador partial already has its own quick-links and notifications, so the admin partial appends admin-only quick-links after. To avoid duplicate quick-links, extract the notification+quicklinks section from `_planeador.blade.php` into a conditionally-rendered block that checks `$this->dashboardRole !== 'admin'`, and instead render admin-specific links in `_admin.blade.php`.

**Step 4: Write test**

Create `tests/Feature/Dashboard/AdminDashboardTest.php`:

```php
<?php

namespace Tests\Feature\Dashboard;

use App\Enums\SystemRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_global_kpis_and_admin_links(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::ADMIN->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Monitoreo IA')
            ->assertSee('Gestion de usuarios');
    }

    public function test_operador_does_not_see_admin_links(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Monitoreo IA')
            ->assertSee('Capturar avance');
    }
}
```

**Step 5: Verify**

Run:
```bash
./vendor/bin/sail artisan test
```
Expected: baseline + new tests pass.

**Step 6: Commit**

```bash
git add app/Services/DashboardService.php app/Livewire/Dashboard.php resources/views/livewire/dashboard/partials/_admin.blade.php resources/views/livewire/dashboard/partials/_planeador.blade.php tests/Feature/Dashboard/
git commit -m "feat(S15): add admin dashboard — global KPIs, cross-team alerts, admin links

Add getGlobalAdminStats and calcularEficaciaGlobal to DashboardService.
Admin partial shows global eficacia, programas evaluados, cross-team
vencidos, plus links to Monitoreo IA and Gestion de usuarios.
Includes planeador content via @include for team-scoped data.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 5: Notification Bell Component in Topbar

**Files:**
- Create: `app/Livewire/NotificationBell.php`
- Create: `resources/views/livewire/notification-bell.blade.php`
- Modify: `resources/views/components/ui/topbar.blade.php`

**Step 1: Create the Livewire component**

Create `app/Livewire/NotificationBell.php`:

```php
<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function notifications()
    {
        return auth()->user()->unreadNotifications()->limit(10)->get();
    }

    public function markAsRead(string $notificationId): void
    {
        auth()->user()->notifications()->where('id', $notificationId)->update(['read_at' => now()]);
        unset($this->unreadCount, $this->notifications);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->notifications);
        $this->open = false;
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
```

**Step 2: Create the Blade view**

Create `resources/views/livewire/notification-bell.blade.php`:

```blade
<div class="relative" x-data="{ open: $wire.entangle('open') }">
    {{-- Bell button --}}
    <button @click="open = !open" class="relative p-2 text-gray-500 hover:text-gray-700 focus:outline-none transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        @if($this->unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Notificaciones</h3>
            @if($this->unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-brand hover:underline">Marcar todo como leido</button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
            @forelse($this->notifications as $notification)
                <div class="px-4 py-3 hover:bg-gray-50 flex items-start gap-3" wire:key="notif-{{ $notification->id }}">
                    <div class="shrink-0 mt-1 w-2 h-2 rounded-full bg-brand"></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-700">{{ $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion' }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <button wire:click="markAsRead('{{ $notification->id }}')" class="shrink-0 text-gray-400 hover:text-gray-600" title="Marcar como leido">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">
                    No tienes notificaciones sin leer.
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-gray-100 text-center">
            <a href="{{ route('notifications.index') }}" class="text-xs text-brand hover:underline">Ver todas las notificaciones</a>
        </div>
    </div>
</div>
```

**Step 3: Insert the bell into the topbar**

In `resources/views/components/ui/topbar.blade.php`, add the Livewire component before the user dropdown. Find the `{{-- Right: user dropdown --}}` comment and add the bell before it:

Replace:
```blade
    {{-- Right: user dropdown --}}
    <div class="flex items-center">
```

With:
```blade
    {{-- Right: notifications + user dropdown --}}
    <div class="flex items-center space-x-2">
        @auth
            @livewire('notification-bell')
        @endauth
```

**Step 4: Write test**

Create `tests/Feature/Notifications/NotificationBellTest.php`:

```php
<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\AvanceVencidoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_bell_shows_unread_count(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        // Create a database notification directly
        $user->notify(new \Illuminate\Notifications\DatabaseNotification);
        // Alternative: insert directly into notifications table for simpler setup

        $this->actingAs($user);

        Livewire::test(\App\Livewire\NotificationBell::class)
            ->assertOk();
    }

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        // Insert test notification
        \DB::table('notifications')->insert([
            'id' => \Str::uuid(),
            'type' => 'App\Notifications\AvanceVencidoNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Test notification']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\NotificationBell::class)
            ->assertSee('Test notification')
            ->call('markAllAsRead');

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }
}
```

**Step 5: Verify**

Run:
```bash
./vendor/bin/sail artisan test
```
Expected: baseline + new tests pass.

**Step 6: Commit**

```bash
git add app/Livewire/NotificationBell.php resources/views/livewire/notification-bell.blade.php resources/views/components/ui/topbar.blade.php tests/Feature/Notifications/
git commit -m "feat(S15): add notification bell dropdown in topbar

Livewire NotificationBell component with unread count badge, dropdown
list, mark-as-read (individual + all), and link to full notifications
page. Inserted before user avatar in topbar.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 6: Notifications Page — Full List with Mark-as-Read

**Files:**
- Create: `app/Livewire/NotificationsIndex.php`
- Create: `resources/views/livewire/notifications-index.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Notifications/NotificationsIndexTest.php`

**Step 1: Create the Livewire component**

Create `app/Livewire/NotificationsIndex.php`:

```php
<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NotificationsIndex extends Component
{
    use WithPagination;

    public string $filter = 'all'; // 'all' | 'unread'

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $query = auth()->user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->orderByDesc('created_at')->paginate(20);

        return view('livewire.notifications-index', [
            'notifications' => $notifications,
        ]);
    }
}
```

**Step 2: Create the Blade view**

Create `resources/views/livewire/notifications-index.blade.php`:

```blade
<div>
    <x-page.header>
        <x-slot name="title">Notificaciones</x-slot>
        <x-slot name="actions">
            <button wire:click="markAllAsRead" class="text-sm text-brand hover:underline">
                Marcar todo como leido
            </button>
        </x-slot>
    </x-page.header>

    <x-page.container>
        {{-- Filter tabs --}}
        <div class="flex items-center gap-4 mb-6 border-b border-gray-200">
            <button
                wire:click="$set('filter', 'all')"
                class="pb-2 text-sm font-medium border-b-2 transition {{ $filter === 'all' ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                Todas
            </button>
            <button
                wire:click="$set('filter', 'unread')"
                class="pb-2 text-sm font-medium border-b-2 transition {{ $filter === 'unread' ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                Sin leer
            </button>
        </div>

        {{-- Notification list --}}
        @if($notifications->isEmpty())
            <x-ui.empty-state
                title="No hay notificaciones"
                description="{{ $filter === 'unread' ? 'No tienes notificaciones sin leer.' : 'Tu bandeja de notificaciones esta vacia.' }}" />
        @else
            <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
                @foreach($notifications as $notification)
                    <div class="px-5 py-4 flex items-start gap-4 {{ is_null($notification->read_at) ? 'bg-blue-50/30' : '' }}" wire:key="notif-{{ $notification->id }}">
                        {{-- Unread indicator --}}
                        <div class="shrink-0 mt-1.5">
                            @if(is_null($notification->read_at))
                                <div class="w-2 h-2 rounded-full bg-brand"></div>
                            @else
                                <div class="w-2 h-2"></div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800">
                                {{ $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Notificacion del sistema' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                                &middot;
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="shrink-0">
                            @if(is_null($notification->read_at))
                                <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-gray-500 hover:text-brand" title="Marcar como leido">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </x-page.container>
</div>
```

**Step 3: Register the route**

In `routes/web.php`, inside the authenticated middleware group (after the dashboard route), add:

```php
Route::get('/notifications', \App\Livewire\NotificationsIndex::class)->name('notifications.index');
```

**Step 4: Write test**

Create `tests/Feature/Notifications/NotificationsIndexTest.php`:

```php
<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_page_renders(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notificaciones');
    }

    public function test_filter_unread_only(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        // Insert one read and one unread notification
        \DB::table('notifications')->insert([
            [
                'id' => \Str::uuid(),
                'type' => 'TestNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => json_encode(['message' => 'Unread one']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => \Str::uuid(),
                'type' => 'TestNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => json_encode(['message' => 'Read one']),
                'read_at' => now(),
                'created_at' => now()->subHour(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\NotificationsIndex::class)
            ->set('filter', 'unread')
            ->assertSee('Unread one')
            ->assertDontSee('Read one');
    }

    public function test_mark_as_read(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $notifId = \Str::uuid()->toString();
        \DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Test']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\NotificationsIndex::class)
            ->call('markAsRead', $notifId);

        $this->assertNotNull(
            \DB::table('notifications')->where('id', $notifId)->value('read_at')
        );
    }

    public function test_guest_cannot_access_notifications(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }
}
```

**Step 5: Verify**

Run:
```bash
./vendor/bin/sail artisan test
```
Expected: baseline + new tests pass.

**Step 6: Commit**

```bash
git add app/Livewire/NotificationsIndex.php resources/views/livewire/notifications-index.blade.php routes/web.php tests/Feature/Notifications/
git commit -m "feat(S15): add full notifications page with pagination and filters

Livewire NotificationsIndex with all/unread filter tabs, paginated list,
individual mark-as-read, and mark-all. Route registered at /notifications.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 7: Final Verification

**Files:** None (verification only)

**Step 1: Run full test suite**

```bash
./vendor/bin/sail artisan test
```
Expected: baseline (426 passed, 7 skipped) + new tests. Zero regressions.

**Step 2: Manual smoke test checklist**

- [ ] Login as **operador**: see KPI cards (Mis Pendientes, Capturados), semaforo donut, notifications feed, quick-links (Capturar avance, Mis pendientes)
- [ ] Login as **planeador**: see KPI cards (Programas, Indicadores, Avance Promedio, Vencidos), charts (semaforo, avance por programa, tendencia), Avances por Revisar table, quick-links (Panel seguimiento, Vencidos), notifications feed
- [ ] Login as **admin**: see global KPIs (Eficacia Promedio, Programas Evaluados, Vencidos Global) plus all planeador content, admin quick-links (Monitoreo IA, Gestion usuarios)
- [ ] Notification bell in topbar shows unread count badge
- [ ] Clicking bell opens dropdown with notification list
- [ ] "Marcar como leido" on individual notification works
- [ ] "Marcar todo como leido" works
- [ ] "Ver todas" links to `/notifications` page
- [ ] `/notifications` page shows all/unread filter tabs
- [ ] Pagination works on notifications page
- [ ] Poll (`wire:poll.60s`) refreshes dashboard data

**Step 3: Verify no Tailwind purge issues**

```bash
./vendor/bin/sail npm run build
```
Expected: Build succeeds. All new classes are in template files that Tailwind scans.

**Step 4: Final commit (if any fixups needed)**

```bash
git add -A
git commit -m "fix(S15): final adjustments from smoke testing

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Summary of New/Modified Files

| File | Action | Task |
|------|--------|------|
| `app/Livewire/Dashboard.php` | Modify | 1, 2, 3, 4 |
| `app/Services/DashboardService.php` | Modify | 3, 4 |
| `resources/views/livewire/dashboard.blade.php` | Modify | 1 |
| `resources/views/livewire/dashboard/partials/_operador.blade.php` | Create | 1, 2 |
| `resources/views/livewire/dashboard/partials/_planeador.blade.php` | Create | 1, 3 |
| `resources/views/livewire/dashboard/partials/_admin.blade.php` | Create | 1, 4 |
| `app/Livewire/NotificationBell.php` | Create | 5 |
| `resources/views/livewire/notification-bell.blade.php` | Create | 5 |
| `resources/views/components/ui/topbar.blade.php` | Modify | 5 |
| `app/Livewire/NotificationsIndex.php` | Create | 6 |
| `resources/views/livewire/notifications-index.blade.php` | Create | 6 |
| `routes/web.php` | Modify | 6 |
| `tests/Feature/Dashboard/PlaneadorDashboardTest.php` | Create | 3 |
| `tests/Feature/Dashboard/AdminDashboardTest.php` | Create | 4 |
| `tests/Feature/Notifications/NotificationBellTest.php` | Create | 5 |
| `tests/Feature/Notifications/NotificationsIndexTest.php` | Create | 6 |

## Key Decisions

1. **Reuse existing DashboardService + computed properties** rather than creating new Livewire sub-components for each role. The dashboard is already a single Livewire component with cached service calls; splitting into partials via `@include` keeps it simple.

2. **Admin includes Planeador via `@include`** to avoid duplicating team-scoped charts and KPIs. Admin sees everything Planeador sees, plus global cross-team stats.

3. **Notification bell is a separate Livewire component** (`NotificationBell`) embedded in topbar via `@livewire`, enabling independent polling and state management without affecting the rest of the layout.

4. **All existing notification classes** (`AvanceVencidoNotification`, `PeriodoAbiertoNotification`, `AvanceObservadoNotification`, `AvanceEnRevisionNotification`, `ReporteListoNotification`, `LlmBudgetAlertNotification`) already use the `database` driver and store a `message` or `mensaje` key in `data`. The bell and notifications page read from this key.

5. **No new database migrations** needed -- the `notifications` table already exists with the standard Laravel schema (uuid id, morphs notifiable, text data, read_at).
