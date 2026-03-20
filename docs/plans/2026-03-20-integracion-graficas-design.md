# Integración de Gráficas en Módulos del SPP

**Fecha:** 2026-03-20
**Estado:** Aprobado

## Principio general
- **Módulos operativos** (Seguimiento): gráficas complementan tablas existentes
- **Módulos analíticos** (Evaluación, Presupuesto): gráficas reemplazan donde agregan más valor
- Datos se reusan de propiedades Livewire existentes, transformados en blade

## Módulo 1: Seguimiento (4 vistas)

### panel-seguimiento.blade.php
Agregar barra de resumen visual antes de la tabla:
- `donut` — distribución de semáforos (verde/amarillo/rojo) del filtro actual
- `bar-horizontal` — avance promedio por programa (resultado vs meta)
- La tabla se mantiene intacta debajo

### sabana-captura.blade.php
Las tarjetas métricas actuales se complementan con:
- `donut` — distribución de estados (aprobado/revisión/captura/observado/vencido)
- `sparkline` — tendencia de vencidos por trimestre (inline junto a la tarjeta de Vencidos)

### concentrado-captura.blade.php
Agregar resumen visual:
- `bar-grouped` — comparativa por programa: aprobados vs en_revisión vs en_captura
- `donut` — distribución global de estados

### indicadores-vencidos.blade.php
Agregar contexto visual:
- `bar-horizontal` — vencidos agrupados por programa (conteo)

## Módulo 2: Evaluación (2 vistas)

### evaluacion-programa.blade.php
Tres secciones visuales:
- Sección Semáforos: `donut` reemplaza tabla de conteo + `bullet` para cada nivel (FIN/PROPÓSITO/COMPONENTE/ACTIVIDAD) mostrando promedio vs rangos
- Sección Comparativa: `bar-grouped` resultado_anterior vs resultado_actual por indicador (reemplaza tabla comparativa)

### panel-transversal.blade.php
Cada pestaña (PED/ODS/UR/Anexo):
- `bar-horizontal` — índice de eficacia por eje/ODS/UR (reemplaza la repetición de cards)
- `heatmap` — matriz eje × programa con semáforo como color de celda (clickeable para drill-down)

## Módulo 3: Presupuesto (2 vistas)

### panel-presupuestal.blade.php
Transformar en dashboard visual:
- `gauge` — % ejercido global (KPI principal, reemplaza tarjeta numérica)
- `marimekko` — programas donde ancho=presupuesto aprobado, alto=%ejercido, color=semáforo (reemplaza tabla resumen, clickeable)
- `lollipop` — desviación de ejecución por programa
- La tabla se mantiene como vista alternativa con toggle

### captura-avance-financiero.blade.php
Agregar visualizaciones:
- `waterfall` — flujo presupuestal: saldo_inicial → ejercido → saldo_disponible
- `bar-grouped` — calendarización T1-T4 por partida

## Resumen de componentes

| Componente | Vistas | Estado |
|---|---|---|
| `donut` | panel-seguimiento, sábana, concentrado, eval-programa | Ya en uso |
| `bar-horizontal` | panel-seguimiento, vencidos, panel-transversal | Ya en uso |
| `bar-grouped` | concentrado, eval-programa, captura-financiero | Nuevo uso |
| `sparkline` | sábana-captura | Nuevo uso |
| `bullet` | evaluacion-programa | Nuevo uso |
| `heatmap` | panel-transversal | Nuevo uso |
| `gauge` | panel-presupuestal | Nuevo uso |
| `marimekko` | panel-presupuestal | Nuevo uso |
| `lollipop` | panel-presupuestal | Nuevo uso |
| `waterfall` | captura-financiero | Nuevo uso |

## Cambios en backend
- Cero cambios en componentes Livewire — datos se transforman en blade desde propiedades existentes
- Solo si algún cálculo resulta demasiado complejo en blade, se agrega una computed property puntual

## Orden de implementación
1. Seguimiento (más usado diariamente)
2. Evaluación (más valor para decisiones)
3. Presupuesto (más complejo visualmente)
