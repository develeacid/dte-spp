# Issues de Linear — Sprint 8: Orquestación IA

## Proyecto

| Campo | Valor |
|-------|-------|
| **Nombre** | Sprint 8: Orquestación IA |
| **Equipo** | DTE |
| **Estado** | Backlog |
| **Descripción** | Consolidar LlmService con arquitectura resiliente, pipeline de embeddings batch, y dashboard de monitoreo IA con presupuestos y alertas. |

---

## Issue 1: Consolidar y Refactorizar LlmService

| Campo | Valor |
|-------|-------|
| **Título** | Consolidar y Refactorizar LlmService con Arquitectura Resiliente |
| **Equipo** | DTE |
| **Proyecto** | Sprint 8: Orquestación IA |
| **Estado** | Backlog |
| **Prioridad** | 1 (Urgent) — blocker de T3 |
| **Estimación** | 8 |
| **Labels** | `type: refactor`, `stack: backend`, `stack: ai` |
| **Bloqueado por** | — |

**Descripción:**

```markdown
## Contexto
Consolidar y refactorizar LlmService (creado en S3-T8, extendido en S4-S7) para unificar todos los métodos de dominio bajo una arquitectura robusta. Modo degradado, caché, prompts versionados.

**Rama:** `refactor/S8-T1-llm-service-avanzado`

## Métodos consolidados
- suggestNarrativeSyntax, validateCremaa, validateVerticalLogic, validateHorizontalLogic
- extractVariables, generateJustification, suggestAlignment, detectCausalBreaks

## Criterios de aceptación
- [ ] Todos los métodos de dominio centralizados en LlmService
- [ ] Modo degradado: fallo del API no bloquea el sistema
- [ ] Caché de respuestas con TTL configurable
- [ ] Prompts en resources/prompts/ con manifest.json
- [ ] Prompts NO validan Tipo, Dimensión o Frecuencia (delegado a Poka-Yoke)
- [ ] Tests unitarios con mocks para cada método, modo degradado y caché
```

---

## Issue 2: Pipeline de embeddings batch

| Campo | Valor |
|-------|-------|
| **Título** | Pipeline de embeddings batch |
| **Equipo** | DTE |
| **Proyecto** | Sprint 8: Orquestación IA |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: backend`, `stack: ai` |
| **Bloqueado por** | — |

**Descripción:**

```markdown
## Contexto
Comando Artisan para generar/regenerar embeddings en batch con rate limiting, reintentos y priorización.

**Rama:** `feat/S8-T2-embeddings-batch`

## Criterios de aceptación
- [ ] Comando app:embeddings-generate procesa registros con embedding IS NULL
- [ ] Procesamiento en chunks (--chunk-size) con delay configurable (--delay)
- [ ] Reintentos hasta 3 veces con backoff exponencial
- [ ] Reporte al finalizar: N generados, M fallidos, X omitidos
- [ ] Flag --force para regenerar todos
- [ ] Schedulable para ejecución nocturna
```

---

## Issue 3: Monitoreo y métricas de uso de IA

| Campo | Valor |
|-------|-------|
| **Título** | Monitoreo y métricas de uso de IA |
| **Equipo** | DTE |
| **Proyecto** | Sprint 8: Orquestación IA |
| **Estado** | Backlog |
| **Prioridad** | 2 (High) |
| **Estimación** | 8 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `stack: ai` |
| **Bloqueado por** | S8-T1 |

**Descripción:**

```markdown
## Contexto
Dashboard administrativo para monitorear consumo, costos y rendimiento del servicio de IA, con presupuestos y alertas.

**Rama:** `feat/S8-T3-monitoreo-ia`

## Métricas
- Llamadas por día/semana/mes
- Tokens consumidos y costo estimado en USD
- Tiempo promedio de respuesta
- Tasa de error
- Uso por tipo de operación y por usuario/UR

## Criterios de aceptación
- [ ] Migraciones para llm_logs (actualizada) y llm_budgets
- [ ] Dashboard con gráficas de uso, costos y rendimiento
- [ ] Sistema de alertas para umbrales de costo o tasa de error
- [ ] Política de retención (logs 90 días, métricas agregadas 2 años)
- [ ] Solo accesible para rol admin
```

---

## Resumen

| ID | Ticket | Prioridad | Est. | Labels | Bloqueado por |
|---|---|---|---|---|---|
| — | Consolidar LlmService | Urgent (1) | 8 | refactor, backend, ai | — |
| — | Pipeline embeddings batch | Medium (3) | 5 | feature, backend, ai | — |
| — | Monitoreo métricas IA | High (2) | 8 | feature, backend, frontend, ai | T1 |

**Total puntos:** 21
**Ruta crítica:** T1 → T3 (16 pts)
**Paralelizable:** T1 ∥ T2; T3 después de T1
**Tests estimados:** ~18 nuevos
