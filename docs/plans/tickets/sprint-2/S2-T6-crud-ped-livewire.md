# Plan: S2-T6 — CRUD de PED con interfaz Livewire

**Ticket:** S2-T6
**Tipo:** feat
**Rama:** `feat/S2-T6-crud-ped-livewire`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T3, S1-T3

---

## Contexto

Interfaz de administracion para que el planeador capture y edite toda la jerarquia del PED (6 niveles). Se implementa como componente Livewire con vista de arbol colapsable. Es la primera interfaz CRUD del sistema y establece patrones de UI reutilizables para S2-T7 y S2-T8.

**Decisiones tecnicas:**
- Componente Livewire con Alpine.js para interacciones de arbol (collapse/expand)
- Formularios inline (no modales) para edicion rapida
- Proteccion via middleware `permission:gestionar_catalogos` (definido en S1-T3)
- Confirmacion antes de eliminar nodos padre con hijos dependientes
- Validacion de campos requeridos con Form Request de Laravel

---

## Pre-requisitos

- S2-T3 completado (modelos y migraciones PED)
- S1-T3 completado (permiso `gestionar_catalogos` registrado)

---

## Detalles Tecnicos

**Componentes Livewire:**
- `PedManager` — Componente principal con vista de arbol colapsable
- Formularios inline para crear/editar nodos en cada nivel
- Confirmacion de eliminacion con advertencia de hijos dependientes

---

## Criterios de aceptacion

- [ ] Ruta protegida con middleware `permission:gestionar_catalogos`
- [ ] Componente Livewire `App\Livewire\PedManager` renderiza arbol colapsable con todos los niveles
- [ ] CRUD completo en cada nivel: crear, editar, eliminar con formularios inline
- [ ] Al eliminar un nodo padre, modal de confirmacion muestra conteo de hijos dependientes
- [ ] Validacion: `nombre`/`descripcion` requeridos, longitud maxima 500 caracteres
- [ ] Al crear/editar, se actualiza el arbol sin recarga de pagina completa (Livewire reactivo)
- [ ] Responsive (Tailwind) — funcional en pantallas >= 768px
- [ ] Test Livewire: usuario sin permiso `gestionar_catalogos` recibe 403
- [ ] Test Livewire: crear un eje y verificar que aparece en el arbol

---

## Notas

- El arbol usa Alpine.js `x-show` para collapse/expand, no recarga Livewire por cada toggle (performance)
- Patron reutilizable: este componente establece la base para S2-T7 (CRUD Programas Derivados)
- Los embeddings de nodos editados se regeneran via el pipeline S2-T10 (Observer)