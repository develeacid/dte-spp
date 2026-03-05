# Plan: S2-T8 — Interfaz de Matriz de Alineacion

**Ticket:** S2-T8
**Tipo:** feat
**Rama:** `feat/S2-T8-interfaz-matriz-alineacion`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T5, S2-T6, S1-T3

---

## Contexto

Interfaz para que el planeador configure la Matriz de Alineacion, creando vinculos muchos-a-muchos entre niveles de la cascada de planes. La interfaz debe mostrar la cadena resultante para que el usuario visualice la trazabilidad completa (PED → PND → ODS). Es la pieza clave para la alineacion automatica de MIR en Sprint 4.

**Decisiones tecnicas:**
- 3 secciones independientes para cada tipo de alineacion (PED↔PND, PND↔ODS, Linea↔Programa Derivado)
- Selectores dinamicos con busqueda (no drag-and-drop en v1 — complejidad innecesaria)
- Al vincular, la cadena completa se recalcula y muestra en tiempo real
- Eager loading obligatorio para evitar N+1 al renderizar cadenas

---

## Pre-requisitos

- S2-T5 completado (tablas pivote de alineacion)
- S2-T6 completado (PED disponible para seleccion)
- S1-T3 completado (permiso `gestionar_catalogos`)

---

## Criterios de aceptacion

- [ ] Ruta protegida con middleware `permission:gestionar_catalogos`
- [ ] Vista con 3 secciones: PED↔PND, PND↔ODS, Linea de Accion↔Programa Derivado
- [ ] Selectores dinamicos para agregar vinculos — selects con busqueda (Livewire)
- [ ] Boton de eliminar vinculo con confirmacion
- [ ] Al crear un vinculo PED↔PND, se muestra la cadena resultante completa hasta ODS (si existe vinculo PND↔ODS)
- [ ] Herencia automatica visible: al seleccionar una Linea de Accion, se muestran los ODS heredados via la cadena de relaciones
- [ ] Eager loading (`with()`) en queries para evitar N+1 — verificar con Debugbar o query log
- [ ] Test Livewire: crear vinculo PED↔PND y verificar que aparece en la lista
- [ ] Test: herencia automatica funciona — la cadena completa es navegable

---

## Notas

- En v1 se usan selectores con busqueda textual; drag-and-drop es mejora futura (Sprint 9+)
- La cadena de herencia se calcula con Eloquent lazy eager loading (`load()`) al momento de mostrar
- Esta interfaz es prerequisito para S4-T8 (alineacion automatica MIR → planes)