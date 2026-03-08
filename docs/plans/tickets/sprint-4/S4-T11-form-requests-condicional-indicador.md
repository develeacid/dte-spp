# S4-T11: Form Requests y UI Dinámica Condicional — Motor Poka-Yoke del Indicador

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T11
**Tipo:** feat
**Rama:** `feat/S4-T11-form-requests-condicional-indicador`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T2 (MirNivel), S4-T3 (Indicadores y Enums)

**Goal:** Implementar el motor de reglas condicionales que adapta el formulario de la ficha técnica del indicador según el nivel MIR (Fin, Propósito, Componente, Actividad). La validación se aplica en frontend (Livewire/Alpine.js) y backend (Laravel Form Requests).

**Architecture:** Un servicio `IndicadorReglasService` centraliza las reglas por nivel. Un `StoreIndicadorRequest` (Form Request) valida server-side. En el frontend, Livewire pasa las reglas al componente y Alpine.js filtra las opciones de los dropdowns dinámicamente.

**Tech Stack:** Laravel 12, Livewire 3, Alpine.js, Laravel Form Requests

---

## Contexto

Cada nivel de la MIR tiene restricciones específicas sobre qué tipo de indicador, dimensiones y frecuencias son válidos. Esto previene errores de captura (Poka-Yoke). Las reglas están normadas por SHCP/CONEVAL.

---

## Reglas por Nivel

| Campo | Fin | Propósito | Componente | Actividad |
|---|---|---|---|---|
| Tipo indicador | Estratégico (fijo) | Estratégico (fijo) | Estratégico/Gestión (editable) | Gestión (fijo) |
| Dimensiones | Eficacia | Eficacia, Eficiencia | Eficacia, Eficiencia, Calidad | Eficacia, Eficiencia, Economía |
| Frecuencias | Anual, Bianual, Sexenal | Semestral, Anual | Trimestral, Semestral | Mensual, Trimestral |

---

## Pre-requisitos

- S4-T2 completado (TipoNivelMir enum)
- S4-T3 completado (Enums de indicador: TipoIndicador, DimensionIndicador, FrecuenciaMedicion)

---

## Pasos

### Task 1: Crear servicio de reglas

**Files:**
- Create: `app/Services/Mml/IndicadorReglasService.php`

**Step 1: Implementar el servicio**

```php
<?php

namespace App\Services\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoIndicador;
use App\Enums\TipoNivelMir;

class IndicadorReglasService
{
    public static function tipoPermitido(TipoNivelMir $nivel): array
    {
        return match($nivel) {
            TipoNivelMir::FIN, TipoNivelMir::PROPOSITO => [TipoIndicador::ESTRATEGICO],
            TipoNivelMir::COMPONENTE => [TipoIndicador::ESTRATEGICO, TipoIndicador::GESTION],
            TipoNivelMir::ACTIVIDAD => [TipoIndicador::GESTION],
        };
    }

    public static function esTipoFijo(TipoNivelMir $nivel): bool
    {
        return $nivel !== TipoNivelMir::COMPONENTE;
    }

    public static function tipoDefault(TipoNivelMir $nivel): TipoIndicador
    {
        return match($nivel) {
            TipoNivelMir::FIN, TipoNivelMir::PROPOSITO => TipoIndicador::ESTRATEGICO,
            TipoNivelMir::COMPONENTE => TipoIndicador::ESTRATEGICO,
            TipoNivelMir::ACTIVIDAD => TipoIndicador::GESTION,
        };
    }

    public static function dimensionesPermitidas(TipoNivelMir $nivel): array
    {
        return match($nivel) {
            TipoNivelMir::FIN => [DimensionIndicador::EFICACIA],
            TipoNivelMir::PROPOSITO => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA],
            TipoNivelMir::COMPONENTE => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA, DimensionIndicador::CALIDAD],
            TipoNivelMir::ACTIVIDAD => [DimensionIndicador::EFICACIA, DimensionIndicador::EFICIENCIA, DimensionIndicador::ECONOMIA],
        };
    }

    public static function frecuenciasPermitidas(TipoNivelMir $nivel): array
    {
        return match($nivel) {
            TipoNivelMir::FIN => [FrecuenciaMedicion::ANUAL, FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::SEXENAL],
            TipoNivelMir::PROPOSITO => [FrecuenciaMedicion::SEMESTRAL, FrecuenciaMedicion::ANUAL],
            TipoNivelMir::COMPONENTE => [FrecuenciaMedicion::TRIMESTRAL, FrecuenciaMedicion::SEMESTRAL],
            TipoNivelMir::ACTIVIDAD => [FrecuenciaMedicion::MENSUAL, FrecuenciaMedicion::TRIMESTRAL],
        };
    }

    public static function reglasParaNivel(TipoNivelMir $nivel): array
    {
        return [
            'tipo_fijo' => self::esTipoFijo($nivel),
            'tipo_default' => self::tipoDefault($nivel)->value,
            'tipos' => array_map(fn ($t) => $t->value, self::tipoPermitido($nivel)),
            'dimensiones' => array_map(fn ($d) => $d->value, self::dimensionesPermitidas($nivel)),
            'frecuencias' => array_map(fn ($f) => $f->value, self::frecuenciasPermitidas($nivel)),
        ];
    }
}
```

**Step 2: Commit**

```bash
git add app/Services/Mml/IndicadorReglasService.php
git commit -m "feat(S4-T11): add IndicadorReglasService with level-based rules"
```

---

### Task 2: Crear Form Request

**Files:**
- Create: `app/Http/Requests/Mml/StoreIndicadorRequest.php`

**Step 1: Implementar el Form Request**

```php
<?php

namespace App\Http\Requests\Mml;

use App\Enums\TipoNivelMir;
use App\Services\Mml\IndicadorReglasService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nivel = TipoNivelMir::tryFrom($this->input('nivel'));

        if (!$nivel) {
            return ['nivel' => 'required|in:' . implode(',', TipoNivelMir::values())];
        }

        $reglas = IndicadorReglasService::reglasParaNivel($nivel);

        return [
            'nombre' => 'required|string|max:255',
            'tipo' => ['required', Rule::in($reglas['tipos'])],
            'dimension' => ['required', Rule::in($reglas['dimensiones'])],
            'frecuencia' => ['required', Rule::in($reglas['frecuencias'])],
            'nivel' => ['required', Rule::in(TipoNivelMir::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo de indicador no es válido para este nivel.',
            'dimension.in' => 'La dimensión seleccionada no es válida para este nivel.',
            'frecuencia.in' => 'La frecuencia seleccionada no es válida para este nivel.',
        ];
    }
}
```

**Step 2: Commit**

```bash
git add app/Http/Requests/Mml/StoreIndicadorRequest.php
git commit -m "feat(S4-T11): add StoreIndicadorRequest with conditional validation"
```

---

### Task 3: Escribir y pasar tests

**Files:**
- Create: `tests/Unit/Mml/IndicadorReglasServiceTest.php`
- Create: `tests/Feature/Mml/StoreIndicadorRequestTest.php`

**Tests unitarios (IndicadorReglasService):**
1. Fin → tipo fijo estratégico
2. Componente → tipo editable
3. Actividad → tipo fijo gestión
4. Dimensiones por nivel son correctas
5. Frecuencias por nivel son correctas

**Tests de Form Request:**
1. Guardar "economia" en nivel Fin retorna 422
2. Guardar "gestion" en nivel Fin retorna 422
3. Guardar "estrategico" en nivel Fin pasa
4. Guardar "mensual" en nivel Fin retorna 422
5. Componente acepta "estrategico" y "gestion"
6. Actividad solo acepta "gestion"

**Step 1: Escribir tests**

Los tests de Form Request se pueden implementar instanciando el request directamente con `Validator::make()` o a través de un endpoint temporal de prueba.

**Step 2: Ejecutar tests**

```bash
sail artisan test --filter=IndicadorReglas
sail artisan test --filter=StoreIndicadorRequest
```

**Step 3: Suite completo**

```bash
sail artisan test
```

**Step 4: Commit**

```bash
git add tests/
git commit -m "feat(S4-T11): add tests for indicator rules and form request validation"
```

---

## Criterios de Aceptación

- [ ] Livewire: campo Tipo se renderiza como label read-only en Fin/Propósito/Actividad, select en Componente
- [ ] Alpine.js: dropdown de Dimensión filtra opciones según nivel
- [ ] Alpine.js: dropdown de Frecuencia filtra opciones según nivel
- [ ] StoreIndicadorRequest valida tipo según nivel
- [ ] Form Request valida dimensión y frecuencia en set permitido para nivel
- [ ] Test: guardar "Economía" en nivel Fin retorna 422
- [ ] UI no muestra mensajes de error — opciones inválidas no aparecen
- [ ] Tests pasan
