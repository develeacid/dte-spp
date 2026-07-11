# Análisis de Involucrados (M02 req 5) — Diseño

> Diseño aprobado 2026-07-11. Cierra **M02 req 5** del Informe de Brechas: no hay
> módulo/modelo/etapa para mapear beneficiarios/ejecutores/aliados/opositores.

## Objetivo

Capturar el Análisis de Involucrados del temario (categorías de actores + interés/rol
+ riesgo asociado) como sección de la Etapa 1, insumo del diseño y de los Supuestos.

## Categorías (temario `modulo_02.md §2.3`)

Beneficiarios directos, beneficiarios indirectos, ejecutores, aliados, neutrales,
opositores. Los **riesgos** identificados se convierten en Supuestos de la MIR (esa
trazabilidad es la brecha M02 req 6/29, fuera de alcance aquí).

## Decisiones (aprobadas)

- **Ubicación**: CRUD embebido como **tercera sección de Etapa 1**
  (`DefinicionProblema`), junto al Problema y la Ficha. Sin renumerar el wizard.
- **Campos por involucrado**: `categoria` (enum), `nombre`, `interes_o_rol`,
  `riesgo_asociado` (opcional).

## Modelo de datos

- Tabla `involucrados`: `programa_presupuestario_id` (FK, cascade), `categoria`
  (string), `nombre` (string), `interes_o_rol` (text nullable), `riesgo_asociado`
  (text nullable), `orden` (int), timestamps.
- Enum `App\Enums\InvolucradoCategoria`: `BENEFICIARIO_DIRECTO,
  BENEFICIARIO_INDIRECTO, EJECUTOR, ALIADO, NEUTRAL, OPOSITOR` con `label()` y
  `values()`.
- Modelo `App\Models\Mml\Involucrado` (cast `categoria` → enum, fillable).
- Relación `ProgramaPresupuestario::involucrados(): HasMany` (ordenada por `orden`).

## Componente (Etapa 1, `DefinicionProblema`)

Sigue el patrón del CRUD de supuestos en `MirEditor` (persistencia inmediata por
fila, con scoping defensivo):

- Helper `involucradoDelPrograma(int $id): ?Involucrado` (scoped al programa montado).
- `agregarInvolucrado()`: crea fila en blanco (`categoria` default
  `beneficiario_directo`, `orden` = max+1).
- `guardarInvolucrado(int $id, array $data)`: valida y actualiza; no-op si no es del
  programa. Reglas: `nombre required|string|max:255`, `categoria` in enum,
  `interes_o_rol nullable|string|max:1000`, `riesgo_asociado nullable|string|max:1000`.
- `eliminarInvolucrado(int $id)`: borra (scoped).
- `render()` carga `$this->programa->involucrados`.

## UI (blade Etapa 1)

Sección `x-forms.section` **"Análisis de Involucrados"** con:
- Lista de filas (`x-data` por fila): select de categoría, input nombre, textarea
  interés/rol, textarea riesgo asociado, botón eliminar. `@change` →
  `guardarInvolucrado`.
- Botón "+ Involucrado" → `agregarInvolucrado`.
- Nota didáctica: los riesgos identificados alimentan los Supuestos de la MIR.

## Testing (TDD)

`tests/Feature/Mml/DefinicionProblemaInvolucradosTest`:
- Modelo/migración: tabla existe; relación hasMany; `categoria` castea a enum.
- `agregarInvolucrado` crea una fila del programa.
- `guardarInvolucrado` persiste; rechaza categoría inválida.
- Scoping: `guardarInvolucrado`/`eliminarInvolucrado` sobre involucrado de otro
  programa = no-op.
- `eliminarInvolucrado` borra.

## Fuera de alcance

- Enlace automático involucrado → `MirSupuesto` (M02 req 6/29, siguiente).
- Matriz poder/interés, priorización. YAGNI.

## Post-deploy

`sail artisan migrate` (1 migración privada). Sin BD pública.
