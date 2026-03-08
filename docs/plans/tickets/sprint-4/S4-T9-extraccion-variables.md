# S4-T9: Extracción de Variables de Fórmulas — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T9
**Tipo:** feat
**Rama:** `feat/S4-T9-extraccion-variables`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T3 (IndicadorVariable model), S4-T4 (MirEditor), S3-T8 (LlmService)

**Goal:** Al capturar la fórmula de un indicador, la IA extrae las variables y crea registros en `indicador_variables` con nombre y símbolo. Fallback manual si la IA falla.

**Architecture:** Un prompt Blade recibe la fórmula y pide extraer variables con símbolo asignado. El resultado se parsea y crea registros en `indicador_variables`. El usuario puede editar nombres, símbolos y agregar/eliminar variables manualmente.

**Tech Stack:** Laravel 12, LlmService, Livewire 3, Blade Prompts

---

## Ejemplo

- Fórmula: `(Alumnos inscritos / Egresados secundaria) x 100`
- Variables extraídas:
  - A = "Alumnos inscritos"
  - B = "Egresados secundaria"

---

## Pre-requisitos

- S4-T3 completado (tabla `indicador_variables`)
- S4-T4 completado (MirEditor con ficha de indicador)
- S3-T8 completado (LlmService::suggest())

---

## Pasos

### Task 1: Crear prompt para extracción

**Files:**
- Create: `resources/views/prompts/mir/extraer-variables.blade.php`

**Step 1: Crear prompt**

```blade
Eres un experto en indicadores de programas presupuestarios.

Dada la siguiente fórmula de un indicador, identifica las variables y asígna un símbolo a cada una.

FÓRMULA: "{{ $formula }}"

Responde en formato JSON como array:
[
    {"simbolo": "A", "nombre": "...", "descripcion": "..."},
    {"simbolo": "B", "nombre": "...", "descripcion": "..."}
]

REGLAS:
1. Usa letras mayúsculas como símbolos (A, B, C...)
2. El nombre debe ser conciso pero descriptivo
3. Ignora constantes numéricas (como "x 100")
4. Responde SOLO con el JSON, sin explicaciones
```

**Step 2: Commit**

```bash
git add resources/views/prompts/mir/extraer-variables.blade.php
git commit -m "feat(S4-T9): add variable extraction prompt"
```

---

### Task 2: Implementar extracción en componente

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php` (o componente hijo dedicado)

**Step 1: Agregar método `extraerVariables(int $indicadorId)`**

- Cargar indicador y su fórmula
- Renderizar prompt
- Llamar a `LlmService::suggest()`
- Parsear JSON resultado
- Crear registros en `indicador_variables` (limpiar existentes si re-extrae)

**Step 2: Agregar CRUD manual de variables**

- Método `agregarVariable(int $indicadorId)`
- Método `editarVariable(int $variableId)`
- Método `eliminarVariable(int $variableId)`
- Selector de unidad de medida del catálogo CONAC

**Step 3: Agregar UI**

- Botón "Extraer variables" junto al campo de fórmula
- Tabla de variables con columnas: Símbolo, Nombre, Descripción, Comportamiento, Unidad
- Botones de editar/eliminar por variable
- Botón de agregar variable manual

**Step 4: Commit**

```bash
git add app/Livewire/Mml/ resources/views/livewire/mml/
git commit -m "feat(S4-T9): implement variable extraction with IA and manual fallback"
```

---

### Task 3: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/ExtraccionVariablesTest.php`

**Tests:**
1. Extraer variables crea registros en tabla
2. Variables tienen símbolo y nombre
3. Fallback manual: agregar variable sin IA
4. Editar variable existente
5. Eliminar variable
6. Re-extracción limpia variables anteriores
7. Selector de unidad de medida funciona

**Step 1: Escribir y ejecutar tests**

```bash
sail artisan test --filter=ExtraccionVariables
sail artisan test
```

**Step 2: Commit**

```bash
git add tests/
git commit -m "feat(S4-T9): add variable extraction tests"
```

---

## Criterios de Aceptación

- [ ] Botón "Extraer variables" al escribir fórmula
- [ ] IA identifica variables y asigna símbolos (A, B, C...)
- [ ] Registros creados en `indicador_variables`
- [ ] Fallback manual si IA falla
- [ ] Usuario puede editar nombres, símbolos y comportamiento
- [ ] Selector de unidad de medida del catálogo CONAC
- [ ] Tests pasan
