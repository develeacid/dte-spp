# Plan: S2-T4 — Migraciones y modelos para Programas Derivados

**Ticket:** S2-T4
**Tipo:** feat
**Rama:** `feat/S2-T4-programas-derivados`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T3

---

## Contexto

Los programas derivados (sectoriales, especiales, institucionales, regionales) emanan del PED y representan instrumentos de planificacion intermedios. Cada programa derivado tiene objetivos propios que se vinculan con las lineas de accion del PED via la Matriz de Alineacion (S2-T5). El tipo de programa se almacena como ENUM nativo de PostgreSQL.

---

## Pre-requisitos

- S2-T3 completado (tabla `ped_planes` existente para FK)

---

## Detalles Tecnicos

**Tablas:**
- `programas_derivados`: id, ped_plan_id (FK), tipo (ENUM PostgreSQL: sectorial, especial, institucional, regional), nombre, descripcion, timestamps
- `programas_derivados_objetivos`: id, programa_derivado_id (FK), clave, descripcion, embedding (vector 1536), timestamps

**Relaciones Eloquent:**
- `ProgramaDerivado belongsTo PedPlan`
- `ProgramaDerivado hasMany ProgramaDerivadoObjetivo`
- `ProgramaDerivadoObjetivo belongsTo ProgramaDerivado`

---

## Criterios de aceptacion

- [ ] ENUM nativo PostgreSQL `tipo_programa_derivado` con 4 valores — verificar con `\dT tipo_programa_derivado` en psql
- [ ] Backed Enum PHP `App\Enums\TipoProgramaDerivado` con casos: SECTORIAL, ESPECIAL, INSTITUCIONAL, REGIONAL
- [ ] Columna `embedding vector(1536)` en `programas_derivados_objetivos`
- [ ] FK `ped_plan_id` con `cascadeOnDelete()` — si se elimina el PED, se eliminan programas derivados
- [ ] 2 modelos con `$fillable`, `casts()` (tipo casteado a Enum PHP) y relaciones
- [ ] Seeder con al menos 2 programas derivados de tipos distintos y 3 objetivos
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] `sail artisan migrate:rollback` revierte sin errores (incluye DROP TYPE del ENUM)
- [ ] Archivo `docs/schema/programas-derivados.md` documenta estructura

---

## Notas

- El `down()` debe incluir `DB::statement('DROP TYPE IF EXISTS tipo_programa_derivado')` ya que PostgreSQL no elimina el ENUM automaticamente al eliminar la tabla
- El cast de Enum se hace a nivel PHP (Backed Enum) para consistencia con el patron establecido en S1-T2