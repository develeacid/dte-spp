# Esquema: teams (Unidades Responsables)

La tabla `teams` nativa de Jetstream ha sido extendida para actuar como el catálogo de **Unidades Responsables (UR)** del Estado.

| Columna | Tipo | Postgres | Nullable | Notas / Constraints |
| :--- | :--- | :--- | :--- | :--- |
| `id` | bigint | int8 | No | PK |
| `user_id` | bigint | int8 | No | FK -> users.id (Propietario del Team) |
| `name` | string | varchar | No | Nombre oficial de la dependencia |
| `personal_team` | boolean | bool | No | `true` para los equipos por defecto del usuario, `false` para URs reales |
| `clave_ur` | string | varchar | Sí | Clave gubernamental. `UNIQUE` |
| `titular` | string | varchar | Sí | Nombre del responsable de la UR |
| `tipo_ur` | string | varchar | Sí | Casteado a Enum PHP (`sustantiva`, `apoyo`) |
| `activa` | boolean | bool | No | Determina si la UR puede operar en el sistema. Default `true` |
| `timestamps` | timestamp | timestamp | Sí | created_at, updated_at |

**Consideraciones:**
- La validación del Enum se hace en el modelo Eloquent usando `App\Enums\TipoUnidadResponsable`.
- Las URs institucionales deben tener `personal_team = false`.
