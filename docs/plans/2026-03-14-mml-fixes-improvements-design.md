# Diseño: Correcciones y Mejoras MML Wizard + IA

**Fecha**: 2026-03-14
**Origen**: Observaciones QA sesión 13/03/26
**Enfoque**: Sprints por criticidad (Fase 1 → 2 → 3)

---

## Clasificación de Observaciones

### Bloqueantes (bugs/errores funcionales)
1. Bug paso 2: efectos se agregan como causas
2. Error 500 en botón "Finalizar planeación y crear MIR"

### Alta prioridad (funcionalidad solicitada por admins)
3. Sugerencias de causas indirectas con IA (paso 2)
4. Árbol auto-generado con 2 causas + 2 efectos + 2 causas indirectas de ejemplo
5. Paso 6: alineación base nivel PED con mejora visual ODS/PND
6. Paso 7: vista MIR estructural (lectura) vs. modo edición separado
7. Paso 7: flujo de validación IA inconsistente (re-validar muestra errores anteriores)

### Media prioridad (mejoras UX)
8. Paso 5: interfaz se rompe con números >5 cifras
9. Paso 7: generar fórmula de cálculo con IA
10. Dashboard de indicadores con niveles colapsables y gráficas
11. Paso 7: snapshots visibles en vista estructural

### Baja prioridad (backlog)
12. Diagrama de contexto IA entre pasos (documentación)
13. Drag & drop de nodos en el árbol
14. Funcionalidad de importar MIR (`/mml/importar`) - prueba y documentación
15. Detección de correlación/fusión entre nodos similares

---

## Fase 1 — Fixes Críticos

### 1.1 Bug: Efectos se agregan como causas (Paso 2)

**Archivo**: `resources/views/livewire/mml/arbol-problema-builder.blade.php` (línea ~230)

**Problema confirmado**: El botón "Agregar al árbol" siempre pasa `'causa_directa'` como tercer argumento a `agregarSugerencia()`, sin importar si la sugerencia vino de "Sugerir efectos".

**Fix**: El componente `ArbolProblemaBuilder.php` ya tiene la propiedad `$tipoSugerencia` que se setea al llamar `sugerirConIa('causa_directa')` o `sugerirConIa('efecto_directo')`. El template debe usar esa propiedad en vez del string hardcodeado:

```blade
wire:click="agregarSugerencia('...', {{ $problemaCentral->id }}, '{{ $tipoSugerencia }}')"
```

### 1.2 Error 500: Botón "Finalizar Planeación y Crear MIR" (Paso 6)

**Archivo**: `app/Livewire/Mml/AlineacionEstrategica.php` → `finalizarPlaneacion()`
**Servicio**: `app/Services/Mml/MirPrellenadoService.php` → `prellenarDesdeEAP()`

**Diagnóstico necesario**: El error 500 puede venir de:
- Falta de alternativa seleccionada (paso 4 no completado)
- Árbol de objetivos sin nodos transformados
- Relación faltante entre nodos y alternativa

**Plan**: Revisar logs, agregar validación defensiva antes de llamar al servicio, y asegurar que el botón solo aparezca cuando todos los prerequisitos existan.

### 1.3 Interfaz rota con números >5 cifras (Paso 5)

**Archivo**: `resources/views/livewire/mml/embudo-poblaciones.blade.php`

**Fix**: Aplicar `number_format()` para display y ajustar contenedores CSS del embudo para que sean responsivos con números grandes. Posiblemente usar abreviaciones (e.g., 1.2M) o reducir font-size dinámicamente.

---

## Fase 2 — Mejoras Funcionales IA

### 2.1 Sugerencias de causas indirectas con IA (Paso 2)

**Estado actual**: El asistente solo sugiere `causa_directa` y `efecto_directo`. Las causas indirectas se crean manualmente como hijos de una causa directa.

**Diseño**: Extender `sugerirConIa()` para aceptar `'causa_indirecta'` con contexto adicional (problema central + causa directa padre). En la UI, agregar un botón "Sugerir causas indirectas" dentro de cada nodo de causa directa.

**Impacto en el sistema**: Mínimo — extensión natural del flujo existente. El nodo se crea con `parent_id` apuntando a la causa directa, tipo `causa_indirecta`. Mantiene "IA propone, usuario decide" porque el usuario elige cuáles agregar.

### 2.2 Árbol auto-generado de ejemplo (Paso 2)

**Solicitud**: Crear automáticamente un árbol con 2 causas directas, 2 efectos directos y 2 causas indirectas por cada causa directa (total: 10 nodos) basado en el problema central.

**Diseño**: Nuevo método `generarArbolEjemplo()` que:
1. Llama a la IA con el problema central pidiendo un árbol completo en formato JSON estructurado
2. Presenta el árbol generado al usuario en un modal de previsualización
3. El usuario puede aceptar, modificar o rechazar antes de que se persista

**Sobre "IA propone, usuario decide"**: Se respeta porque:
- El árbol se muestra como **propuesta** antes de guardarse
- El usuario debe confirmar explícitamente con un botón "Aceptar árbol propuesto"
- Cada nodo individual sigue siendo editable/eliminable después
- Si el usuario ya tiene nodos creados, se pregunta si desea reemplazar o complementar

### 2.3 Vista MIR estructural vs. modo edición (Paso 7)

**Problema**: El usuario entra a la MIR y ve todo editable de golpe — inputs, botones de IA, etc. Es abrumador.

**Diseño — dos modos**:

| Aspecto | Vista Lectura (default) | Vista Edición |
|---------|------------------------|---------------|
| Resumen Narrativo | Texto plano | Textarea editable |
| Indicadores | Tabla read-only: nombre, tipo, dimensión, fórmula, frecuencia | Formulario con inputs + botones IA |
| Medios de Verificación | Inline bajo su indicador en la tabla | Editables con add/remove |
| Supuestos | Texto plano | Textarea |
| Acciones | Botón "Editar nivel" por cada nivel | Guardar / Cancelar |
| Snapshots | Selector de versión visible | Oculto |

**Implementación**: Propiedad `$editandoNivel` en `MirEditor.php` que controla qué nivel está en modo edición (null = todos en lectura). Botón "Editar" por nivel, no global.

### 2.4 Validación IA inconsistente (Paso 7)

**Problema**: Al validar resumen narrativo, aceptar sugerencia, y re-validar, sigue mostrando errores del texto anterior.

**Causa probable**: Caché de LlmService usa MD5 del texto original. Al aceptar sugerencia el texto cambia, pero si el componente no refresca el binding antes de re-validar, envía el texto viejo.

**Fix**:
1. Al aceptar sugerencia, limpiar estado de validación (`$validacionResultado = null`)
2. Asegurar que `validarSintaxis()` use el valor actual del modelo
3. Invalidar caché LLM cuando el texto cambia

### 2.5 Generación de fórmula de cálculo con IA (Paso 7)

**Diseño**: Nuevo método `sugerirFormula()` que recibe nombre del indicador, tipo, dimensión y resumen narrativo del nivel, y propone una fórmula matemática. El usuario la acepta o modifica. Complementa a `extraerVariables()` existente.

### 2.6 Alineación base nivel PED con mejora visual (Paso 6)

**Solicitud**: Búsqueda de alineación parte del nivel PED. Al tener matriz ODS-PND-PED, iluminar solo ODS y PND como referencias.

**Diseño**:
- Búsqueda semántica en `PedObjetivoEstrategico` se mantiene como base
- Al seleccionar un objetivo PED, cargar automáticamente relaciones PED→PND y PED→ODS
- Mostrar ODS y PND como badges/chips de referencia (coloreados, no clickeables)
- Contexto de las 5 fases previas se incluye en el prompt de búsqueda semántica

---

## Fase 3 — UX / Dashboard

### 3.1 Dashboard de indicadores con niveles colapsables y gráficas

**Problema actual**: Panel muestra todos los niveles MIR en lista plana desordenada.

**Diseño — Dashboard jerárquico colapsable**:

```
┌─ Programa: ISM-001 Impulso al Sector Mezcalero
│
├─▶ FIN (colapsado por default)
│   └─ Click → expande: resumen narrativo, supuestos, alineación PED
│       └─ Indicador(es) del FIN → click → vista detalle
│
├─▶ PROPOSITO (colapsado)
│   └─ Click → expande: resumen, indicador(es)
│       └─ Indicador → click → vista detalle
│
├─▼ COMPONENTE 1 (expandido ejemplo)
│   ├─ Resumen narrativo, UR responsable
│   ├─ Indicador C1.1: [barra progreso + semáforo]
│   ├─ Indicador C1.2: [barra progreso + semáforo]
│   └─▶ ACTIVIDAD 1.1 (colapsable anidada)
│       └─ Indicador A1.1.1: [barra progreso + semáforo]
│
└─▶ COMPONENTE 2 (colapsado)
```

**Dos niveles de colapso**: Propósito-Componente (nivel 1) y Actividad (nivel 2 anidado).

**Vista detalle por indicador** (click en indicador):
- Info general: nombre, tipo, dimensión, sentido, frecuencia, fórmula
- **Desglose anual**: tabla con periodos según frecuencia declarada:
  - Semestral → 2 columnas (S1, S2) + total anual
  - Trimestral → 4 columnas (T1, T2, T3, T4) + total anual
- Cada celda: meta programada, avance real, % cumplimiento, semáforo
- **Gráfica de evolución**: línea temporal meta vs avance real
- Tipo de gráfica adaptado al indicador:
  - Indicadores de gestión (porcentaje) → gráfica de línea con zona meta
  - Indicadores estratégicos (absolutos) → barras agrupadas (meta vs real)
  - Sentido ascendente → verde cuando avance ≥ meta
  - Sentido descendente → verde cuando avance ≤ meta

### 3.2 Snapshots en vista MIR estructural (Paso 7)

**Diseño**: En vista de lectura (2.3), selector dropdown en header:
- "Versión actual" (default)
- "v3 — 2026-03-10 14:30"
- "v2 — 2026-03-08 09:15"

Al seleccionar versión anterior, vista estructural muestra datos del snapshot en modo solo-lectura con banner "Viendo versión histórica". Botón "Editar" se oculta.

### 3.3 Documentación: Diagrama de contexto IA entre pasos

Crear `docs/architecture/ai-context-flow.md` con diagrama que responda preguntas de admins:

```
Paso 1 → DB: problema_central (ArbolNodo)
  │ ¿Se mantiene al paso 2? SÍ, se lee de BD
  ▼
Paso 2 → DB: causas + efectos (ArbolNodo con parent_id)
  │ ¿Contexto entre nodos? SÍ, prompt incluye nodos existentes
  │ ¿Puede repetir nodos? POSIBLE → mitigación: nodos existentes en prompt
  │ ¿Fusión de similares? NO actual → backlog futuro
  ▼
Paso 3 → DB: árbol objetivos (transformación 1:1 desde paso 2)
  │ Contexto paso anterior: SÍ, lee árbol problemas completo
  ▼
Paso 4 → DB: alternativas con nodos asociados
  │ Contexto: SÍ, lee árbol objetivos
  │ ¿Alternativas contradictorias? → IA evalúa coherencia individual,
  │   NO cruzada entre alternativas (documentar limitación)
  ▼
Paso 5 → DB: poblaciones (independiente de IA)
  ▼
Paso 6 → DB: alineación PED (búsqueda semántica)
  │ Contexto: problema central + objetivo central
  ▼
Paso 7 → MIR (prellenada desde pasos 3-4-6)
```

Cada paso lee de BD, no de sesión ni chat. No hay "sesión de IA" que se pierda.

---

## Items de Backlog (fuera de scope)

| Item | Razón de exclusión |
|------|-------------------|
| Drag & drop de nodos | Requiere librería JS, esfuerzo alto, bajo impacto funcional |
| Correlación/fusión de nodos similares | Requiere comparación semántica N×N, diseño complejo |
| Importar MIR (`/mml/importar`) | Infraestructura existe, necesita pruebas con datos reales — sesión separada |
| Validación cruzada entre alternativas (paso 4) | Mejora de IA, requiere diseño de prompt multi-alternativa |
