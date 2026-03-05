# Plan: S2-T3 — Migraciones y modelos para PED

**Ticket:** S2-T3
**Tipo:** feat
**Rama:** `feat/S2-T3-modelos-ped`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S0-T2

---

## Contexto

El Plan Estatal de Desarrollo (PED) es la estructura central del sistema. Jerarquia de 6 niveles: Plan → Ejes → Temas → Objetivos Estrategicos → Estrategias → Lineas de Accion. A diferencia de ODS y PND (catalogos inmutables), el PED es **editable** por el planeador (CRUD en S2-T6). Solo un plan puede estar activo a la vez (constraint parcial unico en PostgreSQL).

**Decisiones tecnicas:**
- 6 migraciones independientes para facilitar rollback granular
- Constraint parcial `WHERE activo = true` en `ped_planes` para garantizar un solo plan activo (PostgreSQL nativo)
- Cada tabla incluye columna `embedding vector(1536)` para busqueda semantica futura
- Se usa `string` para claves/numeros (no integer) por flexibilidad en numeracion compuesta ("1.2.3")

---

## Pre-requisitos

- S0-T2 completado (extension pgvector)

---

## Detalles Tecnicos

**Tablas:**
- `ped_planes` (id, nombre, nivel_gobierno, periodo_inicio, periodo_fin, activo)
- `ped_ejes` (id, ped_plan_id, numero, nombre, descripcion, embedding)
- `ped_temas` (id, ped_eje_id, numero, nombre, descripcion, embedding)
- `ped_objetivos_estrategicos` (id, ped_tema_id, clave, descripcion, embedding)
- `ped_estrategias` (id, ped_objetivo_estrategico_id, clave, descripcion, embedding)
- `ped_lineas_accion` (id, ped_estrategia_id, clave, descripcion, embedding)

**Relaciones Eloquent (cascada HasMany):**
- Plan → Ejes → Temas → Objetivos → Estrategias → Lineas de Accion
- Cada modelo con `belongsTo` inverso

---

## Criterios de aceptacion

- [ ] 6 migraciones con `up()` y `down()` completos — eliminacion en orden inverso de dependencia
- [ ] Indice parcial unico en `ped_planes`: `CREATE UNIQUE INDEX ... ON ped_planes (activo) WHERE activo = true` — solo un plan activo
- [ ] 6 modelos con `$fillable`, `casts()`, relaciones `hasMany`/`belongsTo` completas
- [ ] Columnas `embedding vector(1536)` en tablas de ped_ejes a ped_lineas_accion
- [ ] FK con `cascadeOnDelete()` en cada nivel hijo
- [ ] Seeder `PedSeeder` con datos ficticios (1 plan, 3 ejes, 2 temas por eje, objetivos, estrategias, lineas)
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Test: intentar activar 2 planes simultaneamente lanza `UniqueConstraintViolationException`
- [ ] Tinker: `PedPlan::where('activo', true)->first()->ejes->count()` retorna 3
- [ ] Archivo `docs/schema/ped.md` documenta las 6 tablas y relaciones

---

## Notas

- El constraint parcial unico se implementa con `DB::statement()` raw porque el Blueprint de Laravel no lo soporta nativamente
- Las claves/numeros usan `string` porque la numeracion compuesta ("1.2.3") no cabe en integer
- El seeder de datos ficticios sera reemplazado por datos reales via el importador Markdown (S2-T9)