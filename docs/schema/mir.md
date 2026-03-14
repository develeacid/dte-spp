# Esquema: MIR (Matriz de Indicadores para Resultados)

## Tabla: `mir_niveles`

Almacena los 4 niveles jerárquicos de la MIR: Fin, Propósito, Componente, Actividad.

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| id | bigint PK | no | |
| programa_presupuestario_id | FK → programa_presupuestarios | no | Programa al que pertenece |
| tipo_nivel | varchar(20) | no | Enum: fin, proposito, componente, actividad |
| componente_id | FK → mir_niveles | sí | Solo para Actividades: referencia al Componente padre |
| resumen_narrativo | text | sí | Descripción del nivel |
| supuestos | text | sí | Supuestos del nivel |
| arbol_nodo_id | FK → arbol_nodos | sí | Trazabilidad al nodo de origen en el árbol |
| orden | smallint | no | Orden de visualización (default 0) |
| ped_objetivo_estrategico_id | FK → ped_objetivos_estrategicos | sí | Alineación con PED |
| programa_derivado_objetivo_id | FK → programas_derivados_objetivos | sí | Alineación con Programa Derivado |
| ped_linea_accion_id | FK → ped_lineas_accion | sí | Alineación con línea de acción |
| team_id | FK → teams | sí | UR Coadyuvante |
| sintaxis_valida | boolean | sí | Resultado de validación de sintaxis |
| sintaxis_observacion | text | sí | Observación de la validación de sintaxis |
| sintaxis_sugerencia | text | sí | Sugerencia de mejora de sintaxis |
| sintaxis_validada_at | timestamp | sí | Fecha/hora de la última validación de sintaxis |
| timestamps | | | created_at, updated_at |

### Índices
- `(programa_presupuestario_id, tipo_nivel)` — consultas por programa y nivel
- `componente_id` — búsqueda de actividades por componente

### Reglas de negocio
- Fin y Propósito: únicos por programa (1 de cada uno)
- Componente: múltiples por programa
- Actividad: múltiples por componente, requiere `componente_id`
- Cascade delete: eliminar programa elimina todos los niveles; eliminar componente elimina sus actividades
- Las FKs de alineación (`ped_objetivo_estrategico_id`, `programa_derivado_objetivo_id`, `ped_linea_accion_id`, `team_id`) usan `nullOnDelete`

## Tabla: `mir_versiones`

Snapshots completos de la MIR en un momento dado.

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| id | bigint PK | no | |
| programa_presupuestario_id | FK → programa_presupuestarios | no | Programa al que pertenece |
| etiqueta | varchar(100) | no | Nombre de la versión |
| snapshot | jsonb | no | Snapshot completo de la MIR |
| created_by | FK → users | sí | Usuario que creó la versión |
| timestamps | | | created_at, updated_at |

## Modelos Eloquent

- `App\Models\Mml\MirNivel` — relaciones:
  - `programa()` → BelongsTo ProgramaPresupuestario
  - `componente()` → BelongsTo MirNivel (componente padre)
  - `actividades()` → HasMany MirNivel (actividades hijas, ordenadas por `orden`)
  - `nodoOrigen()` → BelongsTo ArbolNodo
  - `team()` → BelongsTo Team (UR Coadyuvante)
  - `indicadores()` → HasMany Indicador (ordenados por `orden`)
  - `pedObjetivoEstrategico()` → BelongsTo PedObjetivoEstrategico
  - `programaDerivadoObjetivo()` → BelongsTo ProgramaDerivadoObjetivo (implícita vía FK `programa_derivado_objetivo_id`)
  - `pedLineaAccion()` → BelongsTo PedLineaAccion
- `App\Models\Mml\MirVersion` — relaciones:
  - `programa()` → BelongsTo ProgramaPresupuestario
  - `creador()` → BelongsTo User

## Enums

- `App\Enums\TipoNivelMir` — backed enum: fin, proposito, componente, actividad
  - `label()` — etiqueta legible ("Fin", "Propósito", "Componente", "Actividad")
  - `orden()` — orden numérico (1–4)
  - `colorClass()` — clases Tailwind para badge de color
  - `values()` — array de valores string

## Auditoría

`MirNivel` usa `Spatie\Activitylog\Traits\LogsActivity` para registrar cambios en campos clave, incluyendo `sintaxis_valida` y `sintaxis_observacion`.
