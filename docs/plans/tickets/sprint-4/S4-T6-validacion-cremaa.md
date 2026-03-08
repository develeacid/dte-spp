# S4-T6: Validación CREMAA Desglosada — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T6
**Tipo:** feat
**Rama:** `feat/S4-T6-validacion-cremaa`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T3 (CremaaValidacion model), S4-T4 (MirEditor con indicadores), S3-T8 (LlmService)

**Goal:** Al crear o editar un indicador, la IA evalúa los 6 criterios CREMAA individualmente y guarda el resultado en `cremaa_validaciones`. Resultado visual con 6 letras coloreadas y sugerencias de mejora.

**Architecture:** Un prompt Blade que recibe el indicador completo y pide evaluación por cada letra CREMAA. El resultado se parsea y se guarda en `cremaa_validaciones`. La UI muestra 6 badges con estado y observación expandible.

**Tech Stack:** Laravel 12, LlmService, Livewire 3, Blade Prompts

---

## Contexto

CREMAA es el acrónimo de los 6 criterios que debe cumplir un buen indicador según CONEVAL:
- **C**laro: fácil de entender
- **R**elevante: refleja el objetivo
- **E**conómico: se puede medir sin costo excesivo
- **M**onitoreable: sujeto a verificación independiente
- **A**decuado: proporcional al objetivo medido
- **A**portante: provee información para tomar decisiones

---

## Pre-requisitos

- S4-T3 completado (tabla `cremaa_validaciones`)
- S4-T4 completado (MirEditor con ficha de indicador)
- S3-T8 completado (LlmService)

---

## Pasos

### Task 1: Crear prompt Blade para CREMAA

**Files:**
- Create: `resources/views/prompts/mir/validar-cremaa.blade.php`

**Step 1: Crear prompt**

El prompt recibe: nombre del indicador, fórmula, tipo, dimensión, frecuencia, resumen narrativo del nivel. Pide evaluar cada criterio CREMAA como cumple/no cumple con justificación. Formato JSON.

**Step 2: Commit**

```bash
git add resources/views/prompts/mir/validar-cremaa.blade.php
git commit -m "feat(S4-T6): add CREMAA validation prompt"
```

---

### Task 2: Implementar validación en componente

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php` (agregar método `validarCremaa(int $indicadorId)`)

**Step 1: Implementar método**

- Cargar indicador con su nivel
- Renderizar prompt
- Llamar a `LlmService::suggest()` (espera JSON)
- Parsear resultado y guardar en `cremaa_validaciones` (upsert)

**Step 2: Agregar UI**

- Botón "Validar CREMAA" en la ficha del indicador
- 6 letras como badges: verde (cumple) o rojo (no cumple)
- Tooltip o expandible con observación por letra
- Sugerencias de mejora por criterio

**Step 3: Commit**

```bash
git add app/Livewire/Mml/ resources/views/livewire/mml/
git commit -m "feat(S4-T6): implement CREMAA validation with IA evaluation"
```

---

### Task 3: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/ValidacionCremaaTest.php`

**Tests:**
1. Validar CREMAA crea registro en tabla
2. Resultado tiene 6 campos boolean
3. Observaciones se guardan
4. No bloquea guardado de indicador
5. Actualiza validación existente (upsert)

**Step 1: Escribir y ejecutar tests**

```bash
sail artisan test --filter=ValidacionCremaa
sail artisan test
```

**Step 2: Commit**

```bash
git add tests/Feature/Mml/ValidacionCremaaTest.php
git commit -m "feat(S4-T6): add CREMAA validation tests"
```

---

## Criterios de Aceptación

- [ ] Botón "Validar CREMAA" en la ficha del indicador
- [ ] Resultado visual: 6 letras en verde/rojo
- [ ] Cada letra tiene explicación específica del fallo
- [ ] Sugerencias de mejora por criterio
- [ ] Resultado se persiste en `cremaa_validaciones`
- [ ] No bloquea guardado
- [ ] Tests pasan
