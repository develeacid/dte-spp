# Esquema: Catálogo ODS (Agenda 2030)

Catálogo inmutable de referencia para la Matriz de Alineación.

## Tabla: ods_objetivos

| Columna     | Tipo         | Notas                                       |
| ----------- | ------------ | ------------------------------------------- |
| id          | bigint       | PK                                          |
| numero      | tinyint      | 1-17, UNIQUE                                |
| nombre      | string       | Nombre corto                                |
| descripcion | text         | Descripción oficial                         |
| embedding   | vector(1536) | Para búsqueda semántica (null hasta S2-T10) |
| timestamps  | —            | created_at, updated_at                      |

## Tabla: ods_metas

| Columna         | Tipo         | Notas                                       |
| --------------- | ------------ | ------------------------------------------- |
| id              | bigint       | PK                                          |
| ods_objetivo_id | FK           | Cascade on delete                           |
| clave           | string(10)   | "1.1", "13.2", "1.a", etc. UNIQUE           |
| descripcion     | text         | Texto oficial de la meta                    |
| embedding       | vector(1536) | Para búsqueda semántica (null hasta S2-T10) |
| timestamps      | —            | created_at, updated_at                      |

## Relaciones

- `OdsObjetivo::metas()` → HasMany
- `OdsMeta::objetivo()` → BelongsTo
