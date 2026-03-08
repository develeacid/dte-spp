# S4-T12: Asignación de UR Coadyuvante por Componente/Actividad — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S4-T12
**Tipo:** feat
**Rama:** `feat/S4-T12-asignacion-ur-coadyuvante`
**Sprint:** 4 — MIR y Validaciones
**Depende de:** S4-T4 (MirEditor), S1-T7 (Roles y Permisos, middleware Multi-UR)

**Goal:** En la interfaz MIR (filas de Componente y Actividad), el Planeador puede asignar una UR Coadyuvante responsable. El sistema registra la relación en `programa_team` y en `mir_niveles.team_id`, activando permisos del middleware Multi-UR.

**Architecture:** Un selector de teams (UR) integrado en el MirEditor para filas de Componente y Actividad. Al asignar, se actualiza `mir_niveles.team_id` y se crea/actualiza `programa_team` con `rol = coadyuvante`. Al quitar, se verifica si la UR tiene otros componentes antes de eliminar de `programa_team`.

**Tech Stack:** Laravel 12, Livewire 3, Eloquent Relations

---

## Contexto

En la estructura de gobierno mexicano, un programa presupuestario puede involucrar múltiples Unidades Responsables (UR). La UR Coordinadora es dueña del programa. Las UR Coadyuvantes son responsables de componentes o actividades específicas. Esta asignación habilita permisos granulares vía el middleware Multi-UR.

---

## Pre-requisitos

- S4-T4 completado (MirEditor con tabla de niveles)
- `mir_niveles.team_id` FK ya existe (de S4-T2)
- `programa_team` pivot table ya existe (de S1-T7 o equivalente)
- Middleware Multi-UR configurado

---

## Pasos

### Task 1: Verificar/crear pivot `programa_team`

**Files:**
- Verify: migración de `programa_team` existe con campo `rol`

**Step 1: Verificar esquema**

La tabla `programa_team` debe tener:
```
- id
- programa_presupuestario_id (FK)
- team_id (FK)
- rol (string: 'coordinadora', 'coadyuvante')
- timestamps
- unique: [programa_presupuestario_id, team_id]
```

Si no existe el campo `rol`, crear migración para agregarlo.

**Step 2: Commit si hay cambios**

---

### Task 2: Implementar asignación en MirEditor

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php`
- Modify: vista del MirEditor

**Step 1: Agregar métodos**

```php
public function asignarUrCoadyuvante(int $nivelId, ?int $teamId): void
{
    $nivel = MirNivel::findOrFail($nivelId);

    // Verificar que es Componente o Actividad
    if (!in_array($nivel->tipo_nivel, [TipoNivelMir::COMPONENTE, TipoNivelMir::ACTIVIDAD])) {
        return;
    }

    $oldTeamId = $nivel->team_id;
    $nivel->update(['team_id' => $teamId]);

    if ($teamId) {
        // Crear/actualizar programa_team
        $this->programa->equipos()->syncWithoutDetaching([
            $teamId => ['rol' => 'coadyuvante'],
        ]);
    }

    // Si se quitó la UR anterior, verificar si tiene otros niveles
    if ($oldTeamId && $oldTeamId !== $teamId) {
        $otrosNiveles = MirNivel::where('programa_presupuestario_id', $this->programa->id)
            ->where('team_id', $oldTeamId)
            ->exists();

        if (!$otrosNiveles) {
            $this->programa->equipos()->detach($oldTeamId);
        }
    }
}
```

**Step 2: Agregar UI**

- Selector de teams (dropdown) visible solo en filas de Componente y Actividad
- Campo opcional — si vacío, responsabilidad recae en UR Coordinadora
- Etiqueta visible junto al componente mostrando la UR asignada
- Solo visible para usuarios con permiso `editar_mir`

**Step 3: Commit**

```bash
git add app/Livewire/Mml/ resources/views/livewire/mml/
git commit -m "feat(S4-T12): implement UR Coadyuvante assignment in MIR editor"
```

---

### Task 3: Escribir y pasar tests

**Files:**
- Create: `tests/Feature/Mml/UrCoadyuvanteTest.php`

**Tests:**
1. Asignar UR a componente actualiza `mir_niveles.team_id`
2. Asignar UR crea registro en `programa_team` con rol coadyuvante
3. Quitar UR elimina de `programa_team` si no tiene otros niveles
4. Quitar UR mantiene `programa_team` si tiene otros niveles
5. No se puede asignar UR a nivel Fin o Propósito
6. Solo usuario con permiso `editar_mir` puede asignar
7. Etiqueta de UR visible en componente

**Step 1: Escribir y ejecutar tests**

```bash
sail artisan test --filter=UrCoadyuvante
sail artisan test
```

**Step 2: Commit**

```bash
git add tests/
git commit -m "feat(S4-T12): add UR Coadyuvante assignment tests"
```

---

## Criterios de Aceptación

- [ ] Selector de UR visible en filas de Componente y Actividad
- [ ] Campo opcional; sin asignar = UR Coordinadora responsable
- [ ] Al asignar: actualiza `mir_niveles.team_id` y `programa_team` (rol coadyuvante)
- [ ] Al quitar: limpia `team_id` y verifica si eliminar de `programa_team`
- [ ] Solo Planeador con permiso `editar_mir` puede asignar
- [ ] UR Coadyuvante aparece como etiqueta visible junto al Componente
- [ ] Tests pasan
