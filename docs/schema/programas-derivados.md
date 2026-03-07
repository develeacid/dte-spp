# Esquema: Programas Derivados

Instrumentos de planeación intermedios que emanan del PED.

## Tipos de Programa

| Tipo          | Prefijo | Descripción                                  |
| ------------- | ------- | -------------------------------------------- |
| Sectorial     | OS      | Programas sectoriales del desarrollo estatal |
| Especial      | OE      | Programas para problemáticas específicas     |
| Institucional | OI      | Programas de gestión institucional           |
| Regional      | OR      | Programas de desarrollo regional             |

## Tablas

### programas_derivados

| Columna     | Tipo   | Notas                                      |
| ----------- | ------ | ------------------------------------------ |
| id          | bigint | PK                                         |
| ped_plan_id | FK     | Cascade on delete                          |
| tipo        | ENUM   | `tipo_programa_derivado` nativo PostgreSQL |
| nombre      | string | Nombre del programa                        |
| descripcion | text   | Nullable                                   |
| timestamps  | —      | —                                          |

**ENUM PostgreSQL:**

```sql
CREATE TYPE tipo_programa_derivado AS ENUM (
    'sectorial',
    'especial',
    'institucional',
    'regional'
);
```

### programas_derivados_objetivos

| Columna              | Tipo         | Notas                                  |
| -------------------- | ------------ | -------------------------------------- |
| id                   | bigint       | PK                                     |
| programa_derivado_id | FK           | Cascade on delete                      |
| clave                | string(20)   | "1", "2", etc. UNIQUE con programa_id  |
| descripcion          | text         | Texto del objetivo                     |
| embedding            | vector(1536) | Búsqueda semántica (null hasta S2-T10) |
| timestamps           | —            | —                                      |

## Relaciones Eloquent

```php
// Navegación
$programa->plan;        // BelongsTo PedPlan
$programa->objetivos;   // HasMany ProgramaDerivadoObjetivo

$objetivo->programa;    // BelongsTo ProgramaDerivado
$objetivo->plan;        // A través de programa

// Scopes por tipo
ProgramaDerivado::sectoriales()->get();
ProgramaDerivado::especiales()->get();
ProgramaDerivado::institucionales()->get();
ProgramaDerivado::regionales()->get();

// Utilidades
$programa->tipo->label();      // "Programa Sectorial"
$programa->prefijoClave();      // "OS"
$objetivo->clave_completa;      // "OS.1"
```

## PHP Enum

```php
use App\Enums\TipoProgramaDerivado;

TipoProgramaDerivado::SECTORIAL;        // case
TipoProgramaDerivado::SECTORIAL->value; // "sectorial"
TipoProgramaDerivado::SECTORIAL->label(); // "Programa Sectorial"
TipoProgramaDerivado::values(); // ['sectorial', 'especial', ...]
```

## Vinculación con PED

Los objetivos de programas derivados se vinculan con Líneas de Acción del PED mediante la **Matriz de Alineación** (S2-T5).

```
ProgramaDerivadoObjetivo ↔ PedLineaAccion
         (N:N via tabla pivote)
```
