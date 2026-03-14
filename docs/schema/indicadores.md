# Esquema: Indicadores y Ficha Técnica

## Tabla: `indicadores`

Ficha técnica de cada indicador vinculado a un nivel MIR.

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| id | bigint PK | no | |
| mir_nivel_id | FK → mir_niveles | no | Nivel MIR al que pertenece |
| nombre | varchar | no | Nombre del indicador |
| formula_texto | text | sí | Fórmula descriptiva (ej: "(A/B)*100") |
| tipo | varchar(20) | no | Enum: estrategico, gestion |
| dimension | varchar(20) | no | Enum: eficacia, eficiencia, calidad, economia |
| frecuencia | varchar(20) | no | Enum: mensual, trimestral, semestral, anual, bianual, sexenal |
| sentido | varchar(20) | sí | Enum: ascendente, descendente, regular |
| linea_base | decimal(12,4) | sí | Valor base de referencia |
| meta | decimal(12,4) | sí | Meta programada |
| rango_verde_min/max | decimal(8,2) | sí | Semaforización verde |
| rango_amarillo_min/max | decimal(8,2) | sí | Semaforización amarilla |
| rango_rojo_min/max | decimal(8,2) | sí | Semaforización roja |
| unidad_medida_id | FK → catalogo_unidades_medida | sí | Unidad de medida |
| orden | smallint | no | Orden (default 0) |
| activo_seguimiento | boolean | no | Indica si el indicador está activo para seguimiento (default true) |

## Tabla: `indicador_variables`

Variables que componen la fórmula del indicador.

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| id | bigint PK | no | |
| indicador_id | FK → indicadores | no | Indicador padre |
| simbolo | varchar(5) | no | Letra/símbolo (A, B, C...) |
| nombre | varchar | no | Nombre descriptivo |
| descripcion | text | sí | Descripción detallada |
| comportamiento | varchar(20) | sí | acumulable, continua, etc. |
| unidad_medida_id | FK → catalogo_unidades_medida | sí | Unidad de medida |
| orden | smallint | no | Orden (default 0) |

## Tabla: `catalogo_unidades_medida`

Catálogo CONAC de unidades de medida.

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| id | bigint PK | no | |
| clave | varchar(10) | no | Clave única (PCT, TASA, IDX, etc.) |
| nombre | varchar | no | Nombre completo |

## Tabla: `medios_verificacion`

Fuentes de datos que sustentan el indicador.

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| id | bigint PK | no | |
| indicador_id | FK → indicadores | no | Indicador padre |
| nombre | varchar | no | Nombre del medio |
| descripcion | text | sí | Descripción |
| fuente | varchar | sí | Fuente de información |
| frecuencia | varchar(20) | sí | Frecuencia de recolección |
| orden | smallint | no | Orden (default 0) |

## Tabla: `cremaa_validaciones`

Validación CREMAA (Claro, Relevante, Económico, Monitoreable, Adecuado, Aportante).

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| id | bigint PK | no | |
| indicador_id | FK → indicadores | no | Indicador evaluado |
| claro | boolean | no | ¿Es claro? (default false) |
| claro_observacion | text | sí | Observación |
| relevante | boolean | no | ¿Es relevante? (default false) |
| relevante_observacion | text | sí | Observación |
| economico | boolean | no | ¿Es económico? (default false) |
| economico_observacion | text | sí | Observación |
| monitoreable | boolean | no | ¿Es monitoreable? (default false) |
| monitoreable_observacion | text | sí | Observación |
| adecuado | boolean | no | ¿Es adecuado? (default false) |
| adecuado_observacion | text | sí | Observación |
| aportante | boolean | no | ¿Es aportante? (default false) |
| aportante_observacion | text | sí | Observación |

## Tabla pivote: `indicador_anexo_transversal`

Vinculación N:N entre indicadores y anexos transversales.

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| indicador_id | FK → indicadores | no | Cascade on delete |
| anexo_transversal_id | FK → anexos_transversales | no | Cascade on delete |
| timestamps | | | created_at, updated_at |

**Constraint UNIQUE:** `(indicador_id, anexo_transversal_id)`

## Modelos Eloquent

- `App\Models\Mml\Indicador` — relaciones:
  - `mirNivel()` → BelongsTo MirNivel
  - `variables()` → HasMany IndicadorVariable (ordenadas por `orden`)
  - `mediosVerificacion()` → HasMany MedioVerificacion (ordenados por `orden`)
  - `cremaaValidacion()` → HasOne CremaaValidacion
  - `unidadMedida()` → BelongsTo CatalogoUnidadMedida
  - `metasPeriodo()` → HasMany MetaPeriodo (ordenadas por `periodo`)
  - `avances()` → HasMany Avance (`App\Models\Tracking\Avance`)
  - `anexosTransversales()` → BelongsToMany AnexoTransversal (`App\Models\Evaluation\AnexoTransversal`) vía `indicador_anexo_transversal`
- `App\Models\Mml\IndicadorVariable` — relaciones: indicador, unidadMedida
- `App\Models\Mml\MedioVerificacion` — relaciones: indicador
- `App\Models\Mml\CremaaValidacion` — relaciones: indicador
- `App\Models\CatalogoUnidadMedida`

## Enums

- `App\Enums\TipoIndicador` — estrategico, gestion
- `App\Enums\DimensionIndicador` — eficacia, eficiencia, calidad, economia
- `App\Enums\FrecuenciaMedicion` — mensual, trimestral, semestral, anual, bianual, sexenal
- `App\Enums\SentidoIndicador` — ascendente, descendente, regular

## Auditoría

`Indicador` usa `Spatie\Activitylog\Traits\LogsActivity` para registrar cambios en campos clave, incluyendo `activo_seguimiento`.
