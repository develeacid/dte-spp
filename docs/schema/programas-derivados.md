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

| Columna              | Tipo         | Notas                                           |
| -------------------- | ------------ | ----------------------------------------------- |
| id                   | bigint       | PK                                              |
| programa_derivado_id | FK           | Cascade on delete                               |
| clave                | string(20)   | "1", "2", etc. UNIQUE con programa_derivado_id  |
| descripcion          | text         | Texto del objetivo                              |
| embedding            | vector(1536) | Búsqueda semántica (pgvector, nullable)         |
| timestamps           | —            | —                                               |

**Nota:** La columna `embedding` se agrega vía `ALTER TABLE` con tipo `vector(1536)` de pgvector. Almacena embeddings OpenAI para búsqueda semántica de objetivos similares.

### alineacion_linea_programa (tabla pivote)

Vinculación N:N entre objetivos de programas derivados y líneas de acción del PED.

| Columna                        | Tipo   | Notas             |
| ------------------------------ | ------ | ----------------- |
| id                             | bigint | PK                |
| ped_linea_accion_id            | FK     | Cascade on delete |
| programa_derivado_objetivo_id  | FK     | Cascade on delete |
| timestamps                     | —      | —                 |

**Constraint UNIQUE:** `(ped_linea_accion_id, programa_derivado_objetivo_id)`

## Relaciones Eloquent

```php
// ProgramaDerivado
$programa->plan;        // BelongsTo PedPlan
$programa->objetivos;   // HasMany ProgramaDerivadoObjetivo

// ProgramaDerivadoObjetivo
$objetivo->programa;          // BelongsTo ProgramaDerivado
$objetivo->plan;              // A través de programa (método directo)
$objetivo->lineasAccionPed;   // BelongsToMany PedLineaAccion vía alineacion_linea_programa
$objetivo->clave_completa;    // Accessor: "OS.1", "OE.2", etc.

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

### Accessor: `getClaveCompletaAttribute()`

Combina el prefijo del tipo del programa padre con la clave del objetivo:

```php
// En ProgramaDerivadoObjetivo
public function getClaveCompletaAttribute(): string
{
    return $this->programa->prefijoClave() . '.' . $this->clave;
}
```

## PHP Enum: `TipoProgramaDerivado`

```php
use App\Enums\TipoProgramaDerivado;

TipoProgramaDerivado::SECTORIAL;           // case
TipoProgramaDerivado::SECTORIAL->value;    // "sectorial"
TipoProgramaDerivado::SECTORIAL->label();  // "Programa Sectorial"
TipoProgramaDerivado::values();            // ['sectorial', 'especial', ...]
```

### Métodos del Enum

| Método         | Retorno | Descripción                                           |
| -------------- | ------- | ----------------------------------------------------- |
| `label()`      | string  | Etiqueta legible ("Programa Sectorial", etc.)         |
| `descripcion()`| string  | Descripción larga del tipo de programa                |
| `prefijo()`    | string  | Prefijo de clave (OS, OE, OI, OR)                     |
| `colorClass()` | string  | Clases Tailwind para badge de color por tipo          |
| `values()`     | array   | Array de todos los valores string (static)            |

### Colores por tipo

| Tipo          | Clases Tailwind                    |
| ------------- | ---------------------------------- |
| Sectorial     | `bg-blue-100 text-blue-800`        |
| Especial      | `bg-green-100 text-green-800`      |
| Institucional | `bg-purple-100 text-purple-800`    |
| Regional      | `bg-orange-100 text-orange-800`    |

## Vinculación con PED

Los objetivos de programas derivados se vinculan con Líneas de Acción del PED mediante la **Matriz de Alineación** (tabla pivote `alineacion_linea_programa`).

```
ProgramaDerivadoObjetivo ↔ PedLineaAccion
         (N:N via alineacion_linea_programa)
```
