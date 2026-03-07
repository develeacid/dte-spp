# Plan: S1-T2 — Extender tabla teams con campos de Unidad Responsable

**Ticket:** S1-T2 | **Tipo:** feat | **Rama:** `feat/S1-T2-teams-campos-ur` | **Sprint:** 1 | **Depende de:** S1-T1

---

## Contexto

Jetstream crea la tabla `teams` con campos base (`id`, `user_id`, `name`, `personal_team`, `timestamps`). En este sistema, cada `Team` representa una **Unidad Responsable (UR)** del gobierno estatal. 

Se deben agregar campos específicos del dominio:
- **clave_ur:** Clave oficial gubernamental (única)
- **titular:** Nombre del responsable de la UR
- **tipo_ur:** Tipo de unidad (sustantiva o apoyo) — validado con PHP Enum para evitar fricciones con PostgreSQL
- **activa:** Estado operativo de la UR

**Mejoras técnicas aplicadas:**
- Eliminación de `after()` por incompatibilidad con PostgreSQL
- Uso de Backed Enums de PHP en lugar de ENUM nativo de BD
- Validación en acciones de Jetstream
- Seeder anti-duplicados con `updateOrCreate()`
- Documentación de esquema incluida
- Pruebas de rollback/reversión

---

## Pre-requisitos

- S1-T1 completado (Jetstream con Teams instalado y migrado)

---

## Pasos

### 1. Crear el Enum para Tipo de UR

Crear el archivo `app/Enums/TipoUnidadResponsable.php`:

```php
<?php

namespace App\Enums;

enum TipoUnidadResponsable: string
{
    case SUSTANTIVA = 'sustantiva';
    case APOYO = 'apoyo';
}
```

**Justificación:** Los Backed Enums de PHP (8.1+) proporcionan validación estricta a nivel de aplicación sin amarrarse a las limitaciones del motor de BD para modificar ENUMs nativos.

---

### 2. Crear y editar la migración

```bash
sail artisan make:migration add_campos_ur_to_teams_table --table=teams
```

Editar el archivo generado en `database/migrations/`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('clave_ur')->nullable()->unique();
            $table->string('titular')->nullable();
            $table->string('tipo_ur')->nullable(); // Cast a PHP Enum en el modelo
            $table->boolean('activa')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['clave_ur', 'titular', 'tipo_ur', 'activa']);
        });
    }
};
```

**Notas técnicas:**
- ✅ Se omite `after()` porque PostgreSQL no soporta reordenar columnas nativamente
- ✅ `tipo_ur` se define como `string` (cast a Enum en el modelo)
- ✅ El `down()` revierte todos los cambios

---

### 3. Ejecutar la migración

```bash
sail artisan migrate
```

---

### 4. Actualizar el modelo Team

Abrir `app/Models/Team.php` y reemplazar todo el contenido:

```php
<?php

namespace App\Models;

use App\Enums\TipoUnidadResponsable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Jetstream\Events\TeamCreated;
use Laravel\Jetstream\Events\TeamDeleted;
use Laravel\Jetstream\Events\TeamUpdated;
use Laravel\Jetstream\Team as JetstreamTeam;

class Team extends JetstreamTeam
{
    use HasFactory;

    protected $fillable = [
        'name',
        'personal_team',
        'clave_ur',
        'titular',
        'tipo_ur',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
            'activa' => 'boolean',
            'tipo_ur' => TipoUnidadResponsable::class, // Cast a Enum con validación
        ];
    }

    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'updated' => TeamUpdated::class,
        'deleted' => TeamDeleted::class,
    ];
}
```

**Cambios principales:**
- ✅ Agregados `clave_ur`, `titular`, `tipo_ur`, `activa` al `$fillable`
- ✅ Cast de `tipo_ur` a `TipoUnidadResponsable::class` para validación automática
- ✅ Método `casts()` moderno (Laravel 11+) en lugar de propiedad `$casts`

---

### 5. Actualizar acciones de Jetstream (Validación)

Editar `app/Actions/Jetstream/CreateTeam.php` para agregar validación en el método `validate()`:

```php
use Illuminate\Validation\Rule;
use App\Enums\TipoUnidadResponsable;

// Dentro del método validate() o similar:
Validator::make($input, [
    'name' => ['required', 'string', 'max:255'],
    'clave_ur' => ['nullable', 'string', 'max:20', 'unique:teams,clave_ur'],
    'titular' => ['nullable', 'string', 'max:255'],
    'tipo_ur' => ['nullable', Rule::enum(TipoUnidadResponsable::class)],
])->validateWithBag('createTeam');
```

Si existe un formulario de actualización (`UpdateTeamName.php`), aplicar las mismas validaciones.

---

### 6. Crear el Seeder de URs (Anti-duplicados)

```bash
sail artisan make:seeder UnidadesResponsablesSeeder
```

Editar `database/seeders/UnidadesResponsablesSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Enums\TipoUnidadResponsable;
use Illuminate\Database\Seeder;

class UnidadesResponsablesSeeder extends Seeder
{
    public function run(): void
    {
        // UR Sustantiva
        Team::updateOrCreate(
            ['clave_ur' => 'SE-001'],
            [
                'user_id'       => 1, // Se sobreescribe en S1-T6
                'name'          => 'Secretaría de Educación',
                'titular'       => 'Dr. Juan Pérez',
                'tipo_ur'       => TipoUnidadResponsable::SUSTANTIVA,
                'activa'        => true,
                'personal_team' => false,
            ]
        );

        // UR de Apoyo
        Team::updateOrCreate(
            ['clave_ur' => 'SS-002'],
            [
                'user_id'       => 1,
                'name'          => 'Secretaría de Salud',
                'titular'       => 'Dra. María López',
                'tipo_ur'       => TipoUnidadResponsable::APOYO,
                'activa'        => true,
                'personal_team' => false,
            ]
        );

        // UR Inactiva
        Team::updateOrCreate(
            ['clave_ur' => 'SEG-003'],
            [
                'user_id'       => 1,
                'name'          => 'Secretaría de Seguridad',
                'titular'       => 'Lic. Roberto Sánchez',
                'tipo_ur'       => TipoUnidadResponsable::SUSTANTIVA,
                'activa'        => false,
                'personal_team' => false,
            ]
        );
    }
}
```

**Ventaja:** `updateOrCreate()` evita errores de duplicidad si el seeder se ejecuta múltiples veces.

---

### 7. Crear archivo de documentación del esquema

Crear `docs/schema/teams.md`:

```markdown
# Esquema: teams (Unidades Responsables)

La tabla `teams` nativa de Jetstream ha sido extendida para actuar como el catálogo de **Unidades Responsables (UR)** del Estado.

## Estructura de columnas

| Columna | Tipo | Nullable | Constraints | Notas |
| :--- | :--- | :---: | :--- | :--- |
| `id` | bigint | No | PK | Identificador único |
| `user_id` | bigint | No | FK → users.id | Propietario/creador del Team |
| `name` | varchar | No | — | Nombre oficial de la dependencia |
| `personal_team` | boolean | No | Default: false | `true` para equipos personales de Jetstream; `false` para URs institucionales |
| `clave_ur` | varchar | Sí | UNIQUE | Clave gubernamental oficial. Se permite null para personal teams |
| `titular` | varchar | Sí | — | Nombre completo del responsable de la UR |
| `tipo_ur` | varchar | Sí | — | Tipo de unidad: `sustantiva` o `apoyo` (casteado a Enum PHP) |
| `activa` | boolean | No | Default: true | Determina si la UR puede operar en el sistema |
| `created_at` | timestamp | Sí | — | Timestamp de creación |
| `updated_at` | timestamp | Sí | — | Timestamp de última actualización |

## Consideraciones de diseño

- **Validación de Enum:** El campo `tipo_ur` se valida usando `App\Enums\TipoUnidadResponsable` a nivel de aplicación, no en la BD
- **Personal Teams:** Jetstream auto-crea un `personal_team` al registrar usuarios. Estos tienen `personal_team = true` y típicamente no tienen `clave_ur`
- **Compatibilidad PostgreSQL:** Se usa `string` en lugar de `enum` nativo para evitar fricciones en migraciones futuras
- **Unicidad de clave_ur:** Es UNIQUE pero nullable para permitir teams personales

## Relaciones

- `Team` → `User` (owner): Relación 1:1 a través de `user_id`
- `Team` → `User` (members): Relación N:N a través de tabla `team_user` (Jetstream)
```

---

### 8. Ejecutar pruebas de reversión y rollback

Antes de dar por completado, ejecutar la secuencia de validación:

```bash
# Prueba 1: Verificar que toda la cadena de migraciones funciona
sail artisan migrate:fresh

# Prueba 2: Ejecutar con seeder incluido
sail artisan migrate:fresh --seed

# Prueba 3: Reversión específica de esta migración (rollback de 1 paso)
sail artisan migrate:rollback --step=1

# Prueba 4: Reaplicar solo esta migración
sail artisan migrate

# Prueba 5: Verificar estructura final
sail artisan tinker
Schema::getColumnListing('teams');
// Expected: ['id', 'user_id', 'name', 'personal_team', 'clave_ur', 'titular', 'tipo_ur', 'activa', 'created_at', 'updated_at']
exit
```

---

## Criterios de aceptación

- [ ] Enum `App\Enums\TipoUnidadResponsable` creado con casos SUSTANTIVA y APOYO
- [ ] Migración omite `after()` y define `tipo_ur` como `string`
- [ ] El método `down()` de la migración revierte todos los campos correctamente
- [ ] `sail artisan migrate` ejecuta sin errores
- [ ] Modelo `Team` con `$fillable` actualizado y `casts()` configurado correctamente
- [ ] Validación agregada en `App\Actions\Jetstream\CreateTeam` (y `UpdateTeamName` si existe)
- [ ] Seeder `UnidadesResponsablesSeeder` usa `updateOrCreate()` y crea 3 URs (1 sustantiva, 1 apoyo, 1 inactiva)
- [ ] `sail artisan migrate:fresh --seed` ejecuta sin errores y crea las 3 URs
- [ ] `sail artisan migrate:rollback --step=1` revierte sin errores
- [ ] Archivo `docs/schema/teams.md` existe y documenta todas las columnas y consideraciones
- [ ] Tinker verifica que los campos estén en la tabla

---

## Notas importantes

- El archivo `docs/schema/teams.md` debe ser actualizado junto con esta migración
- `clave_ur` es `nullable` específicamente para permitir que los personal teams de Jetstream no requieran clave
- La validación a nivel aplicación (Enum + reglas de validación en acciones) ocurre antes de tocar la BD
- Este ticket cierra la extensión base de la tabla `teams`. Los campos de relaciones usuario-team se manejan en S1-T3
