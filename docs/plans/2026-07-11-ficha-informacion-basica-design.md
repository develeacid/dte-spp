# Ficha de Información Básica — 5 preguntas del diagnóstico (M02 req 4) — Diseño

> Diseño aprobado 2026-07-11. Cierra **M02 req 4** del Informe de Brechas: las cinco
> preguntas estructurantes del diagnóstico no se capturan de forma estructurada; la
> Etapa 1 solo guarda el problema central.

## Objetivo

Estructurar en la Etapa 1 (Definición del Problema) las 5 preguntas del diagnóstico
del temario, materializando la **Ficha de Información Básica**.

## Las 5 preguntas (temario `modulo_02.md §2.2`)

1. ¿Qué situación negativa origina el programa? — **problema central (ya existe)**.
2. ¿De qué magnitud y naturaleza es el problema? → `magnitud`.
3. ¿A quién afecta y cómo se puede focalizar la atención? → `focalizacion`.
4. ¿Qué causa el problema y qué efectos tiene si no se atiende? → `causas_efectos`.
5. ¿Qué bienes o servicios son necesarios para resolverlo? → `bienes_servicios`.

## Decisiones (aprobadas)

- La ficha guarda **Q2–Q5**; **Q1 se reutiliza** del problema central existente
  (`ArbolNodo` PROBLEMA_CENTRAL). No se duplica el problema.
- Q2–Q5 **opcionales** (nullable); el problema (Q1) sigue requerido. Badge de
  completitud "X/5".

## Modelo de datos

- Tabla `fichas_informacion_basica` (1:1 con programa):
  `programa_presupuestario_id` (FK, unique) + `magnitud`, `focalizacion`,
  `causas_efectos`, `bienes_servicios` (todos `text` nullable) + timestamps.
- `App\Models\Mml\FichaInformacionBasica` (fillable de los 4 campos + FK).
- `ProgramaPresupuestario::fichaInformacionBasica(): HasOne`.

## Componente (Etapa 1, `DefinicionProblema`)

- Nuevas props públicas `$magnitud, $focalizacion, $causasEfectos, $bienesServicios`;
  `mount()` las carga de la ficha si existe.
- `guardar()` se extiende: además del problema central, hace upsert de la ficha
  (`updateOrCreate` por `programa_presupuestario_id`). Reglas: Q1 `required|min:20|max:1000`
  (sin cambio); Q2–Q5 `nullable|string|max:2000`.
- Propiedad computada `completitud(): int` = 1 (si problema no vacío) + count de
  Q2–Q5 no vacías. Rango 0–5.

## UI (blade Etapa 1)

- Debajo del problema, sección `x-forms.section` **"Ficha de Información Básica —
  Diagnóstico"** con 4 textareas (`wire:model` a las nuevas props), cada una
  etiquetada con su pregunta y ayuda contextual.
- Badge de completitud "X/5 preguntas respondidas" (verde si 5, ámbar si parcial).

## Testing (TDD)

`tests/Feature/Mml/DefinicionProblemaFichaTest`:
- Migración/modelo: tabla existe; `fichaInformacionBasica` es HasOne; fillable.
- `mount()` carga los valores de la ficha existente.
- `guardar()` persiste Q2–Q5 (upsert idempotente: 2 guardados → 1 sola fila).
- `completitud` correcta (problema + 2 de 4 = 3/5).
- Q2–Q5 vacías no bloquean el guardado (problema válido → guarda sin error).

## Fuera de alcance

- Export "documento único" de la ficha → brecha **M03 req 19** (separada).
- Validación IA de Q2–Q5 (la de Q1 ya existe).
- Re-cableado con Población Potencial/Objetivo (Etapa 5) o Árbol (Etapa 2); la
  ficha es el insumo narrativo del diagnóstico.

## Post-deploy

`sail artisan migrate` (1 migración privada). Sin BD pública.
