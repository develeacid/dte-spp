# Plan: S2-T10 — Pipeline de generacion de embeddings

**Ticket:** S2-T10
**Tipo:** feat
**Rama:** `feat/S2-T10-pipeline-embeddings`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T1, S2-T2, S2-T3, S0-T4

---

## Contexto

Los embeddings vectoriales son la base del motor de busqueda semantica del sistema. Este ticket implementa el pipeline completo: servicio que encapsula la llamada al API de embeddings, job asincrono que se despacha a la cola Redis, y observers en los modelos de planes que disparan la generacion automaticamente al crear/actualizar descripciones.

**Decisiones tecnicas:**
- Servicio `EmbeddingService` registrado en el Service Container como singleton
- Job `GenerateEmbedding` con `readonly` constructor properties (Laravel 11+ pattern)
- Observers en modelos de ODS, PND y PED que despachan el job al crear/actualizar campo `descripcion`
- Retry automatico con backoff exponencial si el API falla
- El registro se guarda sin embedding si el API falla — no bloquea al usuario

---

## Pre-requisitos

- S0-T4 completado (Redis como driver de colas configurado)
- S2-T1, S2-T2, S2-T3 completados (tablas con columnas `embedding`)
- API key del proveedor de embeddings configurada en `.env`

---

## Detalles Tecnicos

**Componentes a crear:**
- `App\Services\EmbeddingService` — Encapsula llamada al API del LLM, retorna array de floats
- `App\Jobs\GenerateEmbedding` — Job dispatched a cola Redis, recibe modelo y campo
- `App\Observers\EmbeddingObserver` — Observer registrado en modelos con columna `embedding`

---

## Criterios de aceptacion

- [ ] `EmbeddingService` registrado como singleton en `AppServiceProvider` con interfaz mockeable
- [ ] `EmbeddingService::generate(string $text): array` retorna array de 1536 floats
- [ ] `GenerateEmbedding` job usa cola `embeddings` (separada de `default`) para no saturar cola principal
- [ ] Job con `$tries = 3` y `$backoff = [10, 60, 300]` (backoff exponencial)
- [ ] Observer en modelos `OdsObjetivo`, `OdsMeta`, `PndEje`, `PndObjetivo`, `PndEstrategia`, y los 6 modelos PED
- [ ] Observer solo despacha job si el campo `descripcion` cambio (`$model->isDirty('descripcion')`)
- [ ] Al crear un registro, el embedding se genera automaticamente via job en cola
- [ ] Al actualizar la descripcion, el embedding se regenera
- [ ] Si el API falla despues de 3 intentos, el job se marca como `failed` y el registro queda con embedding null
- [ ] Test con mock del `EmbeddingService`: crear un `OdsObjetivo` y verificar que el job se despacha
- [ ] Test: actualizar `descripcion` despacha nuevo job; actualizar `nombre` no despacha job
- [ ] `.env.example` documentado con variables: `EMBEDDING_API_KEY`, `EMBEDDING_API_URL`, `EMBEDDING_MODEL`

---

## Notas

- Usar cola separada `embeddings` permite ajustar workers independientemente: `sail artisan queue:work --queue=embeddings`
- El `EmbeddingService` debe tener una interfaz (`EmbeddingServiceInterface`) para facilitar mocking en tests
- En ambiente `testing`, el Observer puede desactivarse con `Model::withoutEvents()` para tests que no necesitan embeddings
- El endpoint del API de embeddings es configurable para permitir cambio de proveedor sin tocar codigo