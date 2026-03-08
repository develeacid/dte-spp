# Sprint 10: Dashboard Operativo — Documento de Diseño

> **Sprint anterior:** S9 (Design System & Layout Redesign)
> **Objetivo:** Reemplazar el dashboard placeholder con widgets de datos reales, gráficas ApexCharts y lógica por rol.

---

## 1. Arquitectura

Un componente Livewire `Dashboard` reemplaza la vista estática actual. Carga datos a través de un `DashboardService` que agrega consultas por rol y las cachea 10 minutos. Las gráficas usan ApexCharts vía componentes Blade wrapper con Alpine.js.

```
Dashboard.php (Livewire, polling 60s)
  └─► DashboardService (Cache::remember 10 min)
        └─► Eloquent queries (programas, indicadores, avances, metas)
              └─► Vista Blade con x-ui.widget + x-charts.*
```

### Decisiones clave

| Decisión | Elección | Alternativa descartada |
|----------|----------|----------------------|
| Charting | ApexCharts (npm) | Chart.js (menos tipos de gráfica) |
| Datos | Livewire + polling 60s | Controller estático (sin refresh) |
| Cache | Cache::remember TTL 10 min | Sin cache (consultas directas) |
| Layout | Un solo dashboard con @can | Dashboards separados por rol |
| Actividad | Placeholder diferido | Feed de actividad real |
| Empty states | Contextuales con acción | Valores en "0" |

---

## 2. Secciones del dashboard por rol

### 2.1 Admin / Planeador (`revisar_avance`)

**Widgets (fila de 4 cards):**

| Widget | Dato | Query |
|--------|------|-------|
| Programas | Total activos del equipo | `ProgramaPresupuestario::paraTeam($teamId)->count()` |
| Indicadores | Total en MIR | `Indicador::whereHas('mirNivel.mir.programa', team filter)->count()` |
| Avance Promedio | % promedio real del período actual | `Avance::wherePeriodo(actual)->avg('porcentaje_avance')` |
| Vencidos | Indicadores con fecha límite pasada sin captura | Count con badge rojo si > 0 |

**Gráficas:**

1. **Semáforo global** — Donut chart con distribución verde/amarillo/rojo de todos los indicadores del equipo. Colores: `#059669` (verde), `#F59E0B` (amarillo), `#DC2626` (rojo).

2. **Avance por programa** — Barras horizontales comparando % avance real vs % programado por cada programa. Dos series: "Real" (brand) y "Programado" (gray-300).

3. **Tendencia de captura** — Línea sparkline mostrando cantidad de avances capturados por mes en los últimos 6 meses. Una sola serie.

### 2.2 Operador (`capturar_avance`)

**Widgets (fila de 2-3 cards):**

| Widget | Dato | Query |
|--------|------|-------|
| Mis Pendientes | Avances en EN_CAPTURA asignados al usuario | `Avance::where('capturado_por', $userId)->enCaptura()->count()` |
| Capturados este mes | Avances enviados este mes | `Avance::where('capturado_por', $userId)->thisMonth()->enviados()->count()` |

**Gráficas:**

1. **Semáforo de mis indicadores** — Donut chart con distribución verde/amarillo/rojo solo de los indicadores asignados al operador.

### 2.3 Sección compartida

- **Actividad reciente:** Placeholder con `x-ui.empty-state` — "Actividad reciente estará disponible próximamente".

---

## 3. Empty states contextuales

| Condición | Mensaje | Acción |
|-----------|---------|--------|
| Sin programas (equipo nuevo) | "No hay programas registrados" | Botón "Ir a Programas" → `mml.programas` |
| Sin avances capturados | "No hay avances capturados aún" | — |
| Sin indicadores vencidos | No se muestra el widget de vencidos | — |
| Operador sin pendientes | "No tienes indicadores pendientes" | — |

---

## 4. Componentes Blade para gráficas

Tres wrappers Alpine.js en `resources/views/components/charts/`:

### `x-charts.donut`

```
Props: labels (array), series (array), colors (array), height (int, default 280)
```

Inicializa ApexCharts en `x-init`, destruye en cleanup para evitar memory leaks con Livewire morphing. Usa `wire:ignore` para que Livewire no re-renderice el DOM del chart.

### `x-charts.bar-horizontal`

```
Props: categories (array), series (array de {name, data}), height (int, default 300)
```

Barras horizontales agrupadas. Tooltip con formato porcentaje.

### `x-charts.line`

```
Props: categories (array), series (array de {name, data}), height (int, default 250)
```

Línea con área suave (curve: smooth). Formato de eje Y como entero.

### Patrón de integración con Livewire

Cada componente chart usa `wire:ignore` en su contenedor para que Livewire no interfiera con el DOM de ApexCharts. La actualización de datos se hace vía `x-effect` que observa cambios en las props de Alpine y llama `chart.updateSeries()`.

```blade
<div wire:ignore x-data="chartDonut(@js($labels), @js($series), @js($colors))" x-init="initChart()" ...>
    <div x-ref="chart"></div>
</div>
```

---

## 5. DashboardService

Clase de servicio en `app/Services/DashboardService.php`:

```
DashboardService
├── getAdminStats(teamId): StdClass {programas, indicadores, avancePromedio, vencidos}
├── getOperadorStats(userId, teamId): StdClass {pendientes, capturadosMes}
├── getSemaforoDistribution(teamId): array {verde: N, amarillo: N, rojo: N}
├── getAvancePorPrograma(teamId): Collection [{programa, real, programado}, ...]
├── getTendenciaCaptura(teamId): Collection [{mes: 'Ene', count: N}, ...]
└── getSemaforoUsuario(userId): array {verde: N, amarillo: N, rojo: N}
```

Cada método usa `Cache::remember("dashboard:{method}:{id}", 600, fn() => ...)`.

La cache se invalida implícitamente por TTL (10 min). No se necesita invalidación manual porque el dashboard muestra datos agregados donde un desfase de minutos es aceptable.

---

## 6. Stack técnico

| Componente | Tecnología |
|-----------|------------|
| Gráficas | `apexcharts` (npm) |
| Interactividad | Alpine.js (ya instalado) |
| Datos | Livewire 3 polling 60s |
| Cache | Laravel Cache, TTL 600s |
| Tests | PHPUnit (unit + feature) |

### Dependencia nueva

```bash
npm install apexcharts
```

Import en `resources/js/app.js`:
```js
import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;
```

---

## 7. Testing

- **Unit tests** para `DashboardService`: cada método con datos de prueba, verificar conteos y estructura de respuesta.
- **Feature tests** para el componente Livewire `Dashboard`: renderizado por rol, verificar que widgets correctos aparecen según permisos.
- **Estimado:** ~15-20 tests nuevos.

---

## 8. Archivos afectados

### Nuevos
- `app/Livewire/Dashboard.php`
- `app/Services/DashboardService.php`
- `resources/views/livewire/dashboard.blade.php`
- `resources/views/components/charts/donut.blade.php`
- `resources/views/components/charts/bar-horizontal.blade.php`
- `resources/views/components/charts/line.blade.php`
- `tests/Unit/Services/DashboardServiceTest.php`
- `tests/Feature/DashboardTest.php`

### Modificados
- `resources/js/app.js` (import ApexCharts)
- `package.json` (dependencia apexcharts)
- `routes/web.php` (dashboard route apunta a Livewire component)
- `resources/views/dashboard.blade.php` (eliminado, reemplazado por livewire view)
