# Guía de Visualización de Datos — DTE-SPP 2026

> **Principio:** Cada gráfica debe responder una pregunta en menos de 5 segundos.
> Si el directivo necesita leer una tabla para entender la gráfica, la gráfica falló.

---

## 1. Stack de Visualización

### Librería primaria: Chart.js 4.x (ya instalada)

Se mantiene para gráficos estándar. El equipo ya lo conoce y cubre ~60% de las necesidades.

**Usa Chart.js para:** barras (verticales/horizontales), líneas, donuts, radar, scatter.

**Plugin adicional requerido:** `chartjs-plugin-annotation` para líneas de referencia,
cuadrantes en scatter, y umbrales de semáforo.

```bash
npm install chartjs-plugin-annotation
```

### Librería secundaria: D3.js v7 (nueva)

Se agrega para los 8 tipos de gráfico que Chart.js no soporta.

```bash
npm install d3
```

**Usa D3 para:** bullet charts, heatmaps, waterfall, Marimekko, sunburst, gauge,
lollipop, treemap.

**Regla del equipo:** Si la gráfica se puede hacer con Chart.js, se hace con Chart.js.
D3 solo entra cuando Chart.js literalmente no tiene el tipo de gráfico.

### SVG puro (sin librería)

Para visualizaciones simples que son más fáciles de dibujar a mano que de configurar
una librería: timelines, badges de semáforo, indicadores de progreso lineales.

### Convivencia en Blade

```blade
{{-- Chart.js — ya registrado globalmente --}}
<canvas id="chart-avance" width="400" height="200"></canvas>

{{-- D3 — se carga solo donde se necesita --}}
@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
    <script>
        // D3 code específico de esta vista
    </script>
@endpush
```

**Carga lazy de D3:** Solo las vistas que necesitan gráficos D3 cargan la librería.
No se incluye globalmente — pesa ~280KB minificado.

---

## 2. Paleta de Colores para Datos

### Semáforos (uso semántico, consistente en todo el sistema)

| Estado | Hex | Uso |
|--------|-----|-----|
| Verde | `#22C55E` | Meta cumplida, avance adecuado |
| Amarillo | `#EAB308` | Alerta, avance parcial |
| Rojo | `#EF4444` | Incumplimiento, riesgo |
| Gris | `#9CA3AF` | Sin datos, no aplica |

### Series de datos (hasta 6 categorías)

| # | Nombre | Hex | Uso típico |
|---|--------|-----|-----------|
| 1 | Azul | `#3B82F6` | Serie primaria, avance físico |
| 2 | Ámbar | `#F59E0B` | Serie secundaria, avance financiero |
| 3 | Teal | `#14B8A6` | Serie terciaria, metas |
| 4 | Coral | `#F97316` | Serie de contraste |
| 5 | Púrpura | `#8B5CF6` | Serie adicional |
| 6 | Rosa | `#EC4899` | Serie adicional |

### Regla de color

- Máximo 3 colores por gráfica. Si necesitas más, la gráfica intenta decir demasiado.
- El semáforo siempre usa la paleta semántica (verde/amarillo/rojo), nunca los colores de serie.
- En gráficas de comparación físico vs financiero: azul = físico, ámbar = financiero. Siempre.

---

## 3. Catálogo de Gráficas por Reporte

### 3.1 Dashboard Principal

**Gráficas actuales (mejorar):**

#### Donut de semáforos — Chart.js

```
Pregunta que responde: "¿Cuántos indicadores van bien, regular y mal?"
```

| Propiedad | Valor |
|-----------|-------|
| Tipo | `doughnut` |
| Datos | Conteo de indicadores por color de semáforo |
| Colores | Verde, Amarillo, Rojo, Gris (sin datos) |
| Centro | Texto con total de indicadores |
| Mejora | Agregar segundo donut al lado: semáforo financiero |

**Especificación del donut:**
- Cutout: 65% (dona delgada, espacio para número central)
- Sin leyenda de Chart.js — leyenda HTML custom debajo con conteo
- Animación: 800ms ease-out al montar
- Responsive: min-height 200px

#### Barras de avance por programa — Chart.js

```
Pregunta que responde: "¿Cuáles programas van adelante y cuáles atrás?"
```

| Propiedad | Valor |
|-----------|-------|
| Tipo | `bar` horizontal |
| Datos | % avance físico por programa |
| Mejora | Agregar segunda barra: % avance financiero (agrupada) |
| Color | Azul = físico, Ámbar = financiero |
| Orden | Descendente por avance físico |
| Referencia | Línea vertical punteada en meta esperada (ej: 75% al T3) |

**Plugin:** `chartjs-plugin-annotation` para la línea de referencia.

#### Línea de tendencia — Chart.js

```
Pregunta que responde: "¿Estamos mejorando o empeorando mes a mes?"
```

| Propiedad | Valor |
|-----------|-------|
| Tipo | `line` |
| Datos | % avance acumulado mensual (últimos 6 meses) |
| Mejora | Agregar segunda línea: avance financiero acumulado |
| Fill | Área suave debajo de cada línea (opacity 0.1) |

---

### 3.2 Avance Trimestral

#### Bullet Chart — D3 (nuevo)

```
Pregunta que responde: "¿El resultado está dentro del rango aceptable?"
```

Esta es la gráfica central para reportes PbR. Para cada indicador muestra en una
sola barra horizontal:
- Rango rojo (fondo más oscuro)
- Rango amarillo (fondo medio)
- Rango verde (fondo más claro)
- Barra de resultado real (barra negra/oscura superpuesta)
- Marca de meta (línea vertical)

**Especificación D3:**

```
┌─────────────────────────────────────────────────────┐
│▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░░░░░░░░░░░░░░░│ ← rangos semáforo (fondo)
│████████████████████████████│                         │ ← resultado real (barra)
│                            ┃                         │ ← meta (línea vertical)
│  Indicador: Tasa de cobertura    78% (meta: 85%)    │
└─────────────────────────────────────────────────────┘
```

| Propiedad | Valor |
|-----------|-------|
| Librería | D3.js |
| Ancho | 100% del contenedor |
| Alto por indicador | 48px |
| Rangos | Tomados de `rango_verde_min/max`, `rango_amarillo_min/max`, `rango_rojo_min/max` |
| Barra resultado | Color oscuro sólido, alto 60% del rango |
| Marca de meta | Línea vertical de 2px, color negro/gris oscuro |
| Label | Nombre del indicador a la izquierda, valor numérico a la derecha |
| Interactividad | Hover muestra tooltip con detalle (variables, fórmula) |

**Cuándo usar:** Avance trimestral, FMyE, ficha de programa, dashboard de indicadores.

---

### 3.3 Sábana de Captura / Tablero de Semáforos

#### Heatmap — D3 (nuevo)

```
Pregunta que responde: "¿Dónde están los problemas?" (vista panorámica)
```

Matriz de celdas coloreadas donde:
- Eje Y = programas (filas)
- Eje X = trimestres o dimensiones (columnas)
- Color de celda = semáforo (verde/amarillo/rojo/gris)

**Especificación D3:**

```
              │  T1   │  T2   │  T3   │  T4   │
Programa A    │  🟢   │  🟢   │  🟡   │  ░░   │
Programa B    │  🟡   │  🔴   │  🔴   │  ░░   │
Programa C    │  🟢   │  🟢   │  🟢   │  ░░   │
Programa D    │  🔴   │  🔴   │  🟡   │  ░░   │
```

| Propiedad | Valor |
|-----------|-------|
| Librería | D3.js |
| Celda | Rect redondeado (`rx=4`), tamaño fijo (ej: 80×40px) |
| Color | Escala ordinal: verde → amarillo → rojo → gris |
| Label en celda | Valor numérico (% avance) en texto pequeño (11px) |
| Hover | Tooltip con detalle: nombre indicador, resultado, meta |
| Click | Navega al detalle del programa (versión interactiva) |
| Scroll | Scroll vertical si >15 programas, header fijo |

**Variante para Tablero de Semáforos:** Las columnas no son trimestres sino dimensiones:
Físico | Financiero | Combinado | Legal | Tripartita

---

### 3.4 Resumen Ejecutivo para Gabinete

#### Gauge (Velocímetro) — D3 (nuevo)

```
Pregunta que responde: "¿Vamos bien o mal?" (un solo número)
```

Arco semicircular con aguja que indica el porcentaje de avance global.

**Especificación D3:**

| Propiedad | Valor |
|-----------|-------|
| Librería | D3.js |
| Tipo | Arco de 180° (semicírculo) |
| Rangos | 0-60% rojo, 60-85% amarillo, 85-100% verde (configurable) |
| Aguja | Línea desde el centro al arco, con punto de pivote |
| Centro | Número grande (36px bold): "72%" |
| Subtexto | "Avance físico global" o "Ejercicio presupuestal" |
| Dimensión | 200×120px (compacto para caber en tarjeta KPI) |
| Animación | Aguja gira de 0 al valor en 1.2s ease-out |

**Uso:** 2-3 gauges en fila en la primera página del resumen ejecutivo:
1. Avance físico global
2. Ejercicio presupuestal global
3. Índice de eficiencia promedio

#### Barras horizontales Top/Bottom 5 — Chart.js

```
Pregunta que responde: "¿Cuáles son los mejores y peores programas?"
```

| Propiedad | Valor |
|-----------|-------|
| Tipo | 2 gráficas `bar` horizontal (una top, una bottom) |
| Top 5 | Ordenado descendente, color verde→azul |
| Bottom 5 | Ordenado ascendente, color rojo→naranja |
| Label | Nombre del programa (truncado a 30 chars) |
| Valor | % avance en el extremo de cada barra |

---

### 3.5 Informe Trimestral Financiero

#### Waterfall Chart — D3 (nuevo)

```
Pregunta que responde: "¿Cómo pasamos del presupuesto aprobado al ejercido?"
```

Gráfica de cascada donde cada barra flotante muestra un cambio:

```
Aprobado ████████████████████ $100M
         ┌──┐
Adec. +  │  │ +$15M                    (barra verde flotando)
         └──┘
         ┌──────┐
Adec. -  │      │ -$8M                 (barra roja flotando)
         └──────┘
Modif.   ████████████████████████ $107M (barra de subtotal)
                 ┌───────────────┐
No ejer. │                       │ -$30M (barra roja)
                 └───────────────┘
Ejercido █████████████████ $77M         (barra final)
```

| Propiedad | Valor |
|-----------|-------|
| Librería | D3.js |
| Orientación | Vertical (barras de arriba hacia abajo) |
| Incrementos | Color verde (adecuaciones positivas) |
| Decrementos | Color rojo (reducciones, no ejercido) |
| Subtotales | Color azul/gris (aprobado, modificado, ejercido) |
| Conectores | Líneas punteadas entre barras para mostrar continuidad |
| Labels | Monto en la barra + nombre de la categoría debajo |
| Ancho | 100% contenedor, máx 8 columnas |

**Cuándo usar:** Informe trimestral, Cuenta Pública (resumen de adecuaciones).

#### Barras agrupadas aprobado vs ejercido — Chart.js

```
Pregunta que responde: "¿Cuánto se asignó vs cuánto se gastó por programa?"
```

| Propiedad | Valor |
|-----------|-------|
| Tipo | `bar` agrupado vertical |
| Serie 1 | Monto aprobado (azul claro) |
| Serie 2 | Monto ejercido (ámbar) |
| Eje X | Programas |
| Eje Y | Millones de pesos |
| Referencia | Línea horizontal punteada al promedio de ejercicio |

---

### 3.6 Cuenta Pública

#### Marimekko (Mosaico) — D3 (nuevo)

```
Pregunta que responde: "¿Quién tiene más dinero y quién lo gasta mejor?"
```

Esta es la gráfica financiera más informativa para directivos. Es un rectángulo
dividido en columnas (una por programa) donde:
- **Ancho** de columna = proporción del presupuesto total (más dinero = más ancho)
- **Alto** de columna = % de ejercicio presupuestal
- **Color** = semáforo combinado (verde/amarillo/rojo)

```
┌───────────────────┬──────────┬───────┬────────────────┐
│                   │          │       │                │  100%
│    Programa A     │  Prog B  │  P.C  │   Programa D   │
│    (verde)        │  (rojo)  │ (am.) │   (verde)      │
│    $45M           │  $20M    │ $10M  │   $35M         │
│    92% ejercido   │  43%     │ 67%   │   88%          │
│                   │          │       │                │
│                   ├──────────┤       │                │
│                   │          │       │                │
├───────────────────┤          ├───────┤                │  0%
└───────────────────┴──────────┴───────┴────────────────┘
```

| Propiedad | Valor |
|-----------|-------|
| Librería | D3.js |
| Layout | `d3.treemap()` con ratio 1:1 o `d3.stack()` customizado |
| Ancho columna | Proporcional a `monto_aprobado` del programa |
| Alto columna | Proporcional a `% ejercido` (0-100%) |
| Color | Semáforo combinado del programa |
| Label | Nombre del programa, monto, % ejercido (dentro de cada celda) |
| Hover | Tooltip: desglose completo (aprobado, modificado, comprometido, devengado, pagado) |
| Click | Navega a ficha integral del programa |
| Min-width | Ocultar labels en columnas < 60px, solo mostrar en tooltip |

**Cuándo usar:** Cuenta Pública, resumen ejecutivo.

#### Scatter de eficiencia — Chart.js

```
Pregunta que responde: "¿Quién gasta bien y entrega resultados?"
```

Gráfica de dispersión con 4 cuadrantes:

```
        Alto avance físico
             │
    Eficiente│  Sobre-entrega
    (verde)  │  (azul)
  ───────────┼───────────── Alto avance financiero
    Sub-     │  Gasta sin
    ejercicio│  entregar
    (amarillo│  (ROJO - peligro)
             │
        Bajo avance físico
```

| Propiedad | Valor |
|-----------|-------|
| Tipo | `scatter` de Chart.js |
| Eje X | % avance financiero (0-120%) |
| Eje Y | % avance físico (0-120%) |
| Punto | Cada programa es un punto; radio = presupuesto relativo |
| Cuadrantes | Líneas de referencia en X=meta financiera, Y=meta física |
| Color cuadrante | Fondo suave: verde (arriba-izq), azul (arriba-der), amarillo (abajo-izq), rojo (abajo-der) |
| Label | Tooltip con nombre del programa y valores |
| Plugin | `chartjs-plugin-annotation` para cuadrantes |

---

### 3.7 Alineación Estratégica

#### Sunburst — D3 (nuevo)

```
Pregunta que responde: "¿Cómo se distribuye el presupuesto en la cascada de planeación?"
```

Gráfica de anillos concéntricos donde cada nivel es un anillo:
- Centro: total del ejercicio
- Anillo 1: Ejes PED (3-5 segmentos)
- Anillo 2: Temas (subdivisión de cada eje)
- Anillo 3: Objetivos estratégicos
- Anillo 4 (exterior): Programas presupuestarios

| Propiedad | Valor |
|-----------|-------|
| Librería | D3.js con `d3.partition()` y `d3.arc()` |
| Tamaño segmento | Proporcional al presupuesto asignado |
| Color | Ramp por eje PED (ej: Eje 1=azules, Eje 2=verdes, Eje 3=ámbar) |
| Centro | Texto: "Presupuesto total: $XXM" |
| Hover | Muestra ruta completa: "Eje 2 > Salud > OE 2.3 > Programa Nutrición" |
| Click | Zoom al segmento (D3 transition, agranda el segmento seleccionado) |
| Drill-up | Click en centro regresa al nivel anterior |
| Dimensión | 400×400px (cuadrado) |
| Animación | Transición de 600ms al hacer zoom |

---

### 3.8 Ficha de Programa Integral

#### Radar de salud — Chart.js

```
Pregunta que responde: "¿Dónde está fuerte y dónde está débil este programa?"
```

| Propiedad | Valor |
|-----------|-------|
| Tipo | `radar` de Chart.js |
| Ejes (5) | Avance físico, Avance financiero, Cumplimiento legal, CREMAA score, Cobertura poblacional |
| Escala | 0-100% cada eje |
| Fill | Área coloreada con opacity 0.2 |
| Referencia | Segunda serie gris tenue = promedio del ejercicio (para comparar) |
| Dimensión | 300×300px |

**Cálculo de cada eje:**
- Avance físico: promedio de % cumplimiento de indicadores
- Avance financiero: % ejercido de partidas
- Cumplimiento legal: (items validados / items requeridos) × 100
- CREMAA: promedio de 6 criterios CREMAA (cada uno 0 o 100)
- Cobertura poblacional: (población atendida / población objetivo) × 100
  (si no hay padrón aún, usa avance del indicador de cobertura del Componente)

---

### 3.9 Panel Transversal

#### Treemap — D3 (nuevo)

```
Pregunta que responde: "¿Cómo se distribuye el presupuesto por eje y cuál va mejor?"
```

| Propiedad | Valor |
|-----------|-------|
| Librería | D3.js con `d3.treemap()` |
| Datos | Jerarquía: Eje PED → Programas |
| Tamaño | Proporcional al presupuesto aprobado |
| Color | Semáforo promedio del eje (verde/amarillo/rojo) |
| Label | Nombre del eje + monto + % avance dentro de cada rect |
| Hover | Lista de programas del eje con mini-semáforo |
| Click | Filtra las tablas del panel transversal por ese eje |
| Dimensión | 100% ancho × 300px alto |

---

### 3.10 Subejercicio

#### Lollipop Chart — D3 (nuevo)

```
Pregunta que responde: "¿Cuánto se desvía cada programa de su meta de gasto?"
```

```
                    ←sub-ejercicio │ sobre-ejercicio→
                                  │
    Programa A    ────────────────●│                      -23%
    Programa B                    │──●                    +5%
    Programa C    ──────●         │                       -42%
    Programa D                    │────────●              +18%
                                  │
                        0% (meta calendarizada)
```

| Propiedad | Valor |
|-----------|-------|
| Librería | D3.js |
| Eje | Línea vertical en 0% = meta calendarizada |
| Punto | Círculo de 6px en el valor de desviación |
| Línea | De 0 al punto, 1.5px |
| Color | Rojo si negativo (subejercicio), verde si positivo (sobre-ejercicio) |
| Orden | Por desviación absoluta descendente (peores arriba) |
| Hover | Tooltip: monto programado, monto ejercido, desviación absoluta y % |
| Umbral | Línea punteada en el umbral de alerta configurable (ej: -20%) |

---

### 3.11 Sustento Legal

#### Timeline de Jerarquía Legal — SVG puro

```
Pregunta que responde: "¿Qué leyes respaldan este programa, de mayor a menor rango?"
```

```
    ● Constitución Federal (Art. 134)
    │
    ● LGCG (Art. 46, Frac. III)
    │
    ● Constitución de Oaxaca (Art. 137)
    │
    ● Ley Estatal de Planeación (Art. 75)
    │
    ● Ley Orgánica del Poder Ejecutivo (Art. 45)
    │
    ○ Reglas de Operación — pendiente de publicación
```

| Propiedad | Valor |
|-----------|-------|
| Librería | SVG puro (componente Blade) |
| Layout | Línea vertical con nodos circulares |
| Nodo lleno (●) | Fundamento registrado y vigente |
| Nodo vacío (○) | Fundamento pendiente o no vigente |
| Color nodo | Verde = vigente, Gris = pendiente, Rojo = vencido/rechazado |
| Label | Nombre del ordenamiento + artículo a la derecha del nodo |
| Sublabel | Descripción en gris claro debajo (truncada a 80 chars) |

---

## 4. Gráficas Dinámicas del Asistente IA

El asistente genera gráficas en runtime basándose en el tipo de resultado.

| Tipo de resultado | Gráfica | Librería |
|---|---|---|
| Ranking (top/bottom N) | Barras horizontales ordenadas | Chart.js |
| Comparación (2-4 programas) | Barras agrupadas o radar | Chart.js |
| Tendencia temporal | Líneas multiserie | Chart.js |
| Distribución (<6 categorías) | Donut | Chart.js |
| Distribución (jerárquica) | Treemap | D3.js |
| Semáforo por dimensión | Heatmap simplificado | D3.js |

**El `ReporteAsistenteService` decide qué gráfica usar:**

```php
private function seleccionarGrafica(string $tipo, int $numCategories): string
{
    return match($tipo) {
        'ranking' => 'bar_horizontal',
        'comparacion' => $numCategories <= 4 ? 'radar' : 'bar_grouped',
        'tendencia' => 'line',
        'distribucion' => $numCategories <= 6 ? 'donut' : 'treemap',
        'semaforo' => 'heatmap',
        default => 'bar_horizontal',
    };
}
```

---

## 5. Componentes Blade Reutilizables

### Componentes para Chart.js

```
resources/views/components/charts/
├── donut.blade.php          — Donut con texto central
├── bar-horizontal.blade.php — Barras horizontales con referencia
├── bar-grouped.blade.php    — Barras agrupadas (2-3 series)
├── line-trend.blade.php     — Línea con fill y tendencia
├── radar.blade.php          — Radar de 5-6 ejes
├── scatter-quadrant.blade.php — Scatter con cuadrantes de eficiencia
└── sparkline.blade.php      — Mini-línea inline (para tablas)
```

### Componentes para D3

```
resources/views/components/charts/
├── bullet.blade.php         — Bullet chart para indicadores
├── heatmap.blade.php        — Matriz de semáforos
├── waterfall.blade.php      — Cascada financiera
├── marimekko.blade.php      — Mosaico de presupuesto
├── sunburst.blade.php       — Anillos de alineación PED
├── gauge.blade.php          — Velocímetro ejecutivo
├── lollipop.blade.php       — Desviaciones de subejercicio
└── treemap.blade.php        — Distribución jerárquica
```

### Componentes SVG puros

```
resources/views/components/charts/
├── timeline-legal.blade.php — Timeline de jerarquía legal
├── semaforo-pill.blade.php  — Indicador semáforo inline
└── progress-bar.blade.php   — Barra de progreso con referencia
```

### API de cada componente Blade

Todos los componentes siguen la misma interfaz:

```blade
<x-charts.bullet
    :data="$indicadores"
    :height="400"
    :show-labels="true"
    :animate="true"
/>

<x-charts.heatmap
    :rows="$programas"
    :columns="['T1', 'T2', 'T3', 'T4']"
    :values="$matrizSemaforos"
    :clickable="true"
    click-route="reportes.ficha-programa"
/>

<x-charts.gauge
    :value="72"
    :max="100"
    :ranges="[
        ['min' => 0, 'max' => 60, 'color' => '#EF4444'],
        ['min' => 60, 'max' => 85, 'color' => '#EAB308'],
        ['min' => 85, 'max' => 100, 'color' => '#22C55E'],
    ]"
    label="Avance físico global"
/>
```

---

## 6. Especificaciones para PDF (DomPDF)

DomPDF no ejecuta JavaScript, así que las gráficas D3/Chart.js no funcionan directamente
en PDFs generados server-side.

### Estrategia para gráficas en PDF

**Opción A — SVG estático server-side (recomendada):**

Crear versiones SVG estáticas de cada gráfica usando PHP. Para gráficas simples
(barras, donuts, gauges) esto es viable.

```php
// Service que genera SVG string para DomPDF
class ChartSvgService
{
    public function donut(array $data, array $colors): string
    {
        // Genera string SVG con arcos calculados en PHP
        // DomPDF renderiza SVG inline en el PDF
    }

    public function barHorizontal(array $data): string { /* ... */ }
    public function gauge(float $value, float $max): string { /* ... */ }
    public function bullet(array $indicadores): string { /* ... */ }
}
```

**Opción B — Captura de canvas (para gráficas complejas):**

Para gráficas D3 complejas (sunburst, Marimekko, treemap), usar un headless browser
(Puppeteer/Browsershot) para renderizar la gráfica en un navegador y capturar como
imagen PNG que se embebe en el PDF.

```php
// Solo para gráficas complejas D3 que no se pueden replicar en SVG estático
use Spatie\Browsershot\Browsershot;

$html = view('components.charts.marimekko-standalone', $data)->render();
$image = Browsershot::html($html)->windowSize(800, 400)->screenshot();
// Embeber $image en el PDF
```

**Recomendación:** Empezar con Opción A para todas las gráficas. Solo escalar a
Opción B si alguna gráfica D3 es demasiado compleja para replicar en SVG server-side.

### Gráficas prioritarias para PDF

| Gráfica | Estrategia PDF | Complejidad |
|---------|---------------|-------------|
| Donut semáforo | SVG server-side | Baja |
| Barras horizontales | SVG server-side | Baja |
| Gauge | SVG server-side | Media (arcos) |
| Bullet chart | SVG server-side | Media |
| Waterfall | SVG server-side | Media |
| Scatter cuadrantes | SVG server-side | Media |
| Heatmap | SVG server-side (tabla coloreada) | Baja |
| Lollipop | SVG server-side | Baja |
| Marimekko | Browsershot captura | Alta |
| Sunburst | Browsershot captura | Alta |
| Treemap | Browsershot captura | Alta |
| Radar | SVG server-side | Media (polígono) |

---

## 7. Resumen de implementación

### Archivos nuevos

```
resources/views/components/charts/
├── Chart.js (7 componentes)
│   ├── donut.blade.php
│   ├── bar-horizontal.blade.php
│   ├── bar-grouped.blade.php
│   ├── line-trend.blade.php
│   ├── radar.blade.php
│   ├── scatter-quadrant.blade.php
│   └── sparkline.blade.php
├── D3.js (8 componentes)
│   ├── bullet.blade.php
│   ├── heatmap.blade.php
│   ├── waterfall.blade.php
│   ├── marimekko.blade.php
│   ├── sunburst.blade.php
│   ├── gauge.blade.php
│   ├── lollipop.blade.php
│   └── treemap.blade.php
└── SVG puro (3 componentes)
    ├── timeline-legal.blade.php
    ├── semaforo-pill.blade.php
    └── progress-bar.blade.php

app/Services/Charts/
└── ChartSvgService.php          — Generador SVG server-side para PDFs
```

### Dependencias

```bash
npm install d3                          # ~280KB minificado
npm install chartjs-plugin-annotation   # ~15KB
# Opcionales (solo si se necesita Opción B para PDF):
# composer require spatie/browsershot
```

### Orden de implementación sugerido

| Fase | Gráficas | Prioridad |
|------|----------|-----------|
| 1 | Mejorar existentes: donut dual, barras agrupadas, línea dual | Alta — Sprint G |
| 2 | Bullet chart + heatmap (los más pedidos en PbR) | Alta — Sprint G/H |
| 3 | Gauge + waterfall + scatter cuadrantes | Media — Sprint H |
| 4 | Marimekko + sunburst + treemap + lollipop | Media — Sprint H/I |
| 5 | ChartSvgService para PDFs | Media — Sprint H |
| 6 | Gráficas dinámicas del asistente IA | Baja — Sprint I |