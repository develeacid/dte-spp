# S4-T4: Interfaz de Captura MIR 4x4 — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T4
**Tipo:** feat
**Rama:** `feat/S4-T4-interfaz-mir-4x4`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T2 (MirNivel), S4-T3 (Indicadores), S4-T11 (Motor Poka-Yoke)

**Goal:** Componente Livewire que renderiza la Matriz de Indicadores para Resultados como tabla 4×4. Columna 1 (Resumen Narrativo) prellenada desde la Estructura Analítica del Programa (EAP/árboles). El usuario captura indicadores, medios de verificación y supuestos. La UI aplica las reglas condicionales del motor Poka-Yoke.

**Architecture:** Componente Livewire `MirEditor` como página completa. Renderiza una tabla con 4 filas (Fin, Propósito, Componente, Actividad) × 4 columnas (Resumen Narrativo, Indicadores, Medios de Verificación, Supuestos). Los Componentes y Actividades son dinámicos (agregar/quitar). Guardado por bloques con `wire:model.defer`. Integra `IndicadorReglasService` para restricciones dinámicas.

**Tech Stack:** Laravel 12, Livewire 3, Alpine.js, Tailwind CSS, Blade Components

---

## Contexto

La MIR 4×4 es el formato estándar de SHCP/CONEVAL para programas presupuestarios federales y estatales. Tiene exactamente esta estructura:

| | Resumen Narrativo | Indicadores | Medios de Verificación | Supuestos |
|---|---|---|---|---|
| **Fin** | 1 (único) | 1+ indicadores | 1+ medios | 1 texto |
| **Propósito** | 1 (único) | 1+ indicadores | 1+ medios | 1 texto |
| **Componente** | N (múltiples) | 1+ por componente | 1+ por indicador | 1 por componente |
| **Actividad** | N (bajo componente) | 1+ por actividad | 1+ por indicador | 1 por actividad |

---

## Pre-requisitos

- S4-T2 completado (MirNivel con jerarquía)
- S4-T3 completado (Indicador, Variables, MediosVerificacion)
- S4-T11 completado (IndicadorReglasService, StoreIndicadorRequest)

---

## Pasos

### Task 1: Agregar ruta y permiso

**Files:**
- Modify: `routes/web/mml.php`
- Modify: `database/seeders/RolesAndPermissionsSeeder.php` (agregar `editar_mir` si no existe)

**Step 1: Agregar ruta**

```php
Route::get('/etapa/5/mir', \App\Livewire\Mml\MirEditor::class)
    ->name('mml.mir');
```

**Step 2: Verificar que el permiso `editar_mir` exista** (o agregarlo al seeder)

**Step 3: Commit**

```bash
git add routes/web/mml.php database/seeders/RolesAndPermissionsSeeder.php
git commit -m "feat(S4-T4): add MIR editor route and permission"
```

---

### Task 2: Crear servicio de prellenado desde EAP

**Files:**
- Create: `app/Services/Mml/MirPrellenadoService.php`

**Step 1: Implementar el servicio**

El servicio toma el árbol de objetivos y la alternativa seleccionada para pregenerar los niveles MIR:
- Objetivo Central → Propósito (resumen narrativo)
- Fines Directos → Fin (resumen narrativo)
- Medios de la alternativa seleccionada → Componentes (resumen narrativo)

```php
<?php

namespace App\Services\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNivelMir;
use App\Enums\TipoNodo;
use App\Models\Mml\Arbol;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;

class MirPrellenadoService
{
    public function prellenar(ProgramaPresupuestario $programa): void
    {
        // Verificar que no existan niveles MIR
        if ($programa->mirNiveles()->exists()) {
            return;
        }

        $arbolObj = $programa->arboles()
            ->where('tipo', TipoArbol::OBJETIVOS->value)
            ->first();

        if (!$arbolObj) {
            return;
        }

        $objetivoCentral = $arbolObj->nodos()
            ->where('tipo_nodo', TipoNodo::OBJETIVO_CENTRAL->value)
            ->first();

        // Fin — desde fines directos del árbol de objetivos
        $finDirecto = $arbolObj->nodos()
            ->where('tipo_nodo', TipoNodo::FIN_DIRECTO->value)
            ->orderBy('orden')
            ->first();

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => $finDirecto?->descripcion,
            'arbol_nodo_id' => $finDirecto?->id,
            'orden' => 1,
        ]);

        // Propósito — desde objetivo central
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => $objetivoCentral?->descripcion,
            'arbol_nodo_id' => $objetivoCentral?->id,
            'orden' => 1,
        ]);

        // Componentes — desde medios de la alternativa seleccionada
        $alternativa = $programa->alternativas()
            ->where('seleccionada', true)
            ->with('nodos')
            ->first();

        if ($alternativa) {
            $mediosDirectos = $alternativa->nodos
                ->filter(fn ($n) => $n->tipo_nodo->value === TipoNodo::MEDIO_DIRECTO->value);

            foreach ($mediosDirectos->values() as $i => $medio) {
                MirNivel::create([
                    'programa_presupuestario_id' => $programa->id,
                    'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
                    'resumen_narrativo' => $medio->descripcion,
                    'arbol_nodo_id' => $medio->id,
                    'orden' => $i + 1,
                ]);
            }
        }
    }
}
```

**Step 2: Commit**

```bash
git add app/Services/Mml/MirPrellenadoService.php
git commit -m "feat(S4-T4): add MirPrellenadoService for EAP-based prefill"
```

---

### Task 3: Crear componente Livewire MirEditor

**Files:**
- Create: `app/Livewire/Mml/MirEditor.php`
- Create: `resources/views/livewire/mml/mir-editor.blade.php`
- Create: `tests/Feature/Mml/MirEditorTest.php`

**Step 1: Escribir tests**

Tests a cubrir:
1. Componente se renderiza con permiso `editar_mir`
2. Prellenado genera niveles desde EAP
3. Editar resumen narrativo de un nivel
4. Agregar componente
5. Agregar actividad bajo componente
6. Eliminar actividad
7. Guardar supuestos
8. No prellenar si MIR ya tiene niveles

**Step 2: Ejecutar tests (RED)**

```bash
sail artisan test --filter=MirEditorTest
```

**Step 3: Crear componente Livewire**

El componente `MirEditor` debe:
- En `mount()`: llamar a `MirPrellenadoService::prellenar()` si no hay niveles
- Cargar todos los niveles con sus indicadores
- Métodos: `guardarNivel()`, `agregarComponente()`, `agregarActividad()`, `eliminarNivel()`, `guardarSupuestos()`
- Pasar reglas de `IndicadorReglasService` a la vista para cada nivel
- Usar `wire:model.defer` en textareas para mejor rendimiento

**Step 4: Crear vista Blade**

La vista renderiza una tabla responsive:
- Filas agrupadas: Fin (1 fila), Propósito (1 fila), Componentes (N filas expandibles), Actividades (N filas bajo componente)
- 4 columnas con formularios inline
- Integración con Alpine.js para dropdowns condicionales de indicadores
- Campo de UR Coadyuvante visible solo en Componente y Actividad (placeholder para S4-T12)

**Step 5: Ejecutar tests (GREEN)**

```bash
sail artisan test --filter=MirEditorTest
```

**Step 6: Suite completo**

```bash
sail artisan test
```

**Step 7: Commit**

```bash
git add app/Livewire/Mml/MirEditor.php resources/views/livewire/mml/mir-editor.blade.php tests/Feature/Mml/MirEditorTest.php
git commit -m "feat(S4-T4): implement MIR 4x4 editor with EAP prefill and Poka-Yoke rules"
```

---

## Criterios de Aceptación

- [ ] Matriz visual 4 filas × 4 columnas
- [ ] Col 1 prellenada y editable
- [ ] Col 2: formulario de indicador con restricciones de S4-T11
- [ ] Col 3: formulario de medios de verificación
- [ ] Col 4: textarea para supuestos
- [ ] Múltiples componentes e indicadores por nivel
- [ ] Campo de UR Coadyuvante en Componente y Actividad (ver S4-T12)
- [ ] Guardado por bloques con `wire:model.defer`
- [ ] Solo accesible con permiso `editar_mir`
- [ ] Tests pasan
