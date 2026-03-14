# Diseño UX: Wizard de Planeación MML (7 Etapas: 6 de Planeación + MIR)

> Principio rector: **La IA sugiere, el usuario decide.**
> El sistema nunca impone — propone, valida y guía. El planeador conserva
> la autoría de cada decisión. La IA es el copiloto metodológico.

---

## Por qué un Wizard y no un formulario libre

En la administración pública, un formulario en blanco con 50 campos produce:
- Texto genérico copiado de otros programas
- Poblaciones objetivo inventadas sin sustento estadístico
- MIRs que no reflejan la realidad del problema

Un Wizard secuencial con validación por paso **obliga al Planeador a pensar antes de avanzar**.
Cada paso desbloquea el siguiente solo cuando cumple requisitos metodológicos mínimos.

---

## Arquitectura del Wizard

### Stepper Visual (componente `x-mml.stepper`, compartido en etapas 1-6)

```
[1 Problema] ──→ [2 Árbol −] ──→ [3 Árbol +] ──→ [4 Alternativa] ──→ [5 Poblaciones] ──→ [6 Alineación]  ──→  [7 MIR]
     ✓                ✓                ○                  ○                   ○                  ○                  ○
  completado       completado       activo              bloqueado          bloqueado          bloqueado         (separado)
```

> El stepper visual (`x-mml.stepper`) muestra los pasos 1-6 de planeación.
> La Etapa 7 (MIR) se accede tras finalizar la planeación desde el Paso 6
> y tiene su propia interfaz independiente (`MirEditor`).

**Estados de cada paso:**
- `completado` — Círculo verde con checkmark. Clickeable (el usuario puede regresar).
- `activo` — Círculo azul con número. Paso actual.
- `pendiente` — Círculo gris con número. Accesible si el anterior está completado.
- `bloqueado` — Círculo gris con candado. No se puede acceder hasta completar el anterior.

**Implementación:** Componente Blade `x-mml.stepper` que recibe el programa y calcula
el estado de cada paso consultando la DB (`arbol_problemas`, `arbol_objetivos`,
`alternativas`, `poblaciones_programa`, `alineaciones_programa`).

---

## Paso 1 — Definición del Problema Central

**Ruta:** `/mml/{programa}/etapa/1`
**Componente:** `DefinicionProblema` (ya existe, extender)

### UX actual
- Textarea para el problema central
- Botón "Validar con IA"

### UX objetivo (mejoras)
- **Redacción guiada:** El campo muestra el placeholder
  `"Ej: Alta tasa de desnutrición infantil en comunidades rurales del estado"`
- **Validación IA al hacer clic** (botón "Validar con IA", no al perder foco):
  - Detecta si el problema está redactado como **estado negativo** (correcto)
  - Advierte si está redactado como **solución** ("Implementar programa de...") — el error
    más común en planeación pública
  - Advierte si describe **ausencia de recurso** ("Falta de presupuesto...") — trampa MML
- **Documento diagnóstico (opcional pero recomendado):** *(no implementado aún)*
  - ~Campo URL o upload de PDF (INEGI, CONEVAL, diagnóstico propio)~
  - ~El sistema muestra badge "Con evidencia" / "Sin evidencia empírica" en el stepper~
  - ~No bloquea el avance, pero lo registra para la evaluación posterior~

**IA en este paso:**
- `validate()` → detecta tipo de redacción (estado negativo / solución / ausencia recurso)
- Sugiere reformulación si detecta el error
- El planeador acepta o descarta la sugerencia

**Criterio para completar el paso:** `descripcion` guardado (≥ 50 caracteres).

---

## Paso 2 — Árbol de Problemas

**Ruta:** `/mml/{programa}/etapa/2`
**Componente:** `ArbolProblemaBuilder` (ya existe, extender)

### UX objetivo (mejoras)
- **Tooltip metodológico** al agregar causa:
  - Si el texto contiene "falta de", "ausencia de", "sin presupuesto" → warning suave:
    *"La MML considera que la escasez de recursos es un síntoma, no la causa raíz.
    ¿Puedes describir el problema que genera esa escasez?"*
  - El warning no bloquea — informa. El planeador puede ignorarlo.
- **Botón "Siguiente: Árbol de Objetivos"** en el footer (hoy no existe)
- **Contador de completitud:** "3 causas / 2 efectos agregados"

**IA en este paso:**
- `suggest('causa')` → propone 3 causas directas basadas en el problema central
- `suggest('efecto')` → propone 3 efectos directos
- El planeador acepta una a una o descarta todas

**Criterio para completar el paso:** Al menos 1 causa directa + 1 efecto directo guardados.

---

## Paso 3 — Árbol de Objetivos

**Ruta:** `/mml/{programa}/etapa/3`
**Componente:** `ArbolObjetivosBuilder` (ya existe, extender)

### UX objetivo (mejoras)
- **Explicación visual:** Mostrar lado a lado problema (rojo) → objetivo (verde)
  con flecha de transformación entre ellos
- **Generación masiva con IA:** Botón "Transformar todos con IA" que procesa
  todos los nodos pendientes en secuencia (implementado en `transformarTodosConIa()`)
- **Botón "Siguiente: Selección de Alternativa"** en el footer

**IA en este paso:**
- `transformarConIa(nodoId)` → ya implementado
- `transformarTodosConIa()` → itera todos los nodos `[Pendiente]` en secuencia (implementado)

**Criterio para completar el paso:** Ningún nodo en estado `[Pendiente de transformación]`.

---

## Paso 4 — Selección de la Alternativa

**Ruta:** `/mml/{programa}/etapa/4`
**Componente:** `SeleccionAlternativas` (ya existe, extender)

### UX objetivo (mejoras)
- **Explicación del paso:** Banner informativo
  *"Agrupa los Medios de tu Árbol de Objetivos en alternativas de solución.
  Solo una alternativa se convertirá en el Propósito de tu MIR."*
- **Badge "Enlace MIR"** en la alternativa seleccionada:
  *"Esta alternativa generará el nivel Propósito en tu MIR"*
- **Botón "Siguiente: Poblaciones"** en el footer

**IA en este paso:**
- `evaluarConIa(alternativaId)` → ya implementado (viabilidad técnica/institucional/presupuestal)

**Criterio para completar el paso:** Al menos 1 alternativa marcada como `seleccionada`.

---

## Paso 5 — Embudo de Poblaciones (implementado)

**Ruta:** `/mml/{programa}/etapa/5`
**Componente:** `EmbudoPoblaciones`

> Ver `docs/architecture/embudo-poblaciones-padron-beneficiarios.md` para la
> arquitectura de datos completa y la conexión con el Padrón de Beneficiarios.

### UX: Visualización tipo Embudo

```
┌─────────────────────────────────────────────┐
│ 🌍 POBLACIÓN DE REFERENCIA                  │
│    100,000  Niños                           │
│    Fuente: INEGI Censo 2020                 │
├─────────────────────────────────────────────┤
│   🎯 POBLACIÓN POTENCIAL                    │
│      20,000  Niños con desnutrición         │
│      Fuente: CONEVAL 2023                   │
├─────────────────────────────────────────────┤
│     📋 POBLACIÓN OBJETIVO                   │
│        5,000  Niños en región sierra        │
│        (limitado por presupuesto)           │
├─────────────────────────────────────────────┤
│       ✓ POBLACIÓN ATENDIDA                  │
│          (se calcula desde el Padrón        │
│           durante operación del programa)   │
└─────────────────────────────────────────────┘
```

### Campos del formulario

| Campo | Tipo | Validación |
|---|---|---|
| `unidad_medida` | text | required, ej "Niños", "Familias", "MIPYMES" |
| `referencia_cantidad` | integer | > 0 |
| `referencia_fuente` | text | opcional (INEGI, CONEVAL, estudio propio) |
| `potencial_cantidad` | integer | > 0, ≤ referencia |
| `potencial_fuente` | text | opcional |
| `objetivo_cantidad` | integer | > 0, ≤ potencial |
| `objetivo_justificacion` | text | opcional (por qué este recorte) |

### Validación dura (bloquea guardar)

```
Objetivo ≤ Potencial ≤ Referencia
```

- Implementada como `CHECK` en migración PostgreSQL
- Validada en Livewire antes de guardar con mensaje de error específico:
  *"La Población Objetivo (5,001) no puede ser mayor a la Potencial (5,000).
  Revisa las cifras o ajusta la Población Objetivo."*

**IA en este paso:**
- Al escribir el problema central (ya guardado en Paso 1), la IA puede sugerir
  una fuente de datos relevante: *"Para desnutrición infantil en México,
  CONEVAL publica la ENIGH y el INPI el Atlas de Vulnerabilidad."*
- Sugerencia de rango para Población Potencial basada en el texto del problema

**Criterio para completar el paso:** Los 3 niveles guardados con validación matemática cumplida.

---

## Paso 6 — Alineación Estratégica (implementado)

**Ruta:** `/mml/{programa}/etapa/6`
**Componente:** `AlineacionEstrategica`

### UX: Selects dependientes

```
Plan Estatal de Desarrollo (PED)
├── Eje estratégico        [select] → "Eje 2: Bienestar Social"
├── Tema                   [select] → "Salud y Nutrición"
└── Objetivo estratégico   [select] → "OE 2.3 Reducir desnutrición infantil"

ODS (Agenda 2030)
└── Objetivo(s)            [multiselect] → "ODS 2: Hambre Cero", "ODS 3: Salud"

Anexos Transversales
└── Checkboxes             → [✓] Igualdad de Género  [ ] Discapacidad  [✓] Infancia
    (impactarán los campos requeridos en el Padrón de Beneficiarios)
```

**IA en este paso:**
- Búsqueda semántica (embeddings) sobre el catálogo PED para sugerir el
  Objetivo Estratégico más afín al problema central del programa
- *"Basado en tu problema 'Alta tasa de desnutrición infantil', el sistema
  sugiere alinearlo con OE 2.3. ¿Es correcto?"*
- El planeador confirma o elige manualmente

**Criterio para completar el paso:** Al menos 1 objetivo PED seleccionado.

---

## Botón Final: "Finalizar Planeación y Crear MIR" (implementado)

Al completar los 6 pasos de planeación, aparece el CTA en la vista de Alineación Estratégica
(Paso 6), implementado en `AlineacionEstrategica::finalizarPlaneacion()`:

**Acción del sistema al confirmar:**
1. Valida que todos los prerrequisitos estén completos (árbol de objetivos, alternativa seleccionada, poblaciones)
2. Marca `programa.planeacion_completada_at = now()`
3. Llama `MirPrellenadoService::prellenar($programa)`
4. Redirige a la ruta `mml.mir` (MIR Editor)

---

## Paso 7 — Matriz de Indicadores para Resultados (MIR)

**Ruta:** `/mml/{programa}/mir`
**Componente:** `MirEditor` (implementado)

El MIR Editor permite construir la Matriz de Indicadores completa con niveles
Fin, Propósito, Componentes y Actividades. Incluye validación de sintaxis con IA,
validación CREMAA, búsqueda semántica de alineación, gestión de snapshots/versiones
y asignación de URs coadyuvantes.

> **Nota:** Actualmente la Etapa 7 (MIR) no tiene gate de completitud (completion gate).
> El usuario puede trabajar en la MIR sin restricción de que todos los campos
> estén completos. No hay un botón de "finalizar MIR" que bloquee hasta cumplir
> requisitos mínimos.

---

## Rutas Actuales

| Ruta | Componente | Estado |
|---|---|---|
| `/mml/{programa}/etapa/1` | `DefinicionProblema` | Implementado |
| `/mml/{programa}/etapa/2` | `ArbolProblemaBuilder` | Implementado |
| `/mml/{programa}/etapa/3` | `ArbolObjetivosBuilder` | Implementado |
| `/mml/{programa}/etapa/4` | `SeleccionAlternativas` | Implementado |
| `/mml/{programa}/etapa/5` | `EmbudoPoblaciones` | Implementado |
| `/mml/{programa}/etapa/6` | `AlineacionEstrategica` | Implementado |
| `/mml/{programa}/mir` | `MirEditor` | Implementado |

---

## Principios de Implementación

1. **La IA sugiere, el usuario decide** — Ninguna sugerencia se aplica automáticamente.
   Siempre hay un botón "Aceptar" o "Descartar".

2. **Validación progresiva** — Cada paso valida solo lo estrictamente necesario para
   avanzar. No se bloquea por campos opcionales.

3. **Navegación libre hacia atrás** — El usuario siempre puede regresar a un paso
   completado para corregir. El sistema recalcula el estado del stepper.

4. **Persistencia inmediata** — Los cambios se guardan al perder el foco o al
   presionar guardar explícito. No se pierde trabajo.

5. **Sin modales para flujos principales** — Cada paso es una página completa,
   consistente con la arquitectura frontend del proyecto.

---

## Estado de Implementación

| Componente | Estado |
|---|---|
| Stepper visual `x-mml.stepper` (6 pasos de planeación) | ✅ Implementado |
| Paso 1 — Validación IA on-click + placeholder guiado | ✅ Implementado |
| Paso 1 — Documento diagnóstico (upload/URL) | ❌ No implementado |
| Paso 2 — Árbol de problemas con sugerencias IA | ✅ Implementado |
| Paso 3 — Transformar nodos con IA (individual y masivo) | ✅ Implementado |
| Paso 4 — Selección de alternativas con evaluación IA | ✅ Implementado |
| Paso 5 — `EmbudoPoblaciones` (formulario + validación embudo) | ✅ Implementado |
| Paso 6 — `AlineacionEstrategica` (PED + ODS + anexos + búsqueda semántica) | ✅ Implementado |
| CTA "Finalizar Planeación y Crear MIR" (en Paso 6) | ✅ Implementado |
| Paso 7 — `MirEditor` (MIR completa con validaciones IA) | ✅ Implementado |
| Paso 7 — Gate de completitud (bloqueo hasta cumplir mínimos) | ❌ No implementado |
