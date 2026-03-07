# Esquema: Matriz de Alineación

Conjunto de tablas pivote que conectan todos los niveles de la cascada de planeación.

## Diagrama de Relaciones

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ AGENDA 2030                                                                 │
│ ODS Objetivo → ODS Meta                                                     │
└─────────────────────────────────────────────────────────────────────────────┘
        ↑
        │ alineacion_pnd_ods
┌─────────────────────────────────────────────────────────────────────────────┐
│ PND                                                                         │
│ Eje → Objetivo → Estrategia                                                 │
└─────────────────────────────────────────────────────────────────────────────┘
        ↑
        │ alineacion_ped_pnd
┌─────────────────────────────────────────────────────────────────────────────┐
│ PED                                                                         │
│ Plan → Eje → Tema → Objetivo Estratégico → Estrategia → Línea Acción        │
└─────────────────────────────────────────────────────────────────────────────┘
        │
        │ alineacion_linea_programa
        ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│ PROGRAMAS DERIVADOS                                                         │
│ Programa → Objetivo                                                         │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Tablas Pivote

### alineacion_ped_pnd

Conecta Objetivos Estratégicos del PED con Objetivos del PND.

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| ped_objetivo_estrategico_id | FK | Cascade delete → `ped_objetivos_estrategicos` |
| pnd_objetivo_id | FK | Cascade delete → `pnd_objetivos` |
| timestamps | — | created_at, updated_at |

**Constraint:** `UNIQUE(ped_objetivo_estrategico_id, pnd_objetivo_id)`

### alineacion_pnd_ods

Conecta Objetivos del PND con Metas de los ODS.

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| pnd_objetivo_id | FK | Cascade delete → `pnd_objetivos` |
| ods_meta_id | FK | Cascade delete → `ods_metas` |
| timestamps | — | created_at, updated_at |

**Constraint:** `UNIQUE(pnd_objetivo_id, ods_meta_id)`

### alineacion_linea_programa

Conecta Líneas de Acción del PED con Objetivos de Programas Derivados.

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| ped_linea_accion_id | FK | Cascade delete → `ped_lineas_accion` |
| programa_derivado_objetivo_id | FK | Cascade delete → `programas_derivados_objetivos` |
| timestamps | — | created_at, updated_at |

**Constraint:** `UNIQUE(ped_linea_accion_id, programa_derivado_objetivo_id)`

## Relaciones Eloquent

```php
// PED → PND
$pedObjetivo->pndObjetivos;             // BelongsToMany
$pndObjetivo->pedObjetivosEstrategicos; // BelongsToMany (inversa)

// PND → ODS
$pndObjetivo->odsMetas;                 // BelongsToMany
$odsMeta->pndObjetivos;                 // BelongsToMany (inversa)

// Línea de Acción ↔ Programa Derivado
$lineaAccion->programasDerivadosObjetivos;  // BelongsToMany
$progObjetivo->lineasAccionPed;             // BelongsToMany (inversa)
```

## Navegación de Cadena Completa

```php
// Desde Línea de Acción hasta ODS
$linea = PedLineaAccion::with([
    'estrategia.objetivoEstrategico.pndObjetivos.odsMetas.objetivo'
])->first();

$pedObjetivo = $linea->estrategia->objetivoEstrategico;
$pndObjetivos = $pedObjetivo->pndObjetivos;
$odsMetas = $pndObjetivos->flatMap->odsMetas;

// Desde ODS hasta PED (inversa)
$meta = OdsMeta::with([
    'pndObjetivos.pedObjetivosEstrategicos.tema.eje.plan'
])->first();

$pndObjetivos = $meta->pndObjetivos;
$pedObjetivos = $pndObjetivos->flatMap->pedObjetivosEstrategicos;
```

## Cardinalidad

| Relación                      | Cardinalidad | Notas                                            |
| ----------------------------- | ------------ | ------------------------------------------------ |
| PED Objetivo ↔ PND Objetivo   | N:N          | Un objetivo PED puede alinearse a múltiples PND  |
| PND Objetivo ↔ ODS Meta       | N:N          | Un objetivo PND puede contribuir a múltiples ODS |
| Línea Acción ↔ Prog. Derivado | N:N          | Una línea puede vincularse a múltiples programas |

## Uso en MIR (Sprint 4)

Cuando se crea una MIR:

1. El **Fin** se alinea a un Objetivo Estratégico PED
2. El sistema **hereda automáticamente** las alineaciones PND y ODS
3. Esto garantiza coherencia metodológica (PbR)
