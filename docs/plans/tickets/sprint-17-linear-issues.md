# Issues de Linear — Sprint 17: MML Wizard Redesign

## Proyecto

| Campo | Valor |
|-------|-------|
| **Nombre** | Sprint 9: MML Wizard Redesign |
| **Equipo** | DTE |
| **Estado** | Backlog |
| **Descripción** | Rediseño visual completo del wizard de planeación MML (6 etapas). Mejoras de UX/UI sin cambios en lógica de negocio: stepper con iconos SVG, paneles IA estilo Notion, conectores CSS en árboles, embudo interactivo, y CTA de finalización prominente. |

---

## Issue 1: Rediseño visual del stepper compartido

| Campo | Valor |
|-------|-------|
| **Título** | Rediseñar stepper MML con iconos SVG, barra de progreso y vista móvil |
| **Equipo** | DTE |
| **Proyecto** | Sprint 9: MML Wizard Redesign |
| **Estado** | Backlog |
| **Prioridad** | 2 (High) — blocker de T2–T7 |
| **Estimación** | 3 |
| **Labels** | `type: feature`, `stack: frontend`, `mod: mml` |
| **Bloqueado por** | — |

**Descripción:**

```markdown
## Contexto
Rediseño del componente compartido `x-mml.stepper` usado en las 6 etapas del wizard de planeación MML. El componente actual muestra números y texto; el rediseño agrega iconos SVG por tipo de paso, barra de progreso global, y una vista compacta para móvil.

**Rama:** `feat/mml-wizard-redesign`

## Archivos
- `resources/views/components/mml/stepper.blade.php`

## Cambios
- Iconos SVG por tipo de paso (problema, árbol, embudo, alineación)
- Barra de progreso con gradiente azul→verde y contador N/6
- Móvil: paso actual con flechas ← → y dots indicadores
- Desktop: círculos w-10 h-10 con hover scale + shadow
- Estados preservados: completado (verde ✓), activo (azul ring), pendiente (gris), bloqueado (candado)

## Criterios de aceptación
- [ ] 6 iconos SVG distintos renderizan correctamente
- [ ] Barra de progreso muestra porcentaje correcto
- [ ] Vista móvil muestra solo paso actual con navegación por flechas
- [ ] Estados visuales (completado/activo/pendiente/bloqueado) funcionan
- [ ] `StepperComponentTest` pasa (2 tests, 3 assertions)
- [ ] Todas las vistas de etapas 1-6 siguen renderizando correctamente
```

---

## Issue 2: Rediseño UI Etapa 1 — Definición del Problema

| Campo | Valor |
|-------|-------|
| **Título** | Rediseñar Etapa 1 con textarea auto-resize y panel IA lateral |
| **Equipo** | DTE |
| **Proyecto** | Sprint 9: MML Wizard Redesign |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 3 |
| **Labels** | `type: feature`, `stack: frontend`, `mod: mml` |
| **Bloqueado por** | Issue 1 |

**Descripción:**

```markdown
## Contexto
Mejora visual de la Etapa 1 (Definición del Problema) del wizard MML. Textarea con auto-resize, panel de validación IA en sidebar lateral estilo Notion (no modal), badges de color para issues, y botones claros aceptar/descartar.

**Rama:** `feat/mml-wizard-redesign`

## Archivos
- `resources/views/livewire/mml/definicion-problema.blade.php`

## NO tocar
- `app/Livewire/Mml/DefinicionProblema.php` — lógica intacta
- Métodos: `validarConIa()`, `guardar()`, `aceptarSugerencia()`

## Cambios UI
- Textarea con auto-resize via Alpine.js (`x-init`, `@input`)
- Placeholder guiado: "Ej: Alta tasa de desnutrición infantil..."
- Layout dos columnas (lg+): formulario izquierda, panel IA derecha
- Panel IA sticky con header verde/ámbar según validez
- Badges de color por tipo de issue (estado negativo, observación)
- Botones "Aceptar sugerencia" (indigo) / "Descartar" (gris outline)

## Criterios de aceptación
- [ ] Textarea crece automáticamente al escribir
- [ ] Panel IA aparece en sidebar derecha (desktop) o debajo (móvil)
- [ ] Badges de color renderizan según tipo de issue
- [ ] Botones aceptar/descartar funcionan correctamente
- [ ] `DefinicionProblemaTest` pasa (6 tests, 14 assertions)
```

---

## Issue 3: Rediseño UI Etapa 2 — Árbol de Problemas

| Campo | Valor |
|-------|-------|
| **Título** | Rediseñar Etapa 2 con conectores CSS y panel IA lateral |
| **Equipo** | DTE |
| **Proyecto** | Sprint 9: MML Wizard Redesign |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: frontend`, `mod: mml` |
| **Bloqueado por** | Issue 1 |

**Descripción:**

```markdown
## Contexto
Rediseño visual de la Etapa 2 (Árbol de Problemas) del wizard MML. Visualización tipo árbol con conectores CSS, cards de nodos con colores por tipo, sugerencias IA en panel lateral sticky, y contador de nodos.

**Rama:** `feat/mml-wizard-redesign`

## Archivos
- `resources/views/livewire/mml/arbol-problema-builder.blade.php`
- `resources/views/livewire/mml/partials/nodo-card.blade.php`

## NO tocar
- `app/Livewire/Mml/ArbolProblemaBuilder.php` — lógica intacta
- Métodos: `agregarNodo()`, `eliminarNodo()`, `sugerirConIa()`, `guardarNuevoNodo()`

## Cambios UI
- Conectores CSS: líneas verticales y horizontales entre nodos padre-hijo
- Colores por tipo: causas (naranja), efectos (morado), central (rojo)
- `nodo-card.blade.php`: borde izquierdo de acento, badge pill para tipo, hover shadow
- Panel IA lateral sticky con botones "Sugerir causas" / "Sugerir efectos"
- Contador badges: "N causas" (naranja) / "N efectos" (morado)
- Modales mejorados con rounded-xl y z-50

## Criterios de aceptación
- [ ] Conectores CSS visibles entre nodos padres e hijos
- [ ] Cards de nodos con borde izquierdo de color según tipo
- [ ] Panel IA en sidebar derecha con sugerencias como cards
- [ ] Contador de causas y efectos visible arriba del árbol
- [ ] `ArbolProblemaBuilderTest` pasa (7 tests, 14 assertions)
```

---

## Issue 4: Rediseño UI Etapa 3 — Árbol de Objetivos

| Campo | Valor |
|-------|-------|
| **Título** | Rediseñar Etapa 3 con layout lado a lado y transformación bulk IA |
| **Equipo** | DTE |
| **Proyecto** | Sprint 9: MML Wizard Redesign |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: frontend`, `stack: backend`, `mod: mml` |
| **Bloqueado por** | Issue 1 |

**Descripción:**

```markdown
## Contexto
Rediseño de la Etapa 3 (Árbol de Objetivos) con layout lado a lado mostrando la transformación problema→objetivo. Incluye un cambio mínimo en el Livewire component para agregar bulk transform.

**Rama:** `feat/mml-wizard-redesign`

## Archivos
- `app/Livewire/Mml/ArbolObjetivosBuilder.php` (cambio mínimo)
- `resources/views/livewire/mml/arbol-objetivos-builder.blade.php`

## Cambio Livewire
- Agregar método `transformarTodosConIa()` que itera nodos pendientes y llama `transformarConIa()` por cada uno

## Cambios UI
- Grid 3 columnas: problema (rojo) | flecha → | objetivo (verde)
- Flechas de transformación (SVG) con color según estado
- Badges: [Pendiente] rojo, [Transformado] verde
- Botón bulk "Transformar todos con IA" (púrpura) con spinner
- Contadores: N transformados (verde) / N pendientes (rojo)
- Mobile: flechas verticales ↓ entre cards apiladas

## Criterios de aceptación
- [ ] Layout lado a lado renderiza correctamente
- [ ] Flechas de transformación visibles entre pares
- [ ] Badges Pendiente/Transformado con colores correctos
- [ ] Botón "Transformar todos con IA" visible cuando hay pendientes
- [ ] Método `transformarTodosConIa()` itera todos los nodos pendientes
- [ ] `ArbolObjetivosBuilderTest` pasa (5 tests, 9 assertions)
```

---

## Issue 5: Rediseño UI Etapa 4 — Selección de Alternativas

| Campo | Valor |
|-------|-------|
| **Título** | Rediseñar Etapa 4 con cards responsive y badge Enlace MIR |
| **Equipo** | DTE |
| **Proyecto** | Sprint 9: MML Wizard Redesign |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: frontend`, `mod: mml` |
| **Bloqueado por** | Issue 1 |

**Descripción:**

```markdown
## Contexto
Rediseño de la Etapa 4 (Selección de Alternativas) con cards en vez de secciones planas, badge "Enlace MIR" en la alternativa seleccionada, banner informativo, y evaluación IA con indicadores visuales de viabilidad.

**Rama:** `feat/mml-wizard-redesign`

## Archivos
- `resources/views/livewire/mml/seleccion-alternativas.blade.php`

## NO tocar
- `app/Livewire/Mml/SeleccionAlternativas.php` — lógica intacta
- Métodos: `crearAlternativa()`, `toggleNodo()`, `seleccionarAlternativa()`, `evaluarConIa()`

## Cambios UI
- Cards con border-2 y rounded-xl para cada alternativa
- Badge "Enlace MIR" (azul) junto a badge "Seleccionada" (verde)
- Banner informativo azul explicando propósito del paso
- Medios en grid 2 columnas con badges tipo pill
- Checkboxes en grid responsive 2 columnas con hover
- Evaluación IA: cards con indicadores alta(verde)/media(ámbar)/baja(rojo)
- Panel evaluación con header púrpura y recomendación

## Criterios de aceptación
- [ ] Alternativas renderizan como cards prominentes
- [ ] Badge "Enlace MIR" aparece en alternativa seleccionada
- [ ] Banner informativo visible al inicio
- [ ] Indicadores de viabilidad con colores correctos
- [ ] `SeleccionAlternativasTest` pasa (8 tests, 17 assertions)
```

---

## Issue 6: Rediseño UI Etapa 5 — Embudo de Poblaciones

| Campo | Valor |
|-------|-------|
| **Título** | Rediseñar Etapa 5 con embudo interactivo y validación visual en tiempo real |
| **Equipo** | DTE |
| **Proyecto** | Sprint 9: MML Wizard Redesign |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: frontend`, `mod: mml` |
| **Bloqueado por** | Issue 1 |

**Descripción:**

```markdown
## Contexto
Rediseño de la Etapa 5 (Embudo de Poblaciones) con visualización del embudo siempre visible en sidebar izquierda, actualización en tiempo real via Alpine.js, y validación visual con bordes rojos cuando se violan las restricciones del embudo.

**Rama:** `feat/mml-wizard-redesign`

## Archivos
- `resources/views/livewire/mml/embudo-poblaciones.blade.php`

## NO tocar
- `app/Livewire/Mml/EmbudoPoblaciones.php` — lógica intacta
- Método `guardar()` y validaciones de modelo

## Cambios UI
- Layout side-by-side: embudo sticky (izquierda) + formulario (derecha)
- Embudo con Alpine.js: actualiza visualmente al escribir (x-on:input)
- Ancho dinámico del embudo proporcional a cantidades (potW, objW)
- Porcentajes automáticos entre niveles (referencia→potencial→objetivo)
- Validación visual: bordes rojos + ring cuando pot > ref o obj > pot
- Secciones del formulario con bordes de color y números circulares (1/2/3)
- Embudo con formato de números localizados (es-MX)

## Criterios de aceptación
- [ ] Embudo siempre visible en sidebar izquierda (desktop)
- [ ] Embudo se actualiza en tiempo real al escribir cantidades
- [ ] Bordes rojos aparecen cuando se violan restricciones del embudo
- [ ] Porcentajes entre niveles se calculan correctamente
- [ ] Layout stacks correctamente en móvil (embudo arriba, form abajo)
- [ ] `EmbudoPoblacionesTest` pasa (6 tests, 22 assertions)
```

---

## Issue 7: Rediseño UI Etapa 6 — Alineación Estratégica y CTA final

| Campo | Valor |
|-------|-------|
| **Título** | Rediseñar Etapa 6 con panel IA, ODS visual, y CTA Crear MIR |
| **Equipo** | DTE |
| **Proyecto** | Sprint 9: MML Wizard Redesign |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: frontend`, `mod: mml` |
| **Bloqueado por** | Issue 1 |

**Descripción:**

```markdown
## Contexto
Rediseño de la Etapa 6 (Alineación Estratégica) — paso final del wizard MML. Panel de búsqueda IA con scores de relevancia, selects dependientes con UX clara, multiselect visual de ODS, checkboxes de anexos transversales, y CTA prominente para finalizar planeación y generar la MIR.

**Rama:** `feat/mml-wizard-redesign`

## Archivos
- `resources/views/livewire/mml/alineacion-estrategica.blade.php`

## NO tocar
- `app/Livewire/Mml/AlineacionEstrategica.php` — lógica intacta
- Métodos: `buscarConIa()`, `guardar()`, `finalizarPlaneacion()`

## Cambios UI
- Panel búsqueda IA: card púrpura con botón "Buscar con IA"
- Sugerencias como cards con score de relevancia (verde ≥70%, ámbar ≥40%, gris <40%)
- Selects dependientes con hints "(selecciona un eje)" cuando están deshabilitados
- ODS: grid visual con badges numerados de colores oficiales y checkmarks
- Anexos transversales: checkbox cards con iconos de documento
- CTA "Finalizar Planeación y Crear MIR": gradient card verde con icono rayo, shadow prominente
- Post-finalización: status card con fecha y link a MIR

## Criterios de aceptación
- [ ] Panel búsqueda IA renderiza con sugerencias tipo card
- [ ] Scores de relevancia con colores correctos
- [ ] Selects dependientes muestran hints cuando deshabilitados
- [ ] Grid ODS renderiza con colores y checkmarks
- [ ] CTA prominente visible cuando todos los pasos están completos
- [ ] Status card aparece después de finalizar
- [ ] `AlineacionEstrategicaTest` pasa (5 tests, 13 assertions)
```

---

## Resumen

| # | Título | Prior. | Est. | Labels | Bloqueado por |
|---|--------|--------|------|--------|---------------|
| 1 | Stepper con iconos SVG y vista móvil | 2 (High) | 3 | `frontend`, `mml` | — |
| 2 | Etapa 1: textarea auto-resize, panel IA lateral | 3 (Med) | 3 | `frontend`, `mml` | Issue 1 |
| 3 | Etapa 2: conectores CSS, panel IA lateral | 3 (Med) | 5 | `frontend`, `mml` | Issue 1 |
| 4 | Etapa 3: layout lado a lado, bulk transform IA | 3 (Med) | 5 | `frontend`, `backend`, `mml` | Issue 1 |
| 5 | Etapa 4: cards responsive, badge Enlace MIR | 3 (Med) | 5 | `frontend`, `mml` | Issue 1 |
| 6 | Etapa 5: embudo interactivo, validación real-time | 3 (Med) | 5 | `frontend`, `mml` | Issue 1 |
| 7 | Etapa 6: panel IA, ODS visual, CTA Crear MIR | 3 (Med) | 5 | `frontend`, `mml` | Issue 1 |

**Total puntos:** 31
**Camino crítico:** Issue 1 → Issues 2–7 (parallelizable)
**Entry points parallelizables:** Issues 2–7 (después de completar Issue 1)
