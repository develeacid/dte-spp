# Analisis de MIR, Indicadores y Modelos Relacionados

> Generado: 2026-03-15

## Estructura Jerarquica de Datos

```
ProgramaPresupuestario
│  clave, nombre, team_id, ejercicio_fiscal, estado, created_by
│  planeacion_completada_at
│
├── programa_team (pivot) ── rol: coordinadora | coadyuvante
│
├── MirNivel (tipo: FIN) ── 1 por programa
│   │  resumen_narrativo, supuestos, team_id
│   │  ped_objetivo_estrategico_id (alineacion PED)
│   │  sintaxis_valida, sintaxis_observacion, sintaxis_sugerencia
│   │
│   └── Indicador (tipo: estrategico, dim: eficacia, frec: anual|bianual|sexenal)
│       ├── nombre, formula_texto, sentido, linea_base, meta
│       ├── rangos: verde_min/max, amarillo_min/max, rojo_min/max
│       ├── unidad_medida_id, activo_seguimiento
│       ├── IndicadorVariable[] ── simbolo(A,B..), nombre, descripcion, unidad_medida_id
│       ├── MedioVerificacion[] ── nombre, descripcion, fuente, frecuencia
│       ├── CremaaValidacion ── claro, relevante, economico, monitoreable, adecuado, aportante
│       └── MetaPeriodo[] ── periodo, meta_periodo, ejercicio_fiscal, fecha_apertura, fecha_cierre
│
├── MirNivel (tipo: PROPOSITO) ── 1 por programa
│   │  (misma estructura, dim: eficacia|eficiencia, frec: semestral|anual)
│   └── Indicador(es)...
│
├── MirNivel (tipo: COMPONENTE) ── N por programa
│   │  resumen_narrativo, supuestos, team_id (UR coadyuvante)
│   │  (dim: eficacia|eficiencia|calidad, frec: trimestral|semestral)
│   │
│   ├── Indicador(es)...
│   │
│   └── MirNivel (tipo: ACTIVIDAD) ── N por componente
│       │  componente_id, team_id
│       │  (tipo: gestion, dim: eficacia|eficiencia|economia, frec: mensual|trimestral)
│       └── Indicador(es)...
│
└── EstadoValidacionPrograma ── consolidado tripartita
    planeacion_estado, juridico_estado, financiero_estado
    consolidado: completo | parcial | critico
```

## Reglas de Negocio por Nivel MIR

| Nivel | Cantidad | Tipo indicador | Dimensiones permitidas | Frecuencias permitidas |
|-------|----------|---------------|----------------------|----------------------|
| FIN | 1 fijo | estrategico (fijo) | eficacia | anual, bianual, sexenal |
| PROPOSITO | 1 fijo | estrategico (fijo) | eficacia, eficiencia | semestral, anual |
| COMPONENTE | N (agregable) | estrategico o gestion | eficacia, eficiencia, calidad | trimestral, semestral |
| ACTIVIDAD | N por componente | gestion (fijo) | eficacia, eficiencia, economia | mensual, trimestral |

Fuente: `App\Services\Mml\IndicadorReglasService`

## Campos Completos del Indicador

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|-------|
| nombre | string(255) | Si | Nombre descriptivo del indicador |
| formula_texto | text | No* | Texto libre, ej: "(A / B) * 100" |
| tipo | enum | Si | estrategico / gestion (segun nivel) |
| dimension | enum | Si | eficacia / eficiencia / calidad / economia |
| frecuencia | enum | Si | mensual a sexenal (segun nivel) |
| sentido | enum | No | ascendente / descendente / regular |
| linea_base | decimal(12,4) | No | Valor de referencia inicial |
| meta | decimal(12,4) | No | Valor objetivo del programa |
| rango_verde_min/max | decimal(8,2) | No | Rango semaforo verde |
| rango_amarillo_min/max | decimal(8,2) | No | Rango semaforo amarillo |
| rango_rojo_min/max | decimal(8,2) | No | Rango semaforo rojo (no en BD, calculado) |
| unidad_medida_id | FK | No | Catalogo de unidades |
| activo_seguimiento | bool | Si | Si se reportara en tracking |

## Hijos del Indicador

### IndicadorVariable
- simbolo: string(5) — A, B, C...
- nombre: string
- descripcion: text (nullable)
- comportamiento: string(20) (nullable)
- unidad_medida_id: FK (nullable)
- orden: smallint

### MedioVerificacion
- nombre: string
- descripcion: text (nullable)
- fuente: string (nullable)
- frecuencia: string(20) (nullable)
- orden: smallint

### CremaaValidacion (1:1 con Indicador)
6 criterios booleanos + observacion cada uno:
- claro / claro_observacion
- relevante / relevante_observacion
- economico / economico_observacion
- monitoreable / monitoreable_observacion
- adecuado / adecuado_observacion
- aportante / aportante_observacion

### MetaPeriodo
- periodo: smallint (1, 2, 3... segun frecuencia)
- meta_periodo: decimal(12,4)
- ejercicio_fiscal: int
- activo: bool
- fecha_apertura: date
- fecha_cierre: date
- Unique: [indicador_id, periodo, ejercicio_fiscal]

## Involucrados por Accion dentro de la MIR

### PLANEADOR (UR coordinadora) — editar_mir
- Crea programa (crear_programa)
- Navega wizard E1-E6
- En E7 (MirEditor):
  - Edita resumen_narrativo y supuestos de TODOS los niveles
  - Agrega/elimina componentes y actividades
  - Agrega/elimina indicadores por nivel
  - Define formula_texto, tipo, dimension, frecuencia, sentido
  - Define linea_base, meta, rangos de semaforo
  - Agrega/elimina variables de formula (o extrae con IA)
  - Agrega/elimina medios de verificacion
  - Ejecuta validacion de sintaxis (IA)
  - Ejecuta validacion CREMAA (IA)
  - Ejecuta validacion logica vertical/horizontal (IA)
  - Busca y asigna alineacion PED (semantica IA)
  - Asigna UR coadyuvante a componentes/actividades
  - Crea/restaura snapshots de version
  - Vincula indicadores a anexos transversales

### PLANEADOR (UR coadyuvante)
- Edita componentes/actividades asignados a su team_id
- Mismo alcance que coordinadora pero solo en sus niveles

### ANALISTA_FINANCIERO
- NO participa en creacion de MIR
- Ve MIR como contexto (ver_datos_financieros)
- Su trabajo empieza DESPUES: crear partidas y calendarizar

### ANALISTA_JURIDICO
- NO participa en creacion de MIR
- Ve MIR como contexto (ver_sustento_legal)
- Su trabajo: registrar fundamento legal del programa

### OPERADOR
- NO participa en creacion de MIR
- Su trabajo empieza DESPUES: capturar avances de indicadores

### ADMIN
- Puede hacer TODO (bypass Gate::before)
- En la practica: supervisa, no crea MIR

## Validacion Tripartita (EstadoConsolidadoService)

Evalua el programa desde 3 areas independientes:

| Area | Quien la completa | Estados posibles | "Completa" cuando |
|------|-------------------|-----------------|-------------------|
| Planeacion | Planeador | incompleta -> mir_borrador -> mir_completa -> mir_validada | mir_completa o mir_validada |
| Juridico | Analista Juridico | sin_registro -> pendiente -> en_revision -> validado / rechazado | validado |
| Financiero | Analista Financiero | sin_partidas -> parcial -> costeado -> calendarizado | costeado o calendarizado |

### Consolidado
- `completo` = 3 de 3 areas validadas
- `parcial` = 1-2 de 3 (alerta: "riesgo de observacion en auditoria")
- `critico` = 0 de 3 (alerta: "riesgo de observacion ASFE")

Se notifica via ValidacionTripartitaNotification cuando cambia el estado de un area.

## Brechas del QaTestingSeeder vs Modelo Real

| Dato | Existe en modelo | Seedeado |
|------|:---:|:---:|
| MirNivel (4 tipos) | Si | Si (parcial, sin alineacion PED) |
| Indicador (campos basicos) | Si | Si (nombre, tipo, dim, frec, sentido, meta, linea_base) |
| formula_texto | Si | **No** |
| IndicadorVariable | Si | **No** |
| MedioVerificacion | Si | **No** |
| CremaaValidacion | Si | **No** |
| unidad_medida_id | Si | **No** |
| Rangos semaforo (verde/amarillo) | Si | Solo 1 indicador |
| ped_objetivo_estrategico_id | Si | **No** |
| ped_linea_accion_id | Si | **No** |
| programa_derivado_objetivo_id | Si | **No** |
| EstadoValidacionPrograma | Si | **No** |
| Sintaxis validada (IA) | Si | Solo 1 defecto en FSP-003 |
| Snapshots (MirVersion) | Si | **No** |
