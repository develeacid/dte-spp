# Diseño UX: Wizard de Planeación MML (6 Pasos Guiados)

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

### Stepper Visual (componente compartido en todas las etapas)

```
[1 Problema] ──→ [2 Árbol ─] ──→ [3 Árbol +] ──→ [4 Alternativa] ──→ [5 Poblaciones] ──→ [6 Alineación]
     ✓                ✓                ○                  ○                   ○                  ○
  completado       completado       activo              bloqueado          bloqueado          bloqueado
```

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
- **Validación IA en tiempo real** (al perder foco, no solo al presionar botón):
  - Detecta si el problema está redactado como **estado negativo** (correcto)
  - Advierte si está redactado como **solución** ("Implementar programa de...") — el error
    más común en planeación pública
  - Advierte si describe **ausencia de recurso** ("Falta de presupuesto...") — trampa MML
- **Documento diagnóstico (opcional pero recomendado):**
  - Campo URL o upload de PDF (INEGI, CONEVAL, diagnóstico propio)
  - El sistema muestra badge "Con evidencia" / "Sin evidencia empírica" en el stepper
  - No bloquea el avance, pero lo registra para la evaluación posterior

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
  todos los nodos pendientes en secuencia (hoy solo se hace nodo por nodo)
- **Botón "Siguiente: Selección de Alternativa"** en el footer

**IA en este paso:**
- `transformarConIa(nodoId)` → ya implementado
- Nueva: `transformarTodosConIa()` → itera todos los nodos `[Pendiente]` en secuencia

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

## Paso 5 — Embudo de Poblaciones (NUEVO)

**Ruta:** `/mml/{programa}/etapa/5` *(nueva ruta — el MIR pasa a etapa/6)*
**Componente:** `EmbudoPoblaciones` (crear)

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

## Paso 6 — Alineación Estratégica (REUBICADO)

**Ruta:** `/mml/{programa}/etapa/6` *(antes estaba dentro del MIR Editor)*
**Componente:** `AlineacionEstrategica` (extraer del MIR Editor o crear nuevo)

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

## Botón Final: "Finalizar Planeación y Crear MIR"

Al completar los 6 pasos, aparece el CTA principal:

```
┌─────────────────────────────────────────────────┐
│  ✅ Planeación completa (6/6 pasos)             │
│                                                 │
│  [  Finalizar Planeación y Crear MIR  →  ]      │
│                                                 │
│  Esto generará automáticamente:                 │
│  • La estructura Fin/Propósito/Componentes      │
│  • El Propósito vendrá de tu alternativa        │
│    seleccionada en el Paso 4                    │
│  • Los niveles pre-poblados desde el EAP        │
└─────────────────────────────────────────────────┘
```

**Acción del sistema al confirmar:**
1. Marca `programa.planeacion_completada_at = now()`
2. Llama `MirPrellenadoService::prellenarDesdeEAP($programa)` (ya existe)
3. Redirige a `/mml/{programa}/etapa/7/mir` (MIR Editor, renumerado)

---

## Impacto en Rutas (reordenamiento)

| Antes | Después | Componente |
|---|---|---|
| `/etapa/1` | `/etapa/1` | `DefinicionProblema` (extender) |
| `/etapa/2` | `/etapa/2` | `ArbolProblemaBuilder` (extender) |
| `/etapa/3` | `/etapa/3` | `ArbolObjetivosBuilder` (extender) |
| `/etapa/4` | `/etapa/4` | `SeleccionAlternativas` (extender) |
| ❌ no existe | `/etapa/5` | `EmbudoPoblaciones` (crear) |
| ❌ en MIR | `/etapa/6` | `AlineacionEstrategica` (crear/extraer) |
| `/etapa/5/mir` | `/etapa/7/mir` | `MirEditor` (renumerar ruta) |

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
| Stepper visual `x-mml.stepper` | ❌ Crear |
| Paso 1 — mejoras IA en tiempo real | ⚠️ Extender |
| Paso 2 — tooltip metodológico + botón Siguiente | ⚠️ Extender |
| Paso 3 — transformar todos con IA + botón Siguiente | ⚠️ Extender |
| Paso 4 — badge enlace MIR + botón Siguiente | ⚠️ Extender |
| Paso 5 — `EmbudoPoblaciones` (migración + componente + vista) | ❌ Crear |
| Paso 6 — `AlineacionEstrategica` (extraer del MIR + ruta nueva) | ❌ Crear |
| CTA "Finalizar Planeación y Crear MIR" | ❌ Crear |
| Reordenamiento de rutas (etapa/5→7) | ❌ Requiere PR coordinado |
