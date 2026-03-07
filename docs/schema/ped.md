# Esquema: Plan Estatal de Desarrollo (PED)

Estructura central editable del sistema. Jerarquía de 6 niveles.

## Estructura Jerárquica

```
PedPlan (Plan)
└── PedEje (Eje)
    └── PedTema (Tema/Sub-eje)
        └── PedObjetivoEstrategico (Objetivo)
            └── PedEstrategia (Estrategia)
                └── PedLineaAccion (Línea de Acción)
```

## Tablas

### ped_planes

| Columna | Tipo | Notas |
|---------|------|-------|
| id | bigint | PK |
| nombre | string | Nombre del plan |
| nivel_gobierno | string | `estatal` o `municipal` |
| periodo_inicio | smallint | Año inicio (ej: 2025) |
| periodo_fin | smallint | Año fin (ej: 2030) |
| activo | boolean | Constraint: solo 1 activo |
| timestamps | — | — |

**Constraint parcial:** `CREATE UNIQUE INDEX ... WHERE activo = true`

### ped_ejes, ped_temas, ped_objetivos_estrategicos, ped_estrategias, ped_lineas_accion

Todas comparten estructura similar:
- `id` (PK)
- `[nivel_padre]_id` (FK con cascadeOnDelete)
- `numero` o `clave` (string)
- `nombre` o `descripcion` (text)
- `embedding` (vector 1536)
- `timestamps`

## Relaciones Eloquent

```php
// Navegación hacia abajo
$plan->ejes;
$eje->temas;
$tema->objetivosEstrategicos;
$objetivo->estrategias;
$estrategia->lineasAccion;

// Navegación hacia arriba
$lineaAccion->estrategia;
$lineaAccion->objetivoEstrategico();
$lineaAccion->tema();
$lineaAccion->eje();
$lineaAccion->plan();

// Accessor de clave completa
$lineaAccion->clave_completa; // "1.2.3.4.5"
```

## Métodos Auxiliares

```php
// Obtener plan activo
PedPlan::planActivo();

// Activar un plan (desactiva los demás)
$plan->activar();
```
