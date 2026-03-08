# S4-T10: Snapshots y Versionado de MIR — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T10
**Tipo:** feat
**Rama:** `feat/S4-T10-snapshots-mir`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T2 (MirVersion model), S4-T4 (MirEditor)

**Goal:** Implementar el sistema de snapshots que permite crear borradores alternos de la MIR sin destruir el trabajo actual. Soporte para crear, listar, restaurar versiones y advertencias de impacto.

**Architecture:** El snapshot serializa toda la MIR (niveles + indicadores + variables + medios + cremaa) como JSONB en `mir_versiones`. Al restaurar, se reemplaza la MIR actual con confirmación. Se agrega lógica de advertencia si el usuario navega a etapas anteriores (árboles).

**Tech Stack:** Laravel 12, Livewire 3, PostgreSQL JSONB

---

## Contexto

Los usuarios necesitan experimentar con diferentes configuraciones de la MIR sin perder trabajo previo. Los snapshots son copias completas en un momento dado. A diferencia de un historial de cambios, el usuario decide explícitamente cuándo crear un snapshot.

---

## Pre-requisitos

- S4-T2 completado (tabla `mir_versiones` con campo `snapshot` JSONB)
- S4-T4 completado (MirEditor con todos los niveles e indicadores)

---

## Pasos

### Task 1: Crear servicio de snapshots

**Files:**
- Create: `app/Services/Mml/MirSnapshotService.php`

**Step 1: Implementar servicio**

```php
<?php

namespace App\Services\Mml;

use App\Models\Mml\MirNivel;
use App\Models\Mml\MirVersion;
use App\Models\ProgramaPresupuestario;

class MirSnapshotService
{
    public function crear(ProgramaPresupuestario $programa, string $etiqueta, int $userId): MirVersion
    {
        $snapshot = $this->serializarMir($programa);

        return MirVersion::create([
            'programa_presupuestario_id' => $programa->id,
            'etiqueta' => $etiqueta,
            'snapshot' => $snapshot,
            'created_by' => $userId,
        ]);
    }

    public function restaurar(MirVersion $version): void
    {
        $programa = $version->programa;

        // Eliminar MIR actual
        $programa->mirNiveles()->each(function ($nivel) {
            $nivel->indicadores()->each(function ($indicador) {
                $indicador->variables()->delete();
                $indicador->mediosVerificacion()->delete();
                $indicador->cremaaValidacion()?->delete();
            });
            $nivel->indicadores()->delete();
        });
        $programa->mirNiveles()->delete();

        // Restaurar desde snapshot
        $this->deserializarMir($programa, $version->snapshot);
    }

    private function serializarMir(ProgramaPresupuestario $programa): array
    {
        return $programa->mirNiveles()
            ->with(['indicadores.variables', 'indicadores.mediosVerificacion', 'indicadores.cremaaValidacion'])
            ->orderBy('tipo_nivel')
            ->orderBy('orden')
            ->get()
            ->map(fn ($nivel) => [
                'tipo_nivel' => $nivel->tipo_nivel->value ?? $nivel->tipo_nivel,
                'resumen_narrativo' => $nivel->resumen_narrativo,
                'supuestos' => $nivel->supuestos,
                'orden' => $nivel->orden,
                'arbol_nodo_id' => $nivel->arbol_nodo_id,
                'componente_orden' => $nivel->componente_id ? $nivel->componente->orden ?? null : null,
                'indicadores' => $nivel->indicadores->map(fn ($ind) => [
                    'nombre' => $ind->nombre,
                    'formula_texto' => $ind->formula_texto,
                    'tipo' => $ind->tipo?->value,
                    'dimension' => $ind->dimension?->value,
                    'frecuencia' => $ind->frecuencia?->value,
                    'sentido' => $ind->sentido?->value,
                    'linea_base' => $ind->linea_base,
                    'meta' => $ind->meta,
                    'orden' => $ind->orden,
                    'variables' => $ind->variables->toArray(),
                    'medios' => $ind->mediosVerificacion->toArray(),
                ])->toArray(),
            ])->toArray();
    }

    private function deserializarMir(ProgramaPresupuestario $programa, array $snapshot): void
    {
        // Recrear niveles e indicadores desde snapshot
        // Lógica de mapeo de componente_id para actividades
    }
}
```

**Step 2: Commit**

```bash
git add app/Services/Mml/MirSnapshotService.php
git commit -m "feat(S4-T10): add MirSnapshotService for create/restore"
```

---

### Task 2: Integrar en MirEditor

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php`
- Modify: vista del MirEditor

**Step 1: Agregar métodos**

- `crearSnapshot()`: pide etiqueta, llama al servicio
- `listarVersiones()`: carga versiones desde DB
- `restaurarVersion(int $versionId)`: con confirmación, restaura

**Step 2: Agregar UI**

- Botón "Crear snapshot" con input de etiqueta
- Panel/modal de versiones con lista y fechas
- Botón "Restaurar" con confirmación explícita
- Opción "Crear borrador alterno" vs "Sobreescribir actual"

**Step 3: Commit**

```bash
git add app/Livewire/Mml/ resources/views/livewire/mml/
git commit -m "feat(S4-T10): integrate snapshot UI in MIR editor"
```

---

### Task 3: Agregar advertencia de impacto

**Files:**
- Modify: Componentes de Etapa 2, 3 y 4 (ArbolProblemaBuilder, ArbolObjetivosBuilder, SeleccionAlternativas)

**Step 1: Agregar verificación**

Si el programa tiene MIR existente y el usuario edita árboles/alternativas, mostrar advertencia: "Modificar el árbol puede afectar la MIR. ¿Deseas crear un snapshot antes de continuar?"

**Step 2: Commit**

```bash
git add app/Livewire/Mml/
git commit -m "feat(S4-T10): add impact warning when editing trees with existing MIR"
```

---

### Task 4: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/MirSnapshotTest.php`

**Tests:**
1. Crear snapshot guarda estado completo en JSONB
2. Listar versiones con etiqueta y fecha
3. Restaurar versión reemplaza MIR actual
4. Snapshot incluye indicadores y variables
5. Opción crear borrador alterno

**Step 1: Escribir y ejecutar tests**

```bash
sail artisan test --filter=MirSnapshot
sail artisan test
```

**Step 2: Commit**

```bash
git add tests/
git commit -m "feat(S4-T10): add snapshot tests"
```

---

## Criterios de Aceptación

- [ ] Botón "Crear snapshot" guarda estado completo en `mir_versiones` (JSONB)
- [ ] Listado de versiones con etiqueta y fecha
- [ ] Restaurar versión reemplaza MIR actual (con confirmación)
- [ ] Al regresar a etapas anteriores, advertencia de impacto en MIR
- [ ] Opción "Crear borrador alterno" vs "Sobreescribir actual"
- [ ] Tests pasan
