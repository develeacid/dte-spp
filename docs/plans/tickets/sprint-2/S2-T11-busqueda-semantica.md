# Plan: S2-T11 — Servicio de busqueda semantica por similitud

**Ticket:** S2-T11
**Tipo:** feat
**Rama:** `feat/S2-T11-busqueda-semantica`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T10

---

## Contexto

Servicio que realiza busquedas por similitud de cosenos usando pgvector. Es el motor de sugerencias inteligentes del sistema: se usa en la Matriz de Alineacion para sugerir correspondencias, y en la MIR (Sprint 4) para sugerir alineacion con la cascada de planes. Utiliza el operador `<=>` de pgvector (distancia coseno) e indices HNSW para performance.

**Decisiones tecnicas:**
- Servicio `SemanticSearchService` con metodo generico `findSimilar()` que funciona contra cualquier tabla con columna `embedding`
- Indices HNSW (Hierarchical Navigable Small World) en todas las columnas de embedding — mejor performance que IVFFlat para datasets < 1M registros
- DTO `SimilarityResult` para tipar los resultados con score de similitud
- Umbral minimo de similitud configurable para filtrar resultados irrelevantes

---

## Pre-requisitos

- S2-T10 completado (pipeline de embeddings funcional para generar vectors)
- Registros con embeddings generados en al menos una tabla

---

## Detalles Tecnicos

**Componentes a crear:**
- `App\Services\SemanticSearchService` — Servicio de busqueda por similitud
- `App\DTOs\SimilarityResult` — DTO con modelo, score y distancia
- Migracion para crear indices HNSW en todas las tablas con columna `embedding`

**Metodo principal:**
```php
findSimilar(string $texto, string $modelClass, int $limite = 5, float $umbralMinimo = 0.7): Collection<SimilarityResult>
```

**Flujo interno:**
1. Genera embedding del texto de entrada via `EmbeddingService`
2. Ejecuta query: `SELECT *, 1 - (embedding <=> $vector) as score FROM tabla WHERE 1 - (embedding <=> $vector) >= $umbral ORDER BY embedding <=> $vector LIMIT $limite`
3. Retorna coleccion de `SimilarityResult` ordenados por score descendente

---

## Criterios de aceptacion

- [ ] `SemanticSearchService` registrado en Service Container
- [ ] Metodo `findSimilar()` funciona contra todas las tablas con embeddings (ODS, PND, PED, Programas Derivados)
- [ ] Migracion crea indices HNSW en todas las columnas `embedding` — verificar con `\di` en psql
- [ ] DTO `SimilarityResult` con propiedades: `model`, `score` (float 0-1), `distance` (float)
- [ ] Umbral minimo de similitud configurable via parametro (default 0.7)
- [ ] Limite de resultados configurable via parametro (default 5)
- [ ] Test con mock: buscar "reducir pobreza" contra ODS retorna resultados ordenados por score
- [ ] Test: busqueda con umbral alto (0.99) retorna coleccion vacia
- [ ] Test: busqueda con limite 1 retorna exactamente 1 resultado
- [ ] Configuracion en `config/embedding.php`: `similarity_threshold`, `max_results`, `hnsw_ef_search`

---

## Notas

- Los indices HNSW tienen parametros `m` y `ef_construction` que afectan precision vs velocidad — usar defaults de pgvector para iniciar
- El operador `<=>` retorna distancia (0 = identico), no similitud — el score se calcula como `1 - distancia`
- En tests, se puede usar un mock de `EmbeddingService` que retorna vectores predefinidos para simular busquedas
- Este servicio se reutiliza en S4-T8 (alineacion automatica MIR) y S5-T5 (vinculacion de importados)