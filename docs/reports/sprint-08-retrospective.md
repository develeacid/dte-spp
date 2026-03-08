# Sprint 8 Retrospective — Orquestación IA

**Fecha:** 2026-03-07
**Baseline inicial:** 392 tests, 7 skipped
**Baseline final:** 426 tests, 7 skipped (34 nuevos)
**Ramas completadas:** 3/3

---

## Resumen de Ejecución

| Task | Rama | Tests | Estado |
|------|------|:---:|--------|
| T1: Consolidar LlmService | `refactor/S8-T1-llm-service-avanzado` | 15 | Completado |
| T2: Pipeline embeddings batch | `feat/S8-T2-embeddings-batch` | 10 | Completado |
| T3: Monitoreo métricas IA | `feat/S8-T3-monitoreo-ia` | 8 | Completado |

---

## Criterios de Aceptación — Cumplimiento

### T1: Consolidar y Refactorizar LlmService
- [x] Todos los métodos de dominio centralizados en LlmService (8 métodos)
- [x] Modo degradado: fallo del API no bloquea el sistema
- [x] Caché de respuestas con TTL configurable
- [x] Prompts en resources/views/prompts/ con manifest.json (13 prompts versionados)
- [x] Prompts NO validan Tipo, Dimensión o Frecuencia (delegado a Poka-Yoke)
- [x] Tests unitarios con mocks para cada método, modo degradado y caché

### T2: Pipeline embeddings batch
- [x] Comando app:embeddings-generate procesa registros con embedding IS NULL
- [x] Procesamiento en chunks (--chunk-size) con delay configurable (--delay)
- [x] Reintentos hasta 3 veces con backoff exponencial
- [x] Reporte al finalizar: N generados, M fallidos, X omitidos
- [x] Flag --force para regenerar todos
- [x] Schedulable para ejecución nocturna (02:00)

### T3: Monitoreo y métricas de uso de IA
- [x] Migraciones para llm_logs (cost_usd, prompt_version) y llm_budgets
- [x] Dashboard con métricas de uso, costos y rendimiento
- [x] Sistema de alertas para umbrales de costo (LlmBudgetAlertNotification)
- [x] Política de retención implementada (llm:cleanup-logs --days=90, mensual)
- [x] Solo accesible con administrar_usuarios

---

## Decisiones Técnicas Relevantes

1. **Degraded mode pattern:** LlmService retorna respuestas fallback marcadas cuando API no está disponible. Consumers reciben null o texto placeholder sin excepción.

2. **Cache key strategy:** md5(method + prompt + context + model). transform() nunca se cachea. Cache hits se loguean con status='cache_hit'.

3. **Prompt versioning:** manifest.json junto a los templates Blade. Versión incluida en logs para auditoría. No se migran a directorio separado — Blade se mantiene como motor de renderizado.

4. **HasEmbedding trait:** Aplicado a 10 modelos PED/ODS/PND. Cada modelo define `getEmbeddableText()` con su representación textual.

5. **Cost estimation:** $0.15/1M input tokens, $0.60/1M output tokens (gpt-4o-mini pricing). Calculado en LlmBudgetService después de cada llamada exitosa.

6. **Budget scopes:** global, team, user. Alertas automáticas al cruzar threshold (default 80%).

---

## Deuda Técnica

1. **LlmService consumers sin migrar a domain methods:** Los Livewire components (DefinicionProblema, ArbolProblemaBuilder, etc.) aún llaman directamente a suggest/validate/transform en lugar de usar los nuevos domain methods consolidados. Migrar gradualmente.
2. **Dashboard charts:** MonitoreoIa muestra datos en tablas. Agregar gráficas con Chart.js o Alpine.js en sprint futuro.
3. **Budget management UI:** No hay CRUD para crear/editar presupuestos. Admin debe crear registros directamente o vía tinker.
4. **Embedding cost tracking:** EmbeddingService no integra con LlmBudgetService. Agregar tracking de costos de embeddings.

---

## Esquema de BD al cierre del Sprint 8

**Tablas nuevas:** llm_budgets
**Tablas modificadas:** llm_logs (+cost_usd, +prompt_version)
**Modelos nuevos:** LlmBudget
**Traits nuevos:** HasEmbedding (aplicado a 10 modelos)
**Comandos nuevos:** app:embeddings-generate, llm:cleanup-logs
**Servicios nuevos/modificados:** EmbeddingService (generateBatch), LlmBudgetService, LlmService (domain methods + cache + fallback)
**Config modificado:** llm.php (+cache, +fallback, +prompts), embedding.php (+batch)
