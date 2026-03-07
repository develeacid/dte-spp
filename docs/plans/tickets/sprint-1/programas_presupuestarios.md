# Esquema: programas_presupuestarios (Stub)

> **Estado:** Stub — Pendiente de expansión en Sprint 3.

Esta tabla actúa como la cabecera para los Programas Presupuestarios. En el Sprint 1, se creó como un **stub** con los campos mínimos para satisfacer las dependencias del middleware de aislamiento Multi-UR (`S1-T5`).

Será expandida con todos los campos de dominio (ejercicio fiscal, estado, etc.) en el **Sprint 3 (S3-T1)**.

## Estructura de Columnas (Stub)

| Columna      | Tipo        | Nullable | Constraints | Notas                              |
| :----------- | :---------- | :------: | :---------- | :--------------------------------- |
| `id`         | bigint      |    No    | PK          | Identificador único del programa.  |
| `nombre`     | varchar     |    No    | —           | Nombre del programa.               |
| `clave`      | varchar(50) |    No    | UNIQUE      | Clave única del programa.          |
| `created_at` | timestamp   |    Sí    | —           | Timestamp de creación.             |
| `updated_at` | timestamp   |    Sí    | —           | Timestamp de última actualización. |

## Relaciones

- `ProgramaPresupuestario` `belongsToMany` `Team` (a través de `programa_team`).
- `ProgramaPresupuestario` `hasMany` `MirNivel` (se definirá en Sprint 4).
