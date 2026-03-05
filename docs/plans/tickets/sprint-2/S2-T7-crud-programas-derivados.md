# Plan: S2-T7 — CRUD de Programas Derivados con interfaz Livewire

**Ticket:** S2-T7
**Tipo:** feat
**Rama:** `feat/S2-T7-crud-programas-derivados`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T4, S2-T6, S1-T3

---

## Contexto

Interfaz para gestionar los programas derivados (sectoriales, especiales, institucionales, regionales) y sus objetivos. Reutiliza patrones de UI establecidos en S2-T6. Los programas derivados se vinculan al PED activo y se filtran por tipo.

---

## Pre-requisitos

- S2-T4 completado (modelos y migraciones de Programas Derivados)
- S2-T6 completado (patrones de UI Livewire establecidos)
- S1-T3 completado (permiso `gestionar_catalogos`)

---

## Detalles Tecnicos

**Componentes Livewire:**
- `ProgramasDerivadosManager` — Listado filtrable por tipo con CRUD inline
- Formulario de creacion/edicion de programa derivado con selector de tipo (Enum)
- Sub-seccion para gestionar objetivos de cada programa

---

## Criterios de aceptacion

- [ ] Ruta protegida con middleware `permission:gestionar_catalogos`
- [ ] Componente Livewire `App\Livewire\ProgramasDerivadosManager` con listado y CRUD
- [ ] Filtro por tipo de programa (selector: sectorial, especial, institucional, regional, todos)
- [ ] CRUD completo de programas derivados: crear, editar, eliminar
- [ ] CRUD de objetivos por programa derivado (formularios inline anidados)
- [ ] Vinculacion automatica al PED activo (`ped_plan_id` del plan con `activo = true`)
- [ ] Validacion: nombre requerido, tipo requerido (validado contra Enum PHP)
- [ ] Test Livewire: crear programa derivado y verificar que aparece en listado filtrado
- [ ] Test: usuario sin permiso `gestionar_catalogos` recibe 403

---

## Notas

- Si no existe un PED activo, la interfaz muestra mensaje informativo y bloquea la creacion
- Al eliminar un programa derivado con objetivos, se confirma eliminacion en cascada