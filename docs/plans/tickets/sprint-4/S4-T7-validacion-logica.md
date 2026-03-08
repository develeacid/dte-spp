# S4-T7: Validación de Lógica Vertical y Horizontal — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T7
**Tipo:** feat
**Rama:** `feat/S4-T7-validacion-logica`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T4 (MirEditor completo), S4-T3 (Indicadores), S3-T8 (LlmService)

**Goal:** Validación automática de la coherencia interna de la MIR. La lógica vertical verifica la cadena causal (Actividades→Componentes→Propósito→Fin). La lógica horizontal verifica que indicadores midan lo correcto y los medios sustenten los datos.

**Architecture:** Un servicio `MirLogicaValidacionService` ejecuta ambas validaciones. Usa `LlmService::validate()` para análisis semántico de coherencia. Los hallazgos se clasifican por severidad (error crítico, advertencia, sugerencia). Se muestra un reporte consolidado.

**Tech Stack:** Laravel 12, LlmService, Livewire 3, Blade Prompts

---

## Contexto

### Lógica Vertical (cadena causal)
- Actividades producen Componentes
- Componentes logran Propósito
- Propósito contribuye al Fin
- La IA analiza si la relación causal es coherente

### Lógica Horizontal (consistencia por fila)
- El indicador realmente mide el objetivo (Resumen Narrativo)
- El medio de verificación puede proporcionar los datos del indicador
- La frecuencia del medio coincide con la frecuencia del indicador

---

## Pre-requisitos

- S4-T4 completado (MIR con niveles, indicadores y medios)
- S3-T8 completado (LlmService)

---

## Pasos

### Task 1: Crear prompts para validación lógica

**Files:**
- Create: `resources/views/prompts/mir/validar-logica-vertical.blade.php`
- Create: `resources/views/prompts/mir/validar-logica-horizontal.blade.php`

**Step 1: Prompt de lógica vertical**

Recibe toda la MIR (Fin, Propósito, Componentes, Actividades con sus resúmenes narrativos). Pide evaluar coherencia causal nivel por nivel.

**Step 2: Prompt de lógica horizontal**

Recibe una fila de la MIR (resumen narrativo + indicadores + medios + frecuencias). Pide evaluar si indicadores miden correctamente y medios sustentan datos.

**Step 3: Commit**

```bash
git add resources/views/prompts/mir/
git commit -m "feat(S4-T7): add logic validation prompts (vertical and horizontal)"
```

---

### Task 2: Crear servicio de validación

**Files:**
- Create: `app/Services/Mml/MirLogicaValidacionService.php`

**Step 1: Implementar servicio**

```php
<?php

namespace App\Services\Mml;

use App\Contracts\LlmServiceInterface;
use App\Models\ProgramaPresupuestario;

class MirLogicaValidacionService
{
    public function __construct(
        private LlmServiceInterface $llm,
    ) {}

    public function validarCompleta(ProgramaPresupuestario $programa): array
    {
        $vertical = $this->validarVertical($programa);
        $horizontal = $this->validarHorizontal($programa);

        return [
            'vertical' => $vertical,
            'horizontal' => $horizontal,
            'hallazgos' => array_merge($vertical['hallazgos'] ?? [], $horizontal['hallazgos'] ?? []),
        ];
    }

    private function validarVertical(ProgramaPresupuestario $programa): array { /* ... */ }
    private function validarHorizontal(ProgramaPresupuestario $programa): array { /* ... */ }
}
```

Cada hallazgo es un array con: `nivel`, `tipo` (error_critico/advertencia/sugerencia), `mensaje`, `detalle`.

**Step 2: Commit**

```bash
git add app/Services/Mml/MirLogicaValidacionService.php
git commit -m "feat(S4-T7): add MirLogicaValidacionService"
```

---

### Task 3: Integrar en MirEditor

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php`
- Modify: vista del MirEditor

**Step 1: Agregar método `validarMirCompleta()`**

Llama al servicio y almacena hallazgos en propiedad pública para renderizar.

**Step 2: Agregar UI de reporte**

- Botón "Validar MIR completa"
- Panel de reporte con hallazgos coloreados por severidad
- Agrupados por nivel
- Prominent si hay errores críticos

**Step 3: Commit**

```bash
git add app/Livewire/Mml/ resources/views/livewire/mml/
git commit -m "feat(S4-T7): integrate logic validation in MIR editor"
```

---

### Task 4: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/ValidacionLogicaTest.php`

**Tests:**
1. Validar MIR genera reporte con hallazgos
2. Hallazgos clasificados por severidad
3. Lógica horizontal detecta inconsistencia indicador-medio
4. No bloquea guardado
5. Reporte se muestra prominentemente

**Step 1: Escribir y ejecutar tests**

```bash
sail artisan test --filter=ValidacionLogica
sail artisan test
```

**Step 2: Commit**

```bash
git add tests/
git commit -m "feat(S4-T7): add logic validation tests"
```

---

## Criterios de Aceptación

- [ ] Botón "Validar MIR completa"
- [ ] Reporte con hallazgos por nivel
- [ ] Cada hallazgo clasificado: error crítico / advertencia / sugerencia
- [ ] No bloquea guardado pero se muestra prominentemente
- [ ] Tests pasan
