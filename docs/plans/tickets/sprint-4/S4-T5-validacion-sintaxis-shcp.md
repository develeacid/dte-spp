# S4-T5: Validación Sintáctica SHCP del Resumen Narrativo — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T5
**Tipo:** feat
**Rama:** `feat/S4-T5-validacion-sintaxis-shcp`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T4 (MirEditor), S3-T8 (LlmService)

**Goal:** La IA audita que el Resumen Narrativo de cada nivel MIR cumpla con la sintaxis obligatoria de la SHCP. Genera feedback con explicación y sugerencia de reescritura.

**Architecture:** Un prompt Blade por nivel que describe la fórmula sintáctica esperada. Un método `validarSintaxis()` en el componente MirEditor (o servicio dedicado) llama a `LlmService::validate()` y persiste el resultado con timestamp. El resultado es advertencia, no bloqueo.

**Tech Stack:** Laravel 12, LlmService, Blade Prompts, Livewire 3

---

## Reglas Sintácticas por Nivel

| Nivel | Fórmula SHCP |
|---|---|
| Fin | "Contribuir a [Impacto] mediante [Solución]" |
| Propósito | "[Población] + [Verbo presente/participio] + [Condición]" |
| Componente | "[Bien/Servicio] + [Participio -ado/-ido]" |
| Actividad | "[Sustantivo deverbal] + [Complemento]" |

---

## Pre-requisitos

- S4-T4 completado (MirEditor con niveles editables)
- S3-T8 completado (LlmService::validate())

---

## Pasos

### Task 1: Crear prompts Blade por nivel

**Files:**
- Create: `resources/views/prompts/mir/validar-sintaxis-fin.blade.php`
- Create: `resources/views/prompts/mir/validar-sintaxis-proposito.blade.php`
- Create: `resources/views/prompts/mir/validar-sintaxis-componente.blade.php`
- Create: `resources/views/prompts/mir/validar-sintaxis-actividad.blade.php`

**Step 1: Crear los 4 prompts**

Cada prompt describe la fórmula esperada, recibe `$texto` (resumen narrativo), y pide al LLM evaluar si cumple, explicar fallos, y sugerir reescritura.

**Step 2: Commit**

```bash
git add resources/views/prompts/mir/
git commit -m "feat(S4-T5): add SHCP syntax validation prompts per MIR level"
```

---

### Task 2: Crear migración para persistir resultado

**Files:**
- Create: migración `add_validacion_sintaxis_to_mir_niveles`

**Step 1: Agregar campos a mir_niveles**

```php
$table->boolean('sintaxis_valida')->nullable();
$table->text('sintaxis_observacion')->nullable();
$table->text('sintaxis_sugerencia')->nullable();
$table->timestamp('sintaxis_validada_at')->nullable();
```

**Step 2: Commit**

```bash
git add database/migrations/
git commit -m "feat(S4-T5): add syntax validation fields to mir_niveles"
```

---

### Task 3: Implementar validación en el componente

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php` (o crear servicio `MirValidacionService`)

**Step 1: Agregar método `validarSintaxis(int $nivelId)`**

- Cargar el nivel y su tipo
- Renderizar el prompt correspondiente
- Llamar a `LlmService::validate()`
- Persistir resultado en campos `sintaxis_*`
- Mostrar resultado en la UI (advertencia, no bloqueo)

**Step 2: Agregar UI de resultado**

En la vista, junto al textarea de resumen narrativo, mostrar:
- Botón "Validar sintaxis"
- Badge verde/amarillo según resultado
- Observación y sugerencia expandible
- Botón "Aceptar sugerencia"

**Step 3: Commit**

```bash
git add app/Livewire/Mml/MirEditor.php resources/views/livewire/mml/mir-editor.blade.php
git commit -m "feat(S4-T5): implement SHCP syntax validation with IA feedback"
```

---

### Task 4: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/ValidacionSintaxisTest.php`

**Tests:**
1. Validar sintaxis de Fin retorna resultado
2. Resultado se persiste con timestamp
3. Sugerencia de reescritura disponible
4. Validación no bloquea guardado
5. Resultado visual (cumple/no cumple)

**Step 1: Escribir tests**
**Step 2: Ejecutar tests**

```bash
sail artisan test --filter=ValidacionSintaxis
sail artisan test
```

**Step 3: Commit**

```bash
git add tests/Feature/Mml/ValidacionSintaxisTest.php
git commit -m "feat(S4-T5): add syntax validation tests"
```

---

## Criterios de Aceptación

- [ ] Al guardar/validar un nivel, la IA analiza la sintaxis
- [ ] Resultado: cumple / no cumple con explicación específica
- [ ] Sugerencia de reescritura que respeta la fórmula
- [ ] El usuario decide si acepta la sugerencia
- [ ] No bloquea el guardado (es advertencia, no error)
- [ ] El resultado de la validación se persiste con timestamp
- [ ] Tests pasan
