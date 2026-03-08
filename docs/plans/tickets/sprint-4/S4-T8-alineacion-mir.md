# S4-T8: Alineación Automática MIR ↔ Matriz de Alineación — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T8
**Tipo:** feat
**Rama:** `feat/S4-T8-alineacion-mir`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T4 (MirEditor), S2-T11 (Búsqueda Semántica), S2-T8 (Matriz de Alineación)

**Goal:** Al capturar la MIR, el sistema sugiere alineación con la cascada de planes usando búsqueda semántica. El Fin se alinea con Objetivos Estratégicos del PED, heredando PND y ODS. Los Componentes/Actividades se alinean con Líneas de Acción.

**Architecture:** Usa `SemanticSearchService::findSimilar()` para buscar objetivos/líneas más cercanos. El usuario selecciona manualmente. Las FKs de alineación (`ped_objetivo_estrategico_id`, `programa_derivado_objetivo_id`, `ped_linea_accion_id`) se guardan en `mir_niveles`.

**Tech Stack:** Laravel 12, Livewire 3, SemanticSearchService, pgvector

---

## Contexto

La alineación es un requisito normativo: cada programa presupuestario debe demostrar su contribución a los objetivos de desarrollo del estado (PED), del país (PND) y globales (ODS). La búsqueda semántica facilita encontrar las alineaciones correctas basándose en similitud textual.

---

## Flujo

1. Al capturar el **Fin**, buscar Objetivos Estratégicos del PED por similitud semántica
2. Al seleccionar uno, heredar automáticamente: PND → ODS
3. Al capturar **Componentes/Actividades**, sugerir Líneas de Acción
4. Guardar FKs de alineación en `mir_niveles`
5. Mostrar cadena completa: Línea de Acción → Estrategia → Obj. Estratégico → PND → ODS

---

## Pre-requisitos

- S4-T4 completado (MirEditor)
- S2-T11 completado (SemanticSearchService)
- S2-T8 completado (Matriz de Alineación con herencia)
- Embeddings generados para modelos de cascada

---

## Pasos

### Task 1: Crear componente de sugerencia de alineación

**Files:**
- Create: `app/Livewire/Mml/AlineacionSugerencia.php` (componente child)

**Step 1: Implementar componente**

Componente Livewire que:
- Recibe un texto (resumen narrativo) y el tipo de búsqueda (objetivo_estrategico / linea_accion)
- Llama a `SemanticSearchService::findSimilar()` al montar o al recibir evento
- Muestra lista de sugerencias con score de similitud
- Emite evento al seleccionar

**Step 2: Commit**

```bash
git add app/Livewire/Mml/AlineacionSugerencia.php
git commit -m "feat(S4-T8): add AlineacionSugerencia child component"
```

---

### Task 2: Integrar en MirEditor

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php`
- Modify: vista del MirEditor

**Step 1: Agregar métodos de alineación**

- `buscarAlineacion(int $nivelId)`: trigger búsqueda semántica
- `seleccionarAlineacion(int $nivelId, string $tipo, int $entidadId)`: guardar FK
- Al seleccionar Objetivo Estratégico, mostrar cadena heredada (PND, ODS)

**Step 2: Agregar UI**

- Botón "Buscar alineación" junto a cada nivel
- Panel lateral con sugerencias y scores
- Vista de cadena completa tras selección
- Indicador visual de niveles alineados vs no alineados

**Step 3: Commit**

```bash
git add app/Livewire/Mml/ resources/views/livewire/mml/
git commit -m "feat(S4-T8): integrate alignment suggestions in MIR editor"
```

---

### Task 3: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/AlineacionMirTest.php`

**Tests:**
1. Buscar alineación retorna sugerencias con score
2. Seleccionar alineación guarda FK en mir_niveles
3. Herencia de cadena se muestra tras selección
4. FKs correctamente guardadas
5. Vista de cadena completa

**Step 1: Escribir y ejecutar tests**

```bash
sail artisan test --filter=AlineacionMir
sail artisan test
```

**Step 2: Commit**

```bash
git add tests/
git commit -m "feat(S4-T8): add alignment tests"
```

---

## Criterios de Aceptación

- [ ] Lista de sugerencias de alineación con score de similitud
- [ ] Selección manual (no auto-asignación)
- [ ] Herencia de cadena visible tras selección
- [ ] FKs guardadas en `mir_niveles`
- [ ] Vista de cadena completa: Línea de Acción → ... → ODS
- [ ] Tests pasan
