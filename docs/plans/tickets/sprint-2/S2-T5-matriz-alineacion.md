# Plan: S2-T5 — Tablas pivote para Matriz de Alineacion

**Ticket:** S2-T5
**Tipo:** feat
**Rama:** `feat/S2-T5-matriz-alineacion`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T1, S2-T2, S2-T3, S2-T4

---

## Contexto

La Matriz de Alineacion vincula los niveles de la cascada de planes entre si. Son 3 tablas pivote muchos-a-muchos que permiten trazar la cadena completa: Linea de Accion PED → Objetivo PED → PND → ODS. Estas relaciones son la base para la herencia automatica de alineacion en la MIR (Sprint 4).

**Decisiones tecnicas:**
- Constraints UNIQUE compuestos para evitar duplicidad en alineaciones
- Relaciones `belongsToMany` con `withTimestamps()` en los modelos existentes
- Tests que validan tanto la unicidad como la navegabilidad de la cadena completa via Eloquent

---

## Pre-requisitos

- S2-T1 completado (tablas ODS)
- S2-T2 completado (tablas PND)
- S2-T3 completado (tablas PED)
- S2-T4 completado (tablas Programas Derivados)

---

## Detalles Tecnicos

**Tablas pivote:**
- `alineacion_ped_pnd` (ped_objetivo_estrategico_id ↔ pnd_objetivo_id) — Unique compuesto
- `alineacion_pnd_ods` (pnd_objetivo_id ↔ ods_meta_id) — Unique compuesto
- `alineacion_linea_programa_derivado` (ped_linea_accion_id ↔ programa_derivado_objetivo_id) — Unique compuesto

**Relaciones `belongsToMany` a agregar en modelos existentes:**
- `PedObjetivoEstrategico` ↔ `PndObjetivo`
- `PndObjetivo` ↔ `OdsMeta`
- `PedLineaAccion` ↔ `ProgramaDerivadoObjetivo`

---

## Criterios de aceptacion

- [ ] 3 migraciones con indices unicos compuestos en la combinacion de FKs
- [ ] FK con `cascadeOnDelete()` en ambas columnas de cada pivote
- [ ] Relaciones `belongsToMany` con `withTimestamps()` configuradas en los 6 modelos involucrados
- [ ] Seeder `AlineacionSeeder` crea al menos 3 alineaciones de ejemplo entre niveles
- [ ] Test: intentar duplicar una alineacion lanza `UniqueConstraintViolationException`
- [ ] Test: desde una `PedLineaAccion`, se puede recorrer via Eloquent: `$linea->estrategia->objetivoEstrategico->pndObjetivos->first()->odsMetas` — cadena completa hasta ODS
- [ ] `sail artisan migrate:fresh --seed` sin errores
- [ ] Archivo `docs/schema/matriz-alineacion.md` documenta las 3 tablas pivote y ejemplos de cadena

---

## Notas

- Los nombres de tablas pivote usan prefijo `alineacion_` para agruparlas logicamente
- La navegacion de la cadena completa requiere eager loading (`with()`) para evitar N+1 en produccion
- La Matriz de Alineacion es central para S4-T8 (alineacion automatica MIR ↔ planes)