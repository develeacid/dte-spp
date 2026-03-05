# Plan: S2-T2 — Migraciones y modelos para catalogos PND

**Ticket:** S2-T2
**Tipo:** feat
**Rama:** `feat/S2-T2-catalogos-pnd`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S0-T2

---

## Contexto

El Plan Nacional de Desarrollo (PND) es el segundo nivel de la cascada de planes. Estructura jerarquica: Ejes → Objetivos → Estrategias. Catalogo inmutable cargado desde Markdown. Se replica el patron establecido en S2-T1 (columnas embedding, seeders idempotentes).

---

## Pre-requisitos

- S0-T2 completado (extension pgvector)
- Patron de migraciones con `vector(1536)` validado en S2-T1

---

## Detalles Tecnicos

**Tablas:**
- `pnd_ejes`: id, numero, nombre, descripcion, embedding (vector 1536)
- `pnd_objetivos`: id, pnd_eje_id (FK), clave, descripcion, embedding
- `pnd_estrategias`: id, pnd_objetivo_id (FK), clave, descripcion, embedding

**Relaciones Eloquent:**
- `PndEje hasMany PndObjetivo`
- `PndObjetivo hasMany PndEstrategia`
- `PndObjetivo belongsTo PndEje`
- `PndEstrategia belongsTo PndObjetivo`

---

## Criterios de aceptacion

- [ ] 3 migraciones con `up()` y `down()` completos — eliminacion en orden inverso de dependencia
- [ ] Columnas `embedding` de tipo `vector(1536)` en las 3 tablas
- [ ] 3 modelos (`App\Models\PndEje`, `PndObjetivo`, `PndEstrategia`) con `$fillable`, `casts()` y relaciones
- [ ] Seeder `PndSeeder` carga datos del PND vigente desde `docs/data/pnd.md` con `updateOrCreate()`
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] `sail artisan migrate:rollback` revierte las 3 tablas sin errores
- [ ] Tinker verifica conteos y relaciones: `PndEje::first()->objetivos->count()` > 0
- [ ] Archivo `docs/schema/pnd.md` documenta estructura

---

## Notas

- Mismo patron de seeder idempotente que S2-T1
- El archivo fuente `docs/data/pnd.md` debe contener el PND vigente con estructura parseada por headings