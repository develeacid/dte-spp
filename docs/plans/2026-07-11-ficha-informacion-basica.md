# Ficha de Información Básica — 5 preguntas del diagnóstico (M02 req 4) — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Estructurar en la Etapa 1 las preguntas Q2–Q5 del diagnóstico (magnitud, focalización, causas/efectos, bienes/servicios), reutilizando el problema central (Q1).

**Architecture:** Tabla `fichas_informacion_basica` 1:1 con programa + modelo `FichaInformacionBasica`. El componente `DefinicionProblema` (Etapa 1) gana 4 props que se cargan en `mount()` y se upsertean en `guardar()` junto al problema; badge de completitud X/5. Q2–Q5 opcionales.

**Tech Stack:** Laravel 12, Livewire 3, PostgreSQL, Sail/PHPUnit.

**Design doc:** `docs/plans/2026-07-11-ficha-informacion-basica-design.md`
**Rama:** `feat/ficha-informacion-basica` (creada, design doc commiteado)
**Comando de tests:** `./vendor/bin/sail artisan test --filter=<X>`

---

## Task 1: Migración + modelo + relación

**Files:**
- Create: `database/migrations/2026_07_11_000002_create_fichas_informacion_basica_table.php`
- Create: `app/Models/Mml/FichaInformacionBasica.php`
- Modify: `app/Models/ProgramaPresupuestario.php` (relación `fichaInformacionBasica`)
- Test: `tests/Feature/Mml/DefinicionProblemaFichaTest.php` (nuevo)

**Step 1: Write the failing test**

Crear `tests/Feature/Mml/DefinicionProblemaFichaTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\DefinicionProblema;
use App\Models\Mml\FichaInformacionBasica;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Livewire\Livewire;
use Tests\TestCase;

class DefinicionProblemaFichaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Ficha',
            'clave' => 'PT-FICHA-'.uniqid(),
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_relacion_ficha_es_hasone(): void
    {
        $this->assertInstanceOf(HasOne::class, $this->programa->fichaInformacionBasica());
    }

    public function test_ficha_se_persiste(): void
    {
        $ficha = FichaInformacionBasica::create([
            'programa_presupuestario_id' => $this->programa->id,
            'magnitud' => '120,000 alumnos (Estadística 911)',
        ]);

        $this->assertDatabaseHas('fichas_informacion_basica', [
            'programa_presupuestario_id' => $this->programa->id,
            'magnitud' => '120,000 alumnos (Estadística 911)',
        ]);
        $this->assertSame($ficha->id, $this->programa->fichaInformacionBasica->id);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=DefinicionProblemaFichaTest`
Expected: FAIL (tabla/modelo/relación no existen).

**Step 3: Crear la migración**

`database/migrations/2026_07_11_000002_create_fichas_informacion_basica_table.php`:

```php
<?php

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas_informacion_basica', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProgramaPresupuestario::class)
                ->constrained()
                ->cascadeOnDelete()
                ->unique();
            $table->text('magnitud')->nullable();
            $table->text('focalizacion')->nullable();
            $table->text('causas_efectos')->nullable();
            $table->text('bienes_servicios')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichas_informacion_basica');
    }
};
```

> Nota: verificar que `foreignIdFor(ProgramaPresupuestario::class)` produce
> `programa_presupuestario_id`. El modelo usa esa columna en el resto del dominio
> (ver `MirNivel`). Si el nombre difiere, usar `->foreignId('programa_presupuestario_id')`.

**Step 4: Crear el modelo**

`app/Models/Mml/FichaInformacionBasica.php`:

```php
<?php

namespace App\Models\Mml;

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FichaInformacionBasica extends Model
{
    protected $table = 'fichas_informacion_basica';

    protected $fillable = [
        'programa_presupuestario_id',
        'magnitud',
        'focalizacion',
        'causas_efectos',
        'bienes_servicios',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }
}
```

**Step 5: Agregar la relación en ProgramaPresupuestario**

En `app/Models/ProgramaPresupuestario.php` (ya importa `HasOne` y otras relaciones hasOne como `poblacion`), agregar (con `use App\Models\Mml\FichaInformacionBasica;` si no está):

```php
public function fichaInformacionBasica(): HasOne
{
    return $this->hasOne(FichaInformacionBasica::class);
}
```

**Step 6: Migrar (dev)**

Run: `./vendor/bin/sail artisan migrate`
Expected: `... create_fichas_informacion_basica_table ... DONE`

**Step 7: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=DefinicionProblemaFichaTest`
Expected: PASS (2 tests).

**Step 8: Commit**

```bash
git add database/migrations/2026_07_11_000002_create_fichas_informacion_basica_table.php app/Models/Mml/FichaInformacionBasica.php app/Models/ProgramaPresupuestario.php tests/Feature/Mml/DefinicionProblemaFichaTest.php
git commit -m "feat(mml): modelo Ficha de Información Básica 1:1 con programa (M02 req 4)"
```

---

## Task 2: Componente — carga, upsert y completitud

**Files:**
- Modify: `app/Livewire/Mml/DefinicionProblema.php`
- Test: `tests/Feature/Mml/DefinicionProblemaFichaTest.php` (añadir casos)

**Step 1: Write the failing test**

Añadir a `DefinicionProblemaFichaTest`:

```php
public function test_guardar_persiste_ficha_y_es_idempotente(): void
{
    Livewire::actingAs($this->user)
        ->test(DefinicionProblema::class, ['programa' => $this->programa])
        ->set('descripcion', 'Alta tasa de deserción escolar en zonas rurales')
        ->set('magnitud', '120,000 alumnos (Estadística 911)')
        ->set('focalizacion', 'Localidades de alta marginación')
        ->call('guardar')
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertSame(1, FichaInformacionBasica::where('programa_presupuestario_id', $this->programa->id)->count());
    $this->assertDatabaseHas('fichas_informacion_basica', [
        'programa_presupuestario_id' => $this->programa->id,
        'magnitud' => '120,000 alumnos (Estadística 911)',
        'focalizacion' => 'Localidades de alta marginación',
    ]);
}

public function test_mount_carga_ficha_existente(): void
{
    FichaInformacionBasica::create([
        'programa_presupuestario_id' => $this->programa->id,
        'bienes_servicios' => 'Becas + materiales',
    ]);

    Livewire::actingAs($this->user)
        ->test(DefinicionProblema::class, ['programa' => $this->programa])
        ->assertSet('bienesServicios', 'Becas + materiales');
}

public function test_completitud_cuenta_las_cinco(): void
{
    // Problema (Q1) + magnitud + focalizacion = 3/5
    Livewire::actingAs($this->user)
        ->test(DefinicionProblema::class, ['programa' => $this->programa])
        ->set('descripcion', 'Alta tasa de deserción escolar en zonas rurales')
        ->set('magnitud', 'X')
        ->set('focalizacion', 'Y')
        ->assertSet('completitud', 3);
}

public function test_ficha_opcional_no_bloquea_guardado(): void
{
    Livewire::actingAs($this->user)
        ->test(DefinicionProblema::class, ['programa' => $this->programa])
        ->set('descripcion', 'Alta tasa de deserción escolar en zonas rurales')
        ->call('guardar')
        ->assertHasNoErrors();
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter="test_guardar_persiste_ficha|test_mount_carga_ficha|test_completitud"`
Expected: FAIL (props/completitud no existen; ficha no se guarda).

**Step 3: Implement en el componente**

En `app/Livewire/Mml/DefinicionProblema.php`:

- Agregar imports: `use App\Models\Mml\FichaInformacionBasica;` y `use Livewire\Attributes\Computed;`.
- Nuevas props tras `$descripcion`:

```php
public string $magnitud = '';

public string $focalizacion = '';

public string $causasEfectos = '';

public string $bienesServicios = '';
```

- En `mount()`, tras cargar el problema central, cargar la ficha:

```php
$ficha = $programa->fichaInformacionBasica;
if ($ficha) {
    $this->magnitud = $ficha->magnitud ?? '';
    $this->focalizacion = $ficha->focalizacion ?? '';
    $this->causasEfectos = $ficha->causas_efectos ?? '';
    $this->bienesServicios = $ficha->bienes_servicios ?? '';
}
```

- En `guardar()`, tras persistir el `ArbolNodo` problema central y antes del flash, ampliar la validación y hacer upsert de la ficha:

```php
$this->validate([
    'magnitud' => 'nullable|string|max:2000',
    'focalizacion' => 'nullable|string|max:2000',
    'causasEfectos' => 'nullable|string|max:2000',
    'bienesServicios' => 'nullable|string|max:2000',
]);

FichaInformacionBasica::updateOrCreate(
    ['programa_presupuestario_id' => $this->programa->id],
    [
        'magnitud' => $this->magnitud ?: null,
        'focalizacion' => $this->focalizacion ?: null,
        'causas_efectos' => $this->causasEfectos ?: null,
        'bienes_servicios' => $this->bienesServicios ?: null,
    ]
);
```

> Mantener la validación existente `descripcion => required|min:20|max:1000` como
> primera llamada a `$this->validate(...)` (no romperla).

- Agregar la propiedad computada de completitud:

```php
#[Computed]
public function completitud(): int
{
    return collect([
        $this->descripcion,
        $this->magnitud,
        $this->focalizacion,
        $this->causasEfectos,
        $this->bienesServicios,
    ])->filter(fn ($v) => trim((string) $v) !== '')->count();
}
```

> Nota: con `#[Computed]`, en los tests se afirma con `->assertSet('completitud', 3)`
> — Livewire expone las propiedades computadas para aserción. Si `assertSet` no
> aplica a computed en esta versión, usar `->assertViewHas` o exponer el valor en el
> render. Alternativa robusta: método público `completitud()` llamado desde el blade
> y afirmado con `->assertSee("{$n}/5")` (ver Task 3).

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=DefinicionProblemaFichaTest`
Expected: PASS (todos). Si `assertSet('completitud', ...)` falla por ser computed,
ajustar a aserción de vista (Task 3 agrega el badge) o exponer prop pública.

**Step 5: Commit**

```bash
git add app/Livewire/Mml/DefinicionProblema.php tests/Feature/Mml/DefinicionProblemaFichaTest.php
git commit -m "feat(mml): capturar Q2–Q5 del diagnóstico + completitud en Etapa 1 (M02 req 4)"
```

---

## Task 3: UI — sección Ficha + badge de completitud

**Files:**
- Modify: `resources/views/livewire/mml/definicion-problema.blade.php`

**Step 1: Agregar la sección tras el `x-forms.section` del Problema Central**

Después del `</x-forms.section>` del Problema Central (~línea 75), agregar:

```blade
<x-forms.section
    title="Ficha de Información Básica — Diagnóstico"
    description="Responde las preguntas estructurantes del diagnóstico (temario SHCP/CONEVAL). Son opcionales pero sustentan la justificación del programa."
>
    <div class="col-span-6 space-y-4">
        <div class="flex justify-end">
            @php $completas = $this->completitud; @endphp
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $completas === 5 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $completas }}/5 preguntas respondidas
            </span>
        </div>

        <div>
            <label for="magnitud" class="block text-sm font-medium text-gray-700">¿De qué magnitud y naturaleza es el problema?</label>
            <p class="text-xs text-gray-400">Cuantifica con datos de fuentes confiables (INEGI, CONEVAL, Estadística 911…). Define la Población Potencial.</p>
            <textarea wire:model="magnitud" id="magnitud" rows="3" maxlength="2000"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
        </div>

        <div>
            <label for="focalizacion" class="block text-sm font-medium text-gray-700">¿A quién afecta y cómo se puede focalizar la atención?</label>
            <p class="text-xs text-gray-400">Grupos más afectados y criterios de priorización. Define la Población Objetivo.</p>
            <textarea wire:model="focalizacion" id="focalizacion" rows="3" maxlength="2000"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
        </div>

        <div>
            <label for="causasEfectos" class="block text-sm font-medium text-gray-700">¿Qué causa el problema y qué efectos tiene si no se atiende?</label>
            <p class="text-xs text-gray-400">Causas verificables (no supuestas) y consecuencias de la inacción. Insumo del Árbol de Problemas.</p>
            <textarea wire:model="causasEfectos" id="causasEfectos" rows="3" maxlength="2000"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
        </div>

        <div>
            <label for="bienesServicios" class="block text-sm font-medium text-gray-700">¿Qué bienes o servicios son necesarios para resolverlo?</label>
            <p class="text-xs text-gray-400">Productos o servicios que la población necesita recibir (no acciones). Anticipa los Componentes.</p>
            <textarea wire:model="bienesServicios" id="bienesServicios" rows="3" maxlength="2000"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
        </div>
    </div>
</x-forms.section>
```

**Step 2: Ajustar el test de completitud a la vista (si aplicaba el caveat del computed)**

Si `assertSet('completitud', 3)` falló en Task 2, sustituir por aserción de vista en `DefinicionProblemaFichaTest`:

```php
->assertSee('3/5 preguntas respondidas');
```

**Step 3: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=DefinicionProblemaFichaTest`
Expected: PASS.

**Step 4: Commit**

```bash
git add resources/views/livewire/mml/definicion-problema.blade.php tests/Feature/Mml/DefinicionProblemaFichaTest.php
git commit -m "feat(mml): sección Ficha de Información Básica + badge completitud en Etapa 1 (M02 req 4)"
```

---

## Task 4: Regresión + verificación

**Step 1: Suite Mml (incluye el test existente de Etapa 1 sin regresión)**

Run: `./vendor/bin/sail artisan test --filter="DefinicionProblema|Mml"`
Expected: PASS (el `DefinicionProblemaTest` original sigue verde).

**Step 2: Pint**

Run: `./vendor/bin/sail bin pint --dirty`
Expected: passed / auto-fix commiteable.

**Step 3: Browser checkpoint**

Con Vite dev corriendo: abrir Etapa 1 de un programa (`/mml/{programa}/etapa/1`), confirmar:
- Sección "Ficha de Información Básica" con las 4 preguntas.
- Badge de completitud actualiza al llenar campos.
- Guardar → recargar → los valores persisten (mount los carga).

**Step 4: Suite completa (baseline)**

Run: `./vendor/bin/sail artisan test`
Expected: baseline + tests nuevos, 0 fallos nuevos.

---

## Notas de cierre

- Post-deploy: `sail artisan migrate` (1 migración privada). Sin BD pública.
- Actualizar `docs/sistema/brechas/README.md`: marcar **M02 req 4** como ✅ RESUELTA + entrada §6.
- PR a `desarrollo`.
