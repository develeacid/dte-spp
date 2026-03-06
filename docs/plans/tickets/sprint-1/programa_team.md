# Esquema: programa_team (Pivote Multi-UR)

Esta tabla pivote es el núcleo del mecanismo de aislamiento Multi-UR. Registra qué Unidades Responsables (`teams`) participan en qué Programas Presupuestarios y con qué rol.

## Estructura de Columnas

| Columna                      | Tipo        | Nullable | Constraints                         | Notas                                                        |
| :--------------------------- | :---------- | :------: | :---------------------------------- | :----------------------------------------------------------- |
| `id`                         | bigint      |    No    | PK                                  | Identificador único de la relación.                          |
| `programa_presupuestario_id` | bigint      |    No    | FK → `programas_presupuestarios.id` | El programa al que se asocia el equipo.                      |
| `team_id`                    | bigint      |    No    | FK → `teams.id`                     | El equipo (UR) que participa.                                |
| `rol`                        | varchar(20) |    No    | Default: `coadyuvante`              | Rol de la UR en el programa: `coordinadora` o `coadyuvante`. |
| `created_at`                 | timestamp   |    Sí    | —                                   | Timestamp de creación.                                       |
| `updated_at`                 | timestamp   |    Sí    | —                                   | Timestamp de última actualización.                           |

## Consideraciones de Diseño

- **Constraint de Unicidad:** Existe un índice `UNIQUE` en la combinación `(programa_presupuestario_id, team_id)` para asegurar que una UR solo puede tener un rol por programa.
- **Tipo de `rol`:** Se utiliza `string` en lugar de `enum` nativo de la base de datos para mayor flexibilidad y compatibilidad con PostgreSQL, siguiendo el patrón arquitectónico del proyecto.

## Relaciones

- Esta tabla materializa la relación `belongsToMany` entre `ProgramaPresupuestario` y `Team`.
