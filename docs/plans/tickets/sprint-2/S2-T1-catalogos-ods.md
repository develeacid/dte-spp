# Plan: S2-T1 — Migraciones y modelos para catalogos ODS

**Ticket:** S2-T1
**Tipo:** feat
**Rama:** `feat/S2-T1-catalogos-ods`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S0-T2

---

## Contexto

Los Objetivos de Desarrollo Sostenible (ODS) de la Agenda 2030 son el nivel mas alto de la cascada de planes. Son **catalogos inmutables** (no editables por el usuario) que sirven como referencia para la Matriz de Alineacion. Se requieren dos tablas: `ods_objetivos` (17 objetivos) y `ods_metas` (169 metas). Ambas incluyen columnas `vector(1536)` para busqueda semantica con pgvector.

**Decisiones tecnicas:**
- Columnas `embedding` usan tipo `vector(1536)` de pgvector (requiere extension habilitada en S0-T2)
- Seeder carga datos reales desde archivo Markdown fuente en `docs/data/`
- Se usa `updateOrCreate()` en seeders para idempotencia
- Indice HNSW en columnas de embedding para performance en busquedas por similitud

---

## Pre-requisitos

- S0-T2 completado (extension pgvector habilitada en PostgreSQL)
- Extension `vector` activa verificable con `SELECT extname FROM pg_extension WHERE extname = 'vector'`

---

## Detalles Tecnicos

**Campos clave:**
- `ods_objetivos`: id, numero (1-17), nombre, descripcion, embedding (vector 1536)
- `ods_metas`: id, ods_objetivo_id (FK), clave ("1.1", "1.2"), descripcion, embedding

**Relaciones Eloquent:**
- `OdsObjetivo hasMany OdsMeta`
- `OdsMeta belongsTo OdsObjetivo`

---

## Criterios de aceptacion

- [ ] Migraciones con `up()` y `down()` completos — `down()` elimina tablas en orden inverso (metas antes que objetivos)
- [ ] Columnas `embedding` de tipo `vector(1536)` creadas correctamente — verificar con `\d ods_objetivos` en psql
- [ ] Modelos `App\Models\OdsObjetivo` y `App\Models\OdsMeta` con `$fillable`, `casts()` y relaciones definidas
- [ ] Seeder `OdsSeeder` carga los 17 ODS y sus 169 metas desde archivo Markdown fuente con `updateOrCreate()`
- [ ] `sail artisan migrate:fresh --seed` ejecuta sin errores
- [ ] `sail artisan migrate:rollback` revierte sin errores
- [ ] Tinker: `OdsObjetivo::count()` retorna 17, `OdsMeta::count()` retorna 169
- [ ] Archivo `docs/schema/ods.md` documenta estructura de tablas y relaciones

---

## Notas

- Los embeddings se generaran posteriormente via el pipeline de S2-T10; por ahora las columnas quedan en null
- El archivo fuente Markdown con los ODS debe colocarse en `docs/data/ods-agenda-2030.md`
- No crear indices HNSW en esta migracion — se crean en S2-T11 cuando el servicio de busqueda lo necesite