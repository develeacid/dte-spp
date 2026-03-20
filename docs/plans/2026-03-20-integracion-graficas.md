# Integración de Gráficas en Módulos SPP — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Integrar 10 componentes de gráficas existentes en 8 vistas de los módulos Seguimiento, Evaluación y Presupuesto.

**Architecture:** Las gráficas se insertan en las vistas blade existentes reutilizando las propiedades Livewire ya expuestas ($filas, $metricas, $tablero, etc.). Los datos se transforman con `@php` inline en el blade. Cero cambios en backend.

**Tech Stack:** Blade components (x-charts.*), ApexCharts, D3.js v7, Alpine.js, Livewire 3

---

## Task 1: Panel de Seguimiento — Gráficas de resumen

**Files:**
- Modify: `resources/views/livewire/tracking/panel-seguimiento.blade.php` (insertar después de línea ~42, antes de la tabla)

**Step 1: Agregar sección de gráficas entre filtros y tabla**

Insertar después del cierre del `</div>` de filtros (línea ~42) y antes del `<div class="overflow-x-auto">` de la tabla:

```blade
{{-- Resumen visual --}}
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2" wire:ignore>
    @php
        $semaforoCounts = collect($filas)->countBy('semaforo');
        $semaforoLabels = ['verde', 'amarillo', 'rojo', 'gris'];
        $semaforoSeries = collect($semaforoLabels)->map(fn ($s) => $semaforoCounts->get($s, 0))->values()->toArray();
        $semaforoColors = ['#22c55e', '#eab308', '#ef4444', '#9ca3af'];
    @endphp
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Semáforo de Indicadores</h4>
        <x-charts.donut
            :labels="$semaforoLabels"
            :series="$semaforoSeries"
            :colors="$semaforoColors"
            :height="220"
            centerText="{{ array_sum($semaforoSeries) }}"
            centerSubtext="indicadores"
        />
    </div>

    @php
        $porPrograma = collect($filas)->groupBy('programa_clave')->map(function ($items, $clave) {
            $conMeta = $items->filter(fn ($i) => $i['meta'] > 0);
            return [
                'clave' => $clave,
                'avance' => $conMeta->count() > 0
                    ? round($conMeta->avg(fn ($i) => min(($i['resultado'] / $i['meta']) * 100, 150)), 1)
                    : 0,
            ];
        })->sortByDesc('avance')->values();
    @endphp
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Avance Promedio por Programa</h4>
        <x-charts.bar-horizontal
            :categories="$porPrograma->pluck('clave')->toArray()"
            :series="[['name' => 'Avance %', 'data' => $porPrograma->pluck('avance')->toArray()]]"
            :height="max(200, $porPrograma->count() * 35)"
            :referenceLine="100"
            referenceLabel="Meta"
        />
    </div>
</div>
```

**Step 2: Verificar visualmente**

Run: Navegar a `http://localhost/seguimiento` con sesión activa.
Expected: Dos gráficas (donut + bar-horizontal) visibles antes de la tabla.

**Step 3: Commit**

```bash
git add resources/views/livewire/tracking/panel-seguimiento.blade.php
git commit -m "feat(tracking): add semaforo donut and avance bar charts to panel-seguimiento"
```

---

## Task 2: Sábana de Captura — Donut de estados

**Files:**
- Modify: `resources/views/livewire/tracking/sabana-captura.blade.php` (insertar después de filtros, antes de la tabla)

**Step 1: Agregar donut de distribución de estados**

Insertar después de los filtros y antes de la tabla:

```blade
{{-- Resumen visual de estados --}}
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3" wire:ignore>
    @php
        $estadoCounts = collect($filas)->countBy('estado');
        $estadoLabels = ['aprobado', 'en_revision', 'en_captura', 'observado', 'pendiente', 'vencido'];
        $estadoSeries = collect($estadoLabels)->map(fn ($e) => $estadoCounts->get($e, 0))->toArray();
        $estadoColors = ['#22c55e', '#3b82f6', '#eab308', '#f97316', '#9ca3af', '#ef4444'];
    @endphp
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 lg:col-span-1">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución de Estados</h4>
        <x-charts.donut
            :labels="$estadoLabels"
            :series="$estadoSeries"
            :colors="$estadoColors"
            :height="220"
            centerText="{{ array_sum($estadoSeries) }}"
            centerSubtext="registros"
        />
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
        @php
            $porProgramaEstado = collect($filas)->groupBy('programa_clave')->map(function ($items, $clave) {
                return [
                    'clave' => $clave,
                    'aprobados' => $items->where('estado', 'aprobado')->count(),
                    'pendientes' => $items->whereIn('estado', ['pendiente', 'en_captura', 'en_revision'])->count(),
                    'problemas' => $items->whereIn('estado', ['observado', 'vencido'])->count(),
                ];
            })->values();
        @endphp
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Estado por Programa</h4>
        <x-charts.bar-horizontal
            :categories="$porProgramaEstado->pluck('clave')->toArray()"
            :series="[
                ['name' => 'Aprobados', 'data' => $porProgramaEstado->pluck('aprobados')->toArray()],
                ['name' => 'En proceso', 'data' => $porProgramaEstado->pluck('pendientes')->toArray()],
                ['name' => 'Observado/Vencido', 'data' => $porProgramaEstado->pluck('problemas')->toArray()],
            ]"
            :height="max(200, $porProgramaEstado->count() * 40)"
        />
    </div>
</div>
```

**Step 2: Verificar visualmente**

Run: Navegar a `http://localhost/seguimiento/sabana`.
Expected: Donut de estados + bar-horizontal por programa visibles antes de la tabla.

**Step 3: Commit**

```bash
git add resources/views/livewire/tracking/sabana-captura.blade.php
git commit -m "feat(tracking): add estado donut and program breakdown to sabana-captura"
```

---

## Task 3: Concentrado de Captura — Gráficas de resumen

**Files:**
- Modify: `resources/views/livewire/tracking/concentrado-captura.blade.php` (insertar después de las tarjetas métricas ~línea 60, antes de la tabla agrupada)

**Step 1: Agregar gráficas después de métricas**

Insertar después del cierre del grid de métricas y antes de la tabla:

```blade
{{-- Resumen visual --}}
<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2" wire:ignore>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución Global</h4>
        <x-charts.donut
            :labels="['Aprobados', 'En revisión', 'En captura', 'Observados']"
            :series="[$metricas['aprobados'], $metricas['en_revision'], $metricas['en_captura'], $metricas['observados']]"
            :colors="['#22c55e', '#3b82f6', '#eab308', '#f97316']"
            :height="220"
            centerText="{{ $metricas['total'] }}"
            centerSubtext="total"
        />
    </div>

    @php
        $programasConcentrado = $agrupado->map(function ($items, $clave) {
            return [
                'clave' => $clave,
                'aprobados' => $items->sum('aprobados'),
                'en_revision' => $items->sum('en_revision'),
                'en_captura' => $items->sum('en_captura'),
                'observados' => $items->sum('observados'),
            ];
        })->values();
    @endphp
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Comparativa por Programa</h4>
        <x-charts.bar-grouped
            :categories="$programasConcentrado->pluck('clave')->toArray()"
            :series="[
                ['name' => 'Aprobados', 'data' => $programasConcentrado->pluck('aprobados')->toArray()],
                ['name' => 'En revisión', 'data' => $programasConcentrado->pluck('en_revision')->toArray()],
                ['name' => 'En captura', 'data' => $programasConcentrado->pluck('en_captura')->toArray()],
            ]"
            :colors="['#22c55e', '#3b82f6', '#eab308']"
            :height="280"
        />
    </div>
</div>
```

**Step 2: Verificar visualmente**

Run: Navegar a `http://localhost/seguimiento/concentrado`.
Expected: Donut global + bar-grouped por programa visibles.

**Step 3: Commit**

```bash
git add resources/views/livewire/tracking/concentrado-captura.blade.php
git commit -m "feat(tracking): add summary charts to concentrado-captura"
```

---

## Task 4: Indicadores Vencidos — Bar chart por programa

**Files:**
- Modify: `resources/views/livewire/tracking/indicadores-vencidos.blade.php` (insertar antes de la tabla)

**Step 1: Agregar bar-horizontal de vencidos por programa**

Insertar después del header y antes de la tabla:

```blade
{{-- Resumen visual --}}
@if ($avances->isNotEmpty())
<div class="mb-6" wire:ignore>
    @php
        $vencidosPorPrograma = $avances->groupBy(fn ($a) => $a->indicador->programa->clave ?? 'N/A')
            ->map(fn ($items, $clave) => ['clave' => $clave, 'count' => $items->count()])
            ->sortByDesc('count')
            ->values();
    @endphp
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Vencidos por Programa</h4>
        <x-charts.bar-horizontal
            :categories="$vencidosPorPrograma->pluck('clave')->toArray()"
            :series="[['name' => 'Vencidos', 'data' => $vencidosPorPrograma->pluck('count')->toArray()]]"
            :height="max(150, $vencidosPorPrograma->count() * 35)"
        />
    </div>
</div>
@endif
```

**Step 2: Verificar visualmente**

Run: Navegar a `http://localhost/seguimiento/vencidos`.
Expected: Bar-horizontal de vencidos por programa antes de la tabla.

**Step 3: Commit**

```bash
git add resources/views/livewire/tracking/indicadores-vencidos.blade.php
git commit -m "feat(tracking): add vencidos-por-programa chart to indicadores-vencidos"
```

---

## Task 5: Evaluación de Programa — Donut semáforo + Bullet por nivel

**Files:**
- Modify: `resources/views/livewire/evaluation/evaluacion-programa.blade.php`

**Step 1: Reemplazar conteo semáforo (líneas ~54-78) con donut + bullet**

Reemplazar el grid de 4 cajas de semáforo con:

```blade
{{-- Sección 2: Tablero de Semáforos --}}
<div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Tablero de Semáforos</h3>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Donut de semáforos --}}
        <div wire:ignore>
            <x-charts.donut
                :labels="['Verde', 'Amarillo', 'Rojo', 'Sin dato']"
                :series="[$tablero['conteo']['verde'], $tablero['conteo']['amarillo'], $tablero['conteo']['rojo'], $tablero['conteo']['sin_dato']]"
                :colors="['#22c55e', '#eab308', '#ef4444', '#9ca3af']"
                :height="250"
                centerText="{{ $tablero['conteo']['verde'] + $tablero['conteo']['amarillo'] + $tablero['conteo']['rojo'] + $tablero['conteo']['sin_dato'] }}"
                centerSubtext="indicadores"
            />
        </div>

        {{-- Bullet charts por nivel --}}
        <div wire:ignore>
            @if (!empty($tablero['desglose']))
                <x-charts.bullet
                    :data="collect($tablero['desglose'])->map(fn ($d) => [
                        'nombre' => $d['nivel'],
                        'resultado' => $d['promedio'],
                        'meta' => 100,
                        'rango_verde_min' => 75, 'rango_verde_max' => 150,
                        'rango_amarillo_min' => 50, 'rango_amarillo_max' => 75,
                        'rango_rojo_min' => 0, 'rango_rojo_max' => 50,
                    ])->toArray()"
                    :height="max(200, count($tablero['desglose']) * 60)"
                    :showLabels="true"
                />
            @endif
        </div>
    </div>

    {{-- Tabla de desglose (se mantiene) --}}
    @if (!empty($tablero['desglose']))
    <div class="mt-4 overflow-x-auto">
        {{-- tabla existente de desglose por nivel aquí --}}
    </div>
    @endif
</div>
```

**Step 2: En la sección Comparativa (~líneas 106-164), agregar bar-grouped antes de la tabla**

Insertar después del heading de la sección y antes de la tabla:

```blade
@if ($comparativa['disponible'] && count($comparativa['filas']) > 0)
<div class="mb-4" wire:ignore>
    @php
        $compFilas = collect($comparativa['filas'])->take(10);
    @endphp
    <x-charts.bar-grouped
        :categories="$compFilas->pluck('indicador')->map(fn ($n) => Str::limit($n, 25))->toArray()"
        :series="[
            ['name' => 'Anterior', 'data' => $compFilas->pluck('resultado_anterior')->toArray()],
            ['name' => 'Actual', 'data' => $compFilas->pluck('resultado_actual')->toArray()],
        ]"
        :colors="['#94a3b8', '#3b82f6']"
        :height="300"
        yaxisFormat="percent"
    />
</div>
@endif
```

**Step 3: Verificar visualmente**

Run: Navegar a `http://localhost/evaluacion/programa/{id}`.
Expected: Donut + bullet charts en sección semáforos, bar-grouped en comparativa.

**Step 4: Commit**

```bash
git add resources/views/livewire/evaluation/evaluacion-programa.blade.php
git commit -m "feat(evaluation): add donut, bullet, and comparison charts to evaluacion-programa"
```

---

## Task 6: Panel Transversal — Bar-horizontal por eje + Heatmap

**Files:**
- Modify: `resources/views/livewire/evaluation/panel-transversal.blade.php`

**Step 1: En cada pestaña, agregar bar-horizontal de índice por eje/ODS/UR**

Para la pestaña PED (el patrón se replica para ODS, UR, Anexo), insertar antes de la iteración de ejes:

```blade
{{-- Gráfica resumen PED --}}
@if (!empty($data['ejes']))
<div class="mb-6" wire:ignore>
    @php
        $ejesChart = collect($data['ejes']);
    @endphp
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Índice de Eficacia por Eje PED</h4>
        <x-charts.bar-horizontal
            :categories="$ejesChart->pluck('eje_nombre')->map(fn ($n) => Str::limit($n, 30))->toArray()"
            :series="[['name' => 'Índice %', 'data' => $ejesChart->pluck('promedio_indice')->toArray()]]"
            :height="max(200, $ejesChart->count() * 45)"
            :referenceLine="100"
            referenceLabel="Meta"
        />
    </div>
</div>
@endif
```

Replicar el mismo patrón para las pestañas ODS (`ods_nombre`), UR (`team_nombre`) y Anexo (`anexo_nombre`), ajustando los campos.

**Step 2: Agregar heatmap en pestaña PED (programa × eje)**

Insertar después de la gráfica bar-horizontal:

```blade
{{-- Heatmap PED × Programa --}}
@php
    $heatmapRows = collect($data['ejes'])->map(fn ($e) => ['id' => $e['eje_numero'], 'nombre' => 'Eje ' . $e['eje_numero']]);
    $heatmapCols = collect($data['ejes'])->flatMap(fn ($e) => collect($e['programas'] ?? []))->pluck('clave')->unique()->values()->toArray();
    $heatmapValues = collect($data['ejes'])->map(function ($eje) use ($heatmapCols) {
        $progs = collect($eje['programas'] ?? [])->keyBy('clave');
        return collect($heatmapCols)->map(function ($clave) use ($progs) {
            $p = $progs->get($clave);
            return $p ? [
                'valor' => $p['indice'] ?? 0,
                'semaforo' => match(true) {
                    ($p['indice'] ?? 0) >= 75 => 'verde',
                    ($p['indice'] ?? 0) >= 50 => 'amarillo',
                    default => 'rojo',
                },
                'detalle' => $clave . ': ' . ($p['indice'] ?? 0) . '%',
            ] : ['valor' => null, 'semaforo' => 'gris', 'detalle' => 'Sin datos'];
        })->toArray();
    })->toArray();
@endphp
@if (count($heatmapCols) > 0)
<div class="mb-6" wire:ignore>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Matriz Eje × Programa</h4>
        <x-charts.heatmap
            :rows="$heatmapRows->toArray()"
            :columns="$heatmapCols"
            :values="$heatmapValues"
        />
    </div>
</div>
@endif
```

**Step 3: Verificar visualmente**

Run: Navegar a `http://localhost/evaluacion/transversal`.
Expected: Bar-horizontal + heatmap en pestaña PED.

**Step 4: Commit**

```bash
git add resources/views/livewire/evaluation/panel-transversal.blade.php
git commit -m "feat(evaluation): add bar-horizontal and heatmap to panel-transversal"
```

---

## Task 7: Panel Presupuestal — Gauge + Marimekko + Lollipop

**Files:**
- Modify: `resources/views/livewire/presupuesto/panel-presupuestal.blade.php`

**Step 1: Reemplazar KPI de % Ejercido con gauge, agregar marimekko y lollipop**

Después de los KPI cards (línea ~34) e antes de la tabla (línea ~36), insertar:

```blade
{{-- Visualizaciones presupuestales --}}
<div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3" wire:ignore>
    {{-- Gauge: % Ejercido global --}}
    <div class="flex items-center justify-center rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <x-charts.gauge
            :value="$pctEjercido"
            :max="100"
            label="% Ejercido"
            :ranges="[
                ['min' => 0, 'max' => 40, 'color' => '#ef4444'],
                ['min' => 40, 'max' => 75, 'color' => '#eab308'],
                ['min' => 75, 'max' => 100, 'color' => '#22c55e'],
            ]"
        />
    </div>

    {{-- Marimekko: Programas (ancho=aprobado, alto=%ejercido) --}}
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución Presupuestal</h4>
        @php
            $marimekkoData = $programas->map(function ($p) {
                $aprobado = $p->partidasPresupuestales->sum(fn ($pp) => $pp->monto_efectivo);
                $ejercido = $p->partidasPresupuestales->sum(fn ($pp) => $pp->avancesFinancieros->sum('monto_pagado'));
                $pct = $aprobado > 0 ? round(($ejercido / $aprobado) * 100, 1) : 0;
                return [
                    'id' => $p->id,
                    'nombre' => $p->clave . ' ' . Str::limit($p->nombre, 20),
                    'monto_aprobado' => $aprobado,
                    'porcentaje_ejercido' => $pct,
                    'semaforo' => $pct >= 75 ? 'verde' : ($pct >= 40 ? 'amarillo' : 'rojo'),
                    'detalle' => '$' . number_format($ejercido, 0) . ' de $' . number_format($aprobado, 0),
                ];
            })->filter(fn ($p) => $p['monto_aprobado'] > 0)->values()->toArray();
        @endphp
        <x-charts.marimekko :data="$marimekkoData" :height="280" />
    </div>
</div>

{{-- Lollipop: Desviación de ejecución --}}
<div class="mb-8" wire:ignore>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Desviación de Ejecución por Programa</h4>
        @php
            $lollipopData = $programas->map(function ($p) {
                $aprobado = $p->partidasPresupuestales->sum(fn ($pp) => $pp->monto_efectivo);
                $ejercido = $p->partidasPresupuestales->sum(fn ($pp) => $pp->avancesFinancieros->sum('monto_pagado'));
                $pct = $aprobado > 0 ? round(($ejercido / $aprobado) * 100, 1) : 0;
                return [
                    'nombre' => $p->clave,
                    'desviacion' => round($pct - 100, 1),
                    'programado' => $aprobado,
                    'ejercido' => $ejercido,
                ];
            })->filter(fn ($p) => $p['programado'] > 0)->values()->toArray();
        @endphp
        <x-charts.lollipop :data="$lollipopData" :height="max(250, count($lollipopData) * 40)" :threshold="-20" />
    </div>
</div>
```

**Step 2: Verificar visualmente**

Run: Navegar a `http://localhost/presupuesto`.
Expected: Gauge + marimekko + lollipop visibles arriba de la tabla.

**Step 3: Commit**

```bash
git add resources/views/livewire/presupuesto/panel-presupuestal.blade.php
git commit -m "feat(presupuesto): add gauge, marimekko, and lollipop charts to panel-presupuestal"
```

---

## Task 8: Captura Avance Financiero — Waterfall + Bar-grouped

**Files:**
- Modify: `resources/views/livewire/presupuesto/captura-avance-financiero.blade.php`

**Step 1: Agregar waterfall en la sección de avance**

Insertar después de la tabla de avance financiero y antes de la leyenda de semáforo (~línea 155):

```blade
{{-- Waterfall: Flujo presupuestal por partida --}}
<div class="mt-6" wire:ignore>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Flujo Presupuestal</h4>
        @php
            $totalAprobadoPartidas = $partidas->sum('monto_efectivo');
            $totalEjercidoPartidas = collect($avances)->flatMap(fn ($t) => collect($t))->sum(fn ($v) => (float) ($v['pagado'] ?? 0));
            $saldoDisponible = $totalAprobadoPartidas - $totalEjercidoPartidas;
            $waterfallData = [
                ['label' => 'Aprobado', 'value' => $totalAprobadoPartidas, 'type' => 'total'],
                ['label' => 'Ejercido', 'value' => -$totalEjercidoPartidas, 'type' => 'decrement'],
                ['label' => 'Disponible', 'value' => $saldoDisponible, 'type' => 'total'],
            ];
        @endphp
        <x-charts.waterfall :data="$waterfallData" :height="280" />
    </div>
</div>
```

**Step 2: Agregar bar-grouped en la sección de calendarización**

Insertar antes del botón "Guardar Calendarización" (~línea 88):

```blade
{{-- Bar-grouped: Calendarización T1-T4 --}}
<div class="mt-6" wire:ignore>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución Trimestral</h4>
        @php
            $calData = $partidas->map(fn ($p) => [
                'clave' => $p->clave_partida,
                't1' => (float) ($metas[$p->id][1] ?? 0),
                't2' => (float) ($metas[$p->id][2] ?? 0),
                't3' => (float) ($metas[$p->id][3] ?? 0),
                't4' => (float) ($metas[$p->id][4] ?? 0),
            ]);
        @endphp
        <x-charts.bar-grouped
            :categories="$calData->pluck('clave')->toArray()"
            :series="[
                ['name' => 'T1', 'data' => $calData->pluck('t1')->toArray()],
                ['name' => 'T2', 'data' => $calData->pluck('t2')->toArray()],
                ['name' => 'T3', 'data' => $calData->pluck('t3')->toArray()],
                ['name' => 'T4', 'data' => $calData->pluck('t4')->toArray()],
            ]"
            :height="280"
            yaxisFormat="currency"
        />
    </div>
</div>
```

**Step 3: Verificar visualmente**

Run: Navegar a `http://localhost/presupuesto/captura/{programa_id}`.
Expected: Bar-grouped en calendarización, waterfall en avance.

**Step 4: Commit**

```bash
git add resources/views/livewire/presupuesto/captura-avance-financiero.blade.php
git commit -m "feat(presupuesto): add waterfall and bar-grouped charts to captura-financiero"
```

---

## Task 9: Verificación final

**Step 1: Correr tests existentes**

```bash
./vendor/bin/sail artisan test
```

Expected: 426 passed, 7 skipped (baseline sin cambios).

**Step 2: Navegar cada vista en el browser y verificar que las gráficas renderizan sin errores de consola**

Vistas a verificar:
1. `/seguimiento` — donut + bar-horizontal
2. `/seguimiento/sabana` — donut + bar-horizontal
3. `/seguimiento/concentrado` — donut + bar-grouped
4. `/seguimiento/vencidos` — bar-horizontal
5. `/evaluacion/programa/{id}` — donut + bullet + bar-grouped
6. `/evaluacion/transversal` — bar-horizontal + heatmap
7. `/presupuesto` — gauge + marimekko + lollipop
8. `/presupuesto/captura/{id}` — waterfall + bar-grouped

**Step 3: Commit final si hay ajustes**

```bash
git add -A
git commit -m "fix(charts): adjustments from visual verification"
```
