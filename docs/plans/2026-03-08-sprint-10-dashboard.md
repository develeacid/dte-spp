# Sprint 10: Dashboard Operativo — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Reemplazar el dashboard placeholder con widgets de datos reales, gráficas ApexCharts y lógica condicional por rol.

**Architecture:** Un componente Livewire `Dashboard` carga datos a través de `DashboardService` (con cache TTL 10 min). Tres componentes Blade wrapper (`x-charts.donut`, `x-charts.bar-horizontal`, `x-charts.line`) encapsulan ApexCharts con Alpine.js. Las secciones del dashboard se muestran condicionalmente según permisos del usuario con `@can`.

**Tech Stack:** Laravel 12, Livewire 3, ApexCharts (npm), Alpine.js, Tailwind CSS 3.4, Cache Laravel

**Design doc:** `docs/plans/2026-03-08-sprint-10-dashboard-design.md`

**Branch:** `feat/S10-dashboard`

**Baseline:** 439 tests, 7 skipped

---

### Task 1: Install ApexCharts & Configure JS

**Files:**
- Modify: `package.json`
- Modify: `resources/js/app.js`

**Step 1: Install ApexCharts**

Run: `./vendor/bin/sail npm install apexcharts`
Expected: Package added to `package.json`

**Step 2: Import ApexCharts in `resources/js/app.js`**

Replace the entire file:

```js
import './bootstrap';

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;
```

**Step 3: Verify Vite builds**

Run: `./vendor/bin/sail npm run build`
Expected: Build completes with no errors. JS bundle should increase in size (~130KB for ApexCharts).

**Step 4: Commit**

```bash
git add package.json package-lock.json resources/js/app.js
git commit -m "feat(S10): install ApexCharts and expose globally

ApexCharts imported in app.js and attached to window for
Alpine.js chart components."
```

---

### Task 2: Chart Blade Components

**Files:**
- Create: `resources/views/components/charts/donut.blade.php`
- Create: `resources/views/components/charts/bar-horizontal.blade.php`
- Create: `resources/views/components/charts/line.blade.php`

**Step 1: Create `x-charts.donut`**

Create `resources/views/components/charts/donut.blade.php`:

```blade
@props([
    'labels' => [],
    'series' => [],
    'colors' => ['#059669', '#F59E0B', '#DC2626'],
    'height' => 280,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$refs.chart, {
                chart: { type: 'donut', height: {{ $height }} },
                labels: @js($labels),
                series: @js($series),
                colors: @js($colors),
                legend: { position: 'bottom', fontSize: '13px' },
                dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' },
                plotOptions: { pie: { donut: { size: '55%' } } },
                responsive: [{ breakpoint: 480, options: { chart: { height: 240 }, legend: { position: 'bottom' } } }],
            });
            this.chart.render();
        },
        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
        }
     }"
     x-init="init()"
     x-on:remove="destroy()"
     {{ $attributes->merge(['class' => '']) }}>
    <div x-ref="chart"></div>
</div>
```

**Step 2: Create `x-charts.bar-horizontal`**

Create `resources/views/components/charts/bar-horizontal.blade.php`:

```blade
@props([
    'categories' => [],
    'series' => [],
    'height' => 300,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$refs.chart, {
                chart: { type: 'bar', height: {{ $height }} },
                plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 4 } },
                xaxis: { categories: @js($categories), labels: { formatter: (val) => Math.round(val) + '%' } },
                yaxis: { labels: { style: { fontSize: '12px' } } },
                series: @js($series),
                colors: ['rgb(var(--color-primary))', '#D1D5DB'],
                dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%', style: { fontSize: '11px' } },
                tooltip: { y: { formatter: (val) => Math.round(val * 10) / 10 + '%' } },
                legend: { position: 'top', fontSize: '13px' },
            });
            this.chart.render();
        },
        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
        }
     }"
     x-init="init()"
     x-on:remove="destroy()"
     {{ $attributes->merge(['class' => '']) }}>
    <div x-ref="chart"></div>
</div>
```

**Step 3: Create `x-charts.line`**

Create `resources/views/components/charts/line.blade.php`:

```blade
@props([
    'categories' => [],
    'series' => [],
    'height' => 250,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$refs.chart, {
                chart: { type: 'area', height: {{ $height }}, toolbar: { show: false }, sparkline: { enabled: false } },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
                xaxis: { categories: @js($categories) },
                yaxis: { labels: { formatter: (val) => Math.round(val) } },
                series: @js($series),
                colors: ['rgb(var(--color-primary))'],
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (val) => val + ' avances' } },
            });
            this.chart.render();
        },
        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
        }
     }"
     x-init="init()"
     x-on:remove="destroy()"
     {{ $attributes->merge(['class' => '']) }}>
    <div x-ref="chart"></div>
</div>
```

**Step 4: Verify Vite builds**

Run: `./vendor/bin/sail npm run build`
Expected: No errors

**Step 5: Commit**

```bash
git add resources/views/components/charts/
git commit -m "feat(S10): create ApexCharts Blade wrapper components

x-charts.donut: donut with labels, colors, responsive.
x-charts.bar-horizontal: grouped bars with percentage format.
x-charts.line: smooth area chart with gradient fill.
All use wire:ignore for Livewire compatibility."
```

---

### Task 3: DashboardService — Admin/Planner Stats

**Files:**
- Create: `app/Services/DashboardService.php`
- Create: `tests/Unit/Services/DashboardServiceTest.php`

**Step 1: Create `app/Services/DashboardService.php`**

```php
<?php

namespace App\Services;

use App\Enums\EstadoAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const TTL = 600; // 10 minutes

    public function getAdminStats(int $teamId): object
    {
        return Cache::remember("dashboard:admin-stats:{$teamId}", self::TTL, function () use ($teamId) {
            $programas = ProgramaPresupuestario::paraTeam($teamId)->count();

            $indicadores = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
                ->where('activo_seguimiento', true)
                ->count();

            $avancePromedio = $this->calcularAvancePromedio($teamId);

            $vencidos = MetaPeriodo::where('fecha_cierre', '<', now())
                ->where('activo', true)
                ->doesntHave('avance')
                ->whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
                ->count();

            return (object) compact('programas', 'indicadores', 'avancePromedio', 'vencidos');
        });
    }

    public function getOperadorStats(int $userId, int $teamId): object
    {
        return Cache::remember("dashboard:operador-stats:{$userId}", self::TTL, function () use ($userId) {
            $pendientes = Avance::where('capturado_por', $userId)
                ->where('estado', EstadoAvance::EN_CAPTURA)
                ->count();

            $capturadosMes = Avance::where('capturado_por', $userId)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->whereIn('estado', [EstadoAvance::EN_REVISION, EstadoAvance::APROBADO])
                ->count();

            return (object) compact('pendientes', 'capturadosMes');
        });
    }

    public function getSemaforoDistribution(int $teamId): array
    {
        return Cache::remember("dashboard:semaforo:{$teamId}", self::TTL, function () use ($teamId) {
            $counts = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
                ->whereNotNull('semaforo_calculado')
                ->select('semaforo_calculado', DB::raw('count(*) as total'))
                ->groupBy('semaforo_calculado')
                ->pluck('total', 'semaforo_calculado')
                ->toArray();

            return [
                'verde' => $counts['verde'] ?? 0,
                'amarillo' => $counts['amarillo'] ?? 0,
                'rojo' => $counts['rojo'] ?? 0,
            ];
        });
    }

    public function getSemaforoUsuario(int $userId): array
    {
        return Cache::remember("dashboard:semaforo-user:{$userId}", self::TTL, function () use ($userId) {
            $counts = Avance::where('capturado_por', $userId)
                ->whereNotNull('semaforo_calculado')
                ->select('semaforo_calculado', DB::raw('count(*) as total'))
                ->groupBy('semaforo_calculado')
                ->pluck('total', 'semaforo_calculado')
                ->toArray();

            return [
                'verde' => $counts['verde'] ?? 0,
                'amarillo' => $counts['amarillo'] ?? 0,
                'rojo' => $counts['rojo'] ?? 0,
            ];
        });
    }

    public function getAvancePorPrograma(int $teamId): Collection
    {
        return Cache::remember("dashboard:avance-programa:{$teamId}", self::TTL, function () use ($teamId) {
            $programas = ProgramaPresupuestario::paraTeam($teamId)
                ->with(['mirNiveles.indicadores' => fn ($q) => $q->where('activo_seguimiento', true)])
                ->get();

            return $programas->map(function ($programa) {
                $indicadores = $programa->mirNiveles->flatMap->indicadores;
                $totalIndicadores = $indicadores->count();

                if ($totalIndicadores === 0) {
                    return null;
                }

                $indicadorIds = $indicadores->pluck('id');

                $avances = Avance::whereIn('indicador_id', $indicadorIds)
                    ->with('metaPeriodo')
                    ->get();

                if ($avances->isEmpty()) {
                    return [
                        'programa' => $programa->clave,
                        'real' => 0,
                        'programado' => 0,
                    ];
                }

                $totalReal = 0;
                $totalMeta = 0;
                foreach ($avances as $avance) {
                    $meta = $avance->metaPeriodo?->meta_periodo ?? 0;
                    if ($meta > 0) {
                        $totalReal += ($avance->resultado / $meta) * 100;
                        $totalMeta += 100;
                    }
                }

                return [
                    'programa' => $programa->clave,
                    'real' => $totalMeta > 0 ? round($totalReal / ($totalMeta / 100), 1) : 0,
                    'programado' => 100,
                ];
            })->filter()->values();
        });
    }

    public function getTendenciaCaptura(int $teamId): Collection
    {
        return Cache::remember("dashboard:tendencia:{$teamId}", self::TTL, function () use ($teamId) {
            $desde = now()->subMonths(5)->startOfMonth();

            $raw = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
                ->where('created_at', '>=', $desde)
                ->select(
                    DB::raw("to_char(created_at, 'YYYY-MM') as mes"),
                    DB::raw('count(*) as total')
                )
                ->groupBy('mes')
                ->orderBy('mes')
                ->pluck('total', 'mes');

            // Fill missing months with 0
            $result = collect();
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $key = $month->format('Y-m');
                $result->push([
                    'mes' => $month->translatedFormat('M'),
                    'count' => $raw[$key] ?? 0,
                ]);
            }

            return $result;
        });
    }

    private function calcularAvancePromedio(int $teamId): float
    {
        $avances = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($teamId))
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
}
```

**Step 2: Create test `tests/Unit/Services/DashboardServiceTest.php`**

```php
<?php

namespace Tests\Unit\Services;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    private User $admin;

    private ProgramaPresupuestario $programa;

    private Indicador $indicador;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();

        $this->service = new DashboardService;

        $this->admin = User::factory()->withPersonalTeam()->create();
        $this->admin->assignRole('admin');
        $this->teamId = $this->admin->currentTeam->id;

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Dashboard',
            'clave' => 'PD-001',
            'team_id' => $this->teamId,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel test',
            'orden' => 1,
            'team_id' => $this->teamId,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Dashboard',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);
    }

    public function test_admin_stats_counts_programs(): void
    {
        $stats = $this->service->getAdminStats($this->teamId);

        $this->assertEquals(1, $stats->programas);
    }

    public function test_admin_stats_counts_indicators(): void
    {
        $stats = $this->service->getAdminStats($this->teamId);

        $this->assertEquals(1, $stats->indicadores);
    }

    public function test_admin_stats_average_progress(): void
    {
        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 75,
            'semaforo_calculado' => 'amarillo',
            'estado' => EstadoAvance::EN_REVISION->value,
            'capturado_por' => $this->admin->id,
        ]);

        Cache::flush();
        $stats = $this->service->getAdminStats($this->teamId);

        $this->assertEquals(75.0, $stats->avancePromedio);
    }

    public function test_admin_stats_counts_overdue(): void
    {
        MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
            'fecha_cierre' => now()->subDay(),
        ]);

        Cache::flush();
        $stats = $this->service->getAdminStats($this->teamId);

        $this->assertEquals(1, $stats->vencidos);
    }

    public function test_admin_stats_zero_when_no_data(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $stats = $this->service->getAdminStats($user->currentTeam->id);

        $this->assertEquals(0, $stats->programas);
        $this->assertEquals(0, $stats->indicadores);
        $this->assertEquals(0.0, $stats->avancePromedio);
        $this->assertEquals(0, $stats->vencidos);
    }

    public function test_operador_stats_counts_pending(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 0,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $operador->id,
        ]);

        $stats = $this->service->getOperadorStats($operador->id, $this->teamId);

        $this->assertEquals(1, $stats->pendientes);
    }

    public function test_semaforo_distribution(): void
    {
        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 95,
            'semaforo_calculado' => 'verde',
            'estado' => EstadoAvance::EN_REVISION->value,
            'capturado_por' => $this->admin->id,
        ]);

        $dist = $this->service->getSemaforoDistribution($this->teamId);

        $this->assertEquals(1, $dist['verde']);
        $this->assertEquals(0, $dist['amarillo']);
        $this->assertEquals(0, $dist['rojo']);
    }

    public function test_semaforo_distribution_empty(): void
    {
        $dist = $this->service->getSemaforoDistribution($this->teamId);

        $this->assertEquals(0, $dist['verde']);
        $this->assertEquals(0, $dist['amarillo']);
        $this->assertEquals(0, $dist['rojo']);
    }

    public function test_avance_por_programa(): void
    {
        $meta = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $this->indicador->id,
            'resultado' => 80,
            'semaforo_calculado' => 'amarillo',
            'estado' => EstadoAvance::EN_REVISION->value,
            'capturado_por' => $this->admin->id,
        ]);

        Cache::flush();
        $result = $this->service->getAvancePorPrograma($this->teamId);

        $this->assertCount(1, $result);
        $this->assertEquals('PD-001', $result[0]['programa']);
        $this->assertEquals(80.0, $result[0]['real']);
    }

    public function test_tendencia_captura_fills_missing_months(): void
    {
        $result = $this->service->getTendenciaCaptura($this->teamId);

        $this->assertCount(6, $result);
        $result->each(fn ($item) => $this->assertArrayHasKey('mes', $item));
        $result->each(fn ($item) => $this->assertArrayHasKey('count', $item));
    }

    public function test_results_are_cached(): void
    {
        $this->service->getAdminStats($this->teamId);

        // Create another program — should NOT be reflected due to cache
        ProgramaPresupuestario::create([
            'nombre' => 'Programa Nuevo',
            'clave' => 'PN-002',
            'team_id' => $this->teamId,
        ]);

        $stats = $this->service->getAdminStats($this->teamId);
        $this->assertEquals(1, $stats->programas); // Still 1, cached
    }

    public function test_team_isolation(): void
    {
        $otroUser = User::factory()->withPersonalTeam()->create();
        $otroTeamId = $otroUser->currentTeam->id;

        $stats = $this->service->getAdminStats($otroTeamId);

        $this->assertEquals(0, $stats->programas);
    }
}
```

**Step 3: Run tests**

Run: `./vendor/bin/sail artisan test --filter=DashboardServiceTest`
Expected: All 13 tests pass.

**Step 4: Commit**

```bash
git add app/Services/DashboardService.php tests/Unit/Services/DashboardServiceTest.php
git commit -m "feat(S10): create DashboardService with cached data aggregation

Admin stats, operador stats, semáforo distribution, avance por programa,
tendencia de captura. All cached with 10 min TTL. 13 unit tests."
```

---

### Task 4: Livewire Dashboard Component

**Files:**
- Create: `app/Livewire/Dashboard.php`
- Create: `resources/views/livewire/dashboard.blade.php`
- Modify: `routes/web.php` (change dashboard route)
- Delete: `resources/views/dashboard.blade.php` (replaced by Livewire view)

**Step 1: Create `app/Livewire/Dashboard.php`**

```php
<?php

namespace App\Livewire;

use App\Services\DashboardService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }

    #[Computed]
    public function adminStats(): ?object
    {
        if (! auth()->user()->can('revisar_avance')) {
            return null;
        }

        return app(DashboardService::class)->getAdminStats($this->teamId());
    }

    #[Computed]
    public function operadorStats(): ?object
    {
        if (! auth()->user()->can('capturar_avance')) {
            return null;
        }

        return app(DashboardService::class)->getOperadorStats(auth()->id(), $this->teamId());
    }

    #[Computed]
    public function semaforo(): array
    {
        if (auth()->user()->can('revisar_avance')) {
            return app(DashboardService::class)->getSemaforoDistribution($this->teamId());
        }

        if (auth()->user()->can('capturar_avance')) {
            return app(DashboardService::class)->getSemaforoUsuario(auth()->id());
        }

        return ['verde' => 0, 'amarillo' => 0, 'rojo' => 0];
    }

    #[Computed]
    public function avancePorPrograma()
    {
        if (! auth()->user()->can('revisar_avance')) {
            return collect();
        }

        return app(DashboardService::class)->getAvancePorPrograma($this->teamId());
    }

    #[Computed]
    public function tendenciaCaptura()
    {
        if (! auth()->user()->can('revisar_avance')) {
            return collect();
        }

        return app(DashboardService::class)->getTendenciaCaptura($this->teamId());
    }

    #[Computed]
    public function haySemaforoData(): bool
    {
        $s = $this->semaforo;

        return ($s['verde'] + $s['amarillo'] + $s['rojo']) > 0;
    }

    private function teamId(): int
    {
        return auth()->user()->currentTeam->id;
    }
}
```

**Step 2: Create `resources/views/livewire/dashboard.blade.php`**

```blade
<div wire:poll.60s>
    <x-page.container title="Dashboard" subtitle="SPP — Ejercicio Fiscal 2026">

        {{-- ===== Admin/Planeador Widgets ===== --}}
        @can('revisar_avance')
            @if($this->adminStats->programas > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-ui.widget title="Programas" :value="$this->adminStats->programas" subtitle="Activos en tu unidad">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>

                    <x-ui.widget title="Indicadores" :value="$this->adminStats->indicadores" subtitle="Con seguimiento activo">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>

                    <x-ui.widget title="Avance Promedio" :value="$this->adminStats->avancePromedio . '%'" subtitle="Todos los indicadores">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>

                    @if($this->adminStats->vencidos > 0)
                        <x-ui.widget title="Vencidos" :value="$this->adminStats->vencidos" subtitle="Requieren atención">
                            <x-slot:icon>
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            </x-slot:icon>
                        </x-ui.widget>
                    @endif
                </div>
            @else
                <x-ui.empty-state
                    title="No hay programas registrados"
                    description="Crea tu primer programa presupuestario para ver estadísticas aquí."
                    :actionUrl="route('mml.programas')"
                    actionLabel="Ir a Programas" />
            @endif
        @endcan

        {{-- ===== Operador Widgets ===== --}}
        @can('capturar_avance')
            @cannot('revisar_avance')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.widget title="Mis Pendientes" :value="$this->operadorStats->pendientes" subtitle="Avances por capturar">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>

                    <x-ui.widget title="Capturados este mes" :value="$this->operadorStats->capturadosMes" subtitle="Avances enviados">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>
                </div>

                @if($this->operadorStats->pendientes === 0)
                    <div class="mt-4">
                        <x-ui.empty-state
                            title="No tienes indicadores pendientes"
                            description="Todos tus avances han sido enviados." />
                    </div>
                @endif
            @endcannot
        @endcan

        {{-- ===== Gráficas ===== --}}
        @can('revisar_avance')
            @if($this->adminStats->programas > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    {{-- Semáforo Global --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Semáforo Global</h3>
                        @if($this->haySemaforoData)
                            <x-charts.donut
                                :labels="['Verde', 'Amarillo', 'Rojo']"
                                :series="[$this->semaforo['verde'], $this->semaforo['amarillo'], $this->semaforo['rojo']]"
                            />
                        @else
                            <x-ui.empty-state title="Sin datos de semáforo" description="No hay avances con semáforo calculado." />
                        @endif
                    </div>

                    {{-- Avance por Programa --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Avance por Programa</h3>
                        @if($this->avancePorPrograma->isNotEmpty())
                            <x-charts.bar-horizontal
                                :categories="$this->avancePorPrograma->pluck('programa')->toArray()"
                                :series="[
                                    ['name' => 'Real', 'data' => $this->avancePorPrograma->pluck('real')->toArray()],
                                    ['name' => 'Programado', 'data' => $this->avancePorPrograma->pluck('programado')->toArray()],
                                ]"
                                :height="max(200, $this->avancePorPrograma->count() * 60)"
                            />
                        @else
                            <x-ui.empty-state title="Sin avances capturados" description="No hay avances capturados aún." />
                        @endif
                    </div>
                </div>

                {{-- Tendencia de Captura --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Tendencia de Captura</h3>
                    <x-charts.line
                        :categories="$this->tendenciaCaptura->pluck('mes')->toArray()"
                        :series="[['name' => 'Avances capturados', 'data' => $this->tendenciaCaptura->pluck('count')->toArray()]]"
                        :height="250"
                    />
                </div>
            @endif
        @endcan

        {{-- Semáforo de operador (solo si NO es admin/planeador) --}}
        @can('capturar_avance')
            @cannot('revisar_avance')
                @if($this->haySemaforoData)
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Semáforo de Mis Indicadores</h3>
                        <x-charts.donut
                            :labels="['Verde', 'Amarillo', 'Rojo']"
                            :series="[$this->semaforo['verde'], $this->semaforo['amarillo'], $this->semaforo['rojo']]"
                        />
                    </div>
                @endif
            @endcannot
        @endcan

        {{-- ===== Actividad Reciente (Placeholder) ===== --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Actividad Reciente</h3>
            <x-ui.empty-state
                title="Próximamente"
                description="El feed de actividad reciente se implementará en un sprint futuro." />
        </div>

    </x-page.container>
</div>
```

**Step 3: Update route in `routes/web.php`**

Replace the dashboard route closure:
```php
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');
```
with:
```php
Route::get('/dashboard', \App\Livewire\Dashboard::class)->name('dashboard');
```

**Step 4: Delete old `resources/views/dashboard.blade.php`**

Run: `rm resources/views/dashboard.blade.php`

The Livewire component now serves the dashboard view from `resources/views/livewire/dashboard.blade.php`.

**Step 5: Run test suite**

Run: `./vendor/bin/sail artisan test`
Expected: All previous tests pass. The `LayoutTest` tests that hit `/dashboard` should still work since the route name and response content are compatible.

**Step 6: Commit**

```bash
git add app/Livewire/Dashboard.php resources/views/livewire/dashboard.blade.php routes/web.php
git rm resources/views/dashboard.blade.php
git commit -m "feat(S10): create Livewire Dashboard component with live data

Role-conditional widgets, charts, and empty states.
Polling every 60s. Replaces static dashboard view."
```

---

### Task 5: Feature Tests for Dashboard Component

**Files:**
- Create: `tests/Feature/DashboardTest.php`

**Step 1: Create `tests/Feature/DashboardTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Dashboard;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operador;

    private int $teamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();

        $this->admin = User::factory()->withPersonalTeam()->create();
        $this->admin->assignRole('admin');
        $this->teamId = $this->admin->currentTeam->id;

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole('operador');
    }

    private function createProgramWithAvance(string $semaforo = 'verde'): void
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $this->teamId,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel test',
            'orden' => 1,
            'team_id' => $this->teamId,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $meta = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'resultado' => 90,
            'semaforo_calculado' => $semaforo,
            'estado' => EstadoAvance::EN_REVISION->value,
            'capturado_por' => $this->admin->id,
        ]);
    }

    public function test_dashboard_renders_for_admin(): void
    {
        $this->createProgramWithAvance();

        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('Dashboard')
            ->assertSee('Programas')
            ->assertSee('Indicadores')
            ->assertSee('Avance Promedio');
    }

    public function test_dashboard_shows_real_stats(): void
    {
        $this->createProgramWithAvance();

        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('1') // 1 programa
            ->assertSee('90%'); // avance promedio
    }

    public function test_admin_sees_charts_section(): void
    {
        $this->createProgramWithAvance();

        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('Semáforo Global')
            ->assertSee('Avance por Programa')
            ->assertSee('Tendencia de Captura');
    }

    public function test_admin_empty_state_no_programs(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('No hay programas registrados')
            ->assertSee('Ir a Programas');
    }

    public function test_operador_sees_pendientes_widget(): void
    {
        $this->actingAs($this->operador);
        Livewire::test(Dashboard::class)
            ->assertSee('Mis Pendientes')
            ->assertSee('Capturados este mes');
    }

    public function test_operador_no_pendientes_empty_state(): void
    {
        $this->actingAs($this->operador);
        Livewire::test(Dashboard::class)
            ->assertSee('No tienes indicadores pendientes');
    }

    public function test_operador_does_not_see_admin_widgets(): void
    {
        $this->actingAs($this->operador);
        Livewire::test(Dashboard::class)
            ->assertDontSee('Avance Promedio')
            ->assertDontSee('Avance por Programa');
    }

    public function test_activity_placeholder_visible(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('Actividad Reciente')
            ->assertSee('Próximamente');
    }

    public function test_guest_redirected(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_vencidos_widget_shown_when_overdue(): void
    {
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Vencido',
            'clave' => 'PV-001',
            'team_id' => $this->teamId,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel',
            'orden' => 1,
            'team_id' => $this->teamId,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Vencido',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
            'fecha_cierre' => now()->subDay(),
        ]);

        $this->actingAs($this->admin);
        Livewire::test(Dashboard::class)
            ->assertSee('Vencidos')
            ->assertSee('1');
    }
}
```

**Step 2: Run dashboard feature tests**

Run: `./vendor/bin/sail artisan test --filter=DashboardTest`
Expected: All 10 tests pass.

**Step 3: Run full test suite**

Run: `./vendor/bin/sail artisan test`
Expected: 439 baseline + 13 DashboardService + 10 DashboardTest = ~462 passed, 7 skipped.

> **Note:** The existing `LayoutTest` tests reference `/dashboard` and check for sidebar rendering. Since the Livewire component still uses `layouts.app`, those tests should still pass. If `test_inter_font_loaded` or similar tests fail because the response format changed with Livewire, adjust the test to use `Livewire::test(Dashboard::class)` instead of `$this->get('/dashboard')`.

**Step 4: Commit**

```bash
git add tests/Feature/DashboardTest.php
git commit -m "test(S10): add dashboard feature tests

10 tests: admin widgets, operador widgets, empty states,
role isolation, vencidos alert, activity placeholder, guest redirect."
```

---

### Task 6: Update LayoutTest for Livewire Dashboard

**Files:**
- Modify: `tests/Feature/LayoutTest.php`

**Step 1: Check if existing LayoutTests still pass**

Run: `./vendor/bin/sail artisan test --filter=LayoutTest`

If all pass, skip this task — no changes needed. The `$this->get('/dashboard')` calls should still work with Livewire full-page components.

If any fail (e.g., the response doesn't contain expected strings because Livewire renders differently), update the failing tests to use `Livewire::test(Dashboard::class)` pattern.

**Step 2: Commit (only if changes needed)**

```bash
git add tests/Feature/LayoutTest.php
git commit -m "fix(S10): adapt LayoutTest for Livewire dashboard

Update assertions to work with Livewire full-page component rendering."
```

---

### Task 7: Final Build & Verification

**Files:**
- None (verification only)

**Step 1: Build frontend assets**

Run: `./vendor/bin/sail npm run build`
Expected: Build completes successfully. JS bundle includes ApexCharts.

**Step 2: Run full test suite**

Run: `./vendor/bin/sail artisan test`
Expected: ~462 passed, 7 skipped

**Step 3: Verify the dashboard loads in the browser**

Start the dev server and verify:
- [ ] Admin sees 4 stat widgets with real data (or empty state if no programs)
- [ ] Admin sees semáforo donut chart
- [ ] Admin sees avance por programa bar chart
- [ ] Admin sees tendencia de captura line chart
- [ ] Admin sees "Actividad Reciente — Próximamente" placeholder
- [ ] Operador sees "Mis Pendientes" and "Capturados este mes" widgets
- [ ] Operador sees semáforo donut for their indicators
- [ ] Operador does NOT see admin charts
- [ ] Empty states appear when there's no data
- [ ] Vencidos widget appears only when count > 0
- [ ] Sidebar and topbar still render correctly

**Step 4: Fix any issues found**

Adjust CSS, queries, or component props as needed.

**Step 5: Final commit (if any fixes)**

```bash
git add -A
git commit -m "fix(S10): visual refinements from dashboard review"
```

---

## Summary

| Task | Description | Files | Commit |
|------|-------------|:---:|--------|
| 1 | Install ApexCharts & JS config | 3 | `feat(S10): install ApexCharts and expose globally` |
| 2 | Chart Blade components | 3 | `feat(S10): create ApexCharts Blade wrapper components` |
| 3 | DashboardService + unit tests | 2 | `feat(S10): create DashboardService with cached data aggregation` |
| 4 | Livewire Dashboard component | 4 | `feat(S10): create Livewire Dashboard component with live data` |
| 5 | Dashboard feature tests | 1 | `test(S10): add dashboard feature tests` |
| 6 | Update LayoutTest (if needed) | 1 | `fix(S10): adapt LayoutTest for Livewire dashboard` |
| 7 | Final build & verification | 0 | `fix(S10): visual refinements from dashboard review` |

**Estimated new tests:** ~23 (13 unit + 10 feature)
**New files:** 7
**Modified files:** 3
**Deleted files:** 1
**Critical path:** T1 → T2 → T3 → T4 → T5 → T7
**Dependencies:** T3 must complete before T4 (service needed by component)
