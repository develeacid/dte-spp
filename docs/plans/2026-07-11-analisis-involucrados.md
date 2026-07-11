# Análisis de Involucrados (M02 req 5) — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Capturar el Análisis de Involucrados (categorías de actores + interés/rol + riesgo) como CRUD embebido en la Etapa 1.

**Architecture:** Tabla `involucrados` (1:N con programa) + enum `InvolucradoCategoria` + modelo `Involucrado`. El componente `DefinicionProblema` gana métodos CRUD (agregar/guardar/eliminar) con scoping defensivo (patrón del CRUD de supuestos en `MirEditor`); persistencia inmediata por fila. Sección nueva en el blade de Etapa 1.

**Tech Stack:** Laravel 12, Livewire 3, PostgreSQL, Sail/PHPUnit.

**Design doc:** `docs/plans/2026-07-11-analisis-involucrados-design.md`
**Rama:** `feat/analisis-involucrados` (creada, design doc commiteado)
**Comando de tests:** `./vendor/bin/sail artisan test --filter=<X>`

---

## Task 1: Enum + migración + modelo + relación

**Files:**
- Create: `app/Enums/InvolucradoCategoria.php`
- Create: `database/migrations/2026_07_11_000003_create_involucrados_table.php`
- Create: `app/Models/Mml/Involucrado.php`
- Modify: `app/Models/ProgramaPresupuestario.php` (relación `involucrados`)
- Test: `tests/Feature/Mml/DefinicionProblemaInvolucradosTest.php` (nuevo)

**Step 1: Write the failing test**

Crear `tests/Feature/Mml/DefinicionProblemaInvolucradosTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\InvolucradoCategoria;
use App\Livewire\Mml\DefinicionProblema;
use App\Models\Mml\Involucrado;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DefinicionProblemaInvolucradosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Involucrados',
            'clave' => 'PT-INV-'.uniqid(),
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    private function otroPrograma(): ProgramaPresupuestario
    {
        $otro = User::factory()->withPersonalTeam()->create();

        return ProgramaPresupuestario::create([
            'nombre' => 'Otro', 'clave' => 'PT-OTRO-'.uniqid(),
            'team_id' => $otro->currentTeam->id,
        ]);
    }

    private function editor()
    {
        return Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa]);
    }

    public function test_relacion_involucrados_es_hasmany(): void
    {
        $this->assertInstanceOf(HasMany::class, $this->programa->involucrados());
    }

    public function test_categoria_castea_a_enum(): void
    {
        $inv = Involucrado::create([
            'programa_presupuestario_id' => $this->programa->id,
            'categoria' => InvolucradoCategoria::OPOSITOR->value,
            'nombre' => 'Actor X',
            'orden' => 1,
        ]);

        $this->assertSame(InvolucradoCategoria::OPOSITOR, $inv->fresh()->categoria);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=DefinicionProblemaInvolucradosTest`
Expected: FAIL (enum/tabla/modelo/relación no existen).

**Step 3: Crear el enum**

`app/Enums/InvolucradoCategoria.php`:

```php
<?php

namespace App\Enums;

enum InvolucradoCategoria: string
{
    case BENEFICIARIO_DIRECTO = 'beneficiario_directo';
    case BENEFICIARIO_INDIRECTO = 'beneficiario_indirecto';
    case EJECUTOR = 'ejecutor';
    case ALIADO = 'aliado';
    case NEUTRAL = 'neutral';
    case OPOSITOR = 'opositor';

    public function label(): string
    {
        return match ($this) {
            self::BENEFICIARIO_DIRECTO => 'Beneficiario directo',
            self::BENEFICIARIO_INDIRECTO => 'Beneficiario indirecto',
            self::EJECUTOR => 'Ejecutor',
            self::ALIADO => 'Aliado',
            self::NEUTRAL => 'Neutral',
            self::OPOSITOR => 'Opositor',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

**Step 4: Crear la migración**

`database/migrations/2026_07_11_000003_create_involucrados_table.php`:

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
        Schema::create('involucrados', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProgramaPresupuestario::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('categoria');
            $table->string('nombre');
            $table->text('interes_o_rol')->nullable();
            $table->text('riesgo_asociado')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('involucrados');
    }
};
```

**Step 5: Crear el modelo**

`app/Models/Mml/Involucrado.php`:

```php
<?php

namespace App\Models\Mml;

use App\Enums\InvolucradoCategoria;
use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Involucrado extends Model
{
    protected $table = 'involucrados';

    protected $fillable = [
        'programa_presupuestario_id',
        'categoria',
        'nombre',
        'interes_o_rol',
        'riesgo_asociado',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'categoria' => InvolucradoCategoria::class,
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
    }
}
```

**Step 6: Relación en ProgramaPresupuestario**

En `app/Models/ProgramaPresupuestario.php`, junto a `fichaInformacionBasica()`, agregar:

```php
public function involucrados(): HasMany
{
    return $this->hasMany(Mml\Involucrado::class)->orderBy('orden');
}
```

> `HasMany` ya está importado en el modelo.

**Step 7: Migrar (dev)**

Run: `./vendor/bin/sail artisan migrate`
Expected: `... create_involucrados_table ... DONE`

**Step 8: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=DefinicionProblemaInvolucradosTest`
Expected: PASS (2 tests).

**Step 9: Commit**

```bash
git add app/Enums/InvolucradoCategoria.php database/migrations/2026_07_11_000003_create_involucrados_table.php app/Models/Mml/Involucrado.php app/Models/ProgramaPresupuestario.php tests/Feature/Mml/DefinicionProblemaInvolucradosTest.php
git commit -m "feat(mml): modelo Involucrado + enum de categorías (M02 req 5)"
```

---

## Task 2: CRUD en el componente

**Files:**
- Modify: `app/Livewire/Mml/DefinicionProblema.php`
- Test: `tests/Feature/Mml/DefinicionProblemaInvolucradosTest.php` (añadir casos)

**Step 1: Write the failing test**

Añadir a `DefinicionProblemaInvolucradosTest`:

```php
public function test_agregar_involucrado_crea_fila(): void
{
    $this->editor()->call('agregarInvolucrado');

    $this->assertSame(1, $this->programa->involucrados()->count());
}

public function test_guardar_involucrado_persiste(): void
{
    $inv = Involucrado::create([
        'programa_presupuestario_id' => $this->programa->id,
        'categoria' => InvolucradoCategoria::EJECUTOR->value,
        'nombre' => '', 'orden' => 1,
    ]);

    $this->editor()->call('guardarInvolucrado', $inv->id, [
        'categoria' => 'aliado',
        'nombre' => 'Universidad Estatal',
        'interes_o_rol' => 'Capacitación técnica',
        'riesgo_asociado' => 'Cambio de administración',
    ]);

    $inv->refresh();
    $this->assertSame(InvolucradoCategoria::ALIADO, $inv->categoria);
    $this->assertSame('Universidad Estatal', $inv->nombre);
    $this->assertSame('Capacitación técnica', $inv->interes_o_rol);
}

public function test_guardar_involucrado_rechaza_categoria_invalida(): void
{
    $inv = Involucrado::create([
        'programa_presupuestario_id' => $this->programa->id,
        'categoria' => InvolucradoCategoria::EJECUTOR->value,
        'nombre' => 'X', 'orden' => 1,
    ]);

    $this->editor()->call('guardarInvolucrado', $inv->id, [
        'categoria' => 'inexistente',
        'nombre' => 'Y',
    ])->assertHasErrors('categoria');
}

public function test_guardar_involucrado_de_otro_programa_es_noop(): void
{
    $ajeno = Involucrado::create([
        'programa_presupuestario_id' => $this->otroPrograma()->id,
        'categoria' => InvolucradoCategoria::EJECUTOR->value,
        'nombre' => 'Original', 'orden' => 1,
    ]);

    $this->editor()->call('guardarInvolucrado', $ajeno->id, [
        'categoria' => 'opositor', 'nombre' => 'HACKEADO',
    ]);

    $this->assertSame('Original', $ajeno->fresh()->nombre);
}

public function test_eliminar_involucrado(): void
{
    $inv = Involucrado::create([
        'programa_presupuestario_id' => $this->programa->id,
        'categoria' => InvolucradoCategoria::EJECUTOR->value,
        'nombre' => 'X', 'orden' => 1,
    ]);

    $this->editor()->call('eliminarInvolucrado', $inv->id);

    $this->assertNull(Involucrado::find($inv->id));
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=DefinicionProblemaInvolucradosTest`
Expected: FAIL (métodos no existen).

**Step 3: Implement en el componente**

En `app/Livewire/Mml/DefinicionProblema.php`:

- Imports: `use App\Enums\InvolucradoCategoria;` y `use App\Models\Mml\Involucrado;`.
- Métodos CRUD (patrón del CRUD de supuestos en `MirEditor`):

```php
private function involucradoDelPrograma(int $id): ?Involucrado
{
    return Involucrado::where('programa_presupuestario_id', $this->programa->id)->find($id);
}

public function agregarInvolucrado(): void
{
    $maxOrden = $this->programa->involucrados()->max('orden') ?? 0;

    Involucrado::create([
        'programa_presupuestario_id' => $this->programa->id,
        'categoria' => InvolucradoCategoria::BENEFICIARIO_DIRECTO->value,
        'nombre' => '',
        'orden' => $maxOrden + 1,
    ]);
}

public function guardarInvolucrado(int $id, array $data): void
{
    $involucrado = $this->involucradoDelPrograma($id);

    if ($involucrado === null) {
        return;
    }

    $validated = validator($data, [
        'categoria' => 'required|in:'.implode(',', InvolucradoCategoria::values()),
        'nombre' => 'required|string|max:255',
        'interes_o_rol' => 'nullable|string|max:1000',
        'riesgo_asociado' => 'nullable|string|max:1000',
    ])->validate();

    $involucrado->update($validated);
}

public function eliminarInvolucrado(int $id): void
{
    $involucrado = $this->involucradoDelPrograma($id);

    if ($involucrado === null) {
        return;
    }

    $involucrado->delete();
}
```

> Nota: `guardarInvolucrado` valida con `validator(...)->validate()` (como
> `MirEditor::guardarIndicador`), que lanza `ValidationException` y Livewire la
> expone vía `assertHasErrors('categoria')`.

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=DefinicionProblemaInvolucradosTest`
Expected: PASS (todos).

**Step 5: Commit**

```bash
git add app/Livewire/Mml/DefinicionProblema.php tests/Feature/Mml/DefinicionProblemaInvolucradosTest.php
git commit -m "feat(mml): CRUD de involucrados en Etapa 1 con scoping (M02 req 5)"
```

---

## Task 3: UI — sección Análisis de Involucrados

**Files:**
- Modify: `resources/views/livewire/mml/definicion-problema.blade.php`

**Step 1: Insertar la sección tras la Ficha (después de `</x-forms.section>` de la Ficha, ~línea 117)**

```blade
<x-forms.section
    title="Análisis de Involucrados"
    description="Mapea los actores relevantes del programa. Los riesgos que identifiques aquí alimentan los Supuestos de la MIR."
>
    <div class="col-span-6 space-y-3">
        @forelse ($programa->involucrados as $inv)
            <div class="rounded-lg border border-gray-200 p-3" wire:key="inv-{{ $inv->id }}"
                x-data="{
                    d: {
                        categoria: @js($inv->categoria?->value ?? 'beneficiario_directo'),
                        nombre: @js($inv->nombre ?? ''),
                        interes_o_rol: @js($inv->interes_o_rol ?? ''),
                        riesgo_asociado: @js($inv->riesgo_asociado ?? ''),
                    },
                    guardar() { $wire.guardarInvolucrado({{ $inv->id }}, { ...this.d }); }
                }">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <select x-model="d.categoria" @change="guardar()" class="rounded-lg border-gray-300 text-sm">
                        @foreach (\App\Enums\InvolucradoCategoria::cases() as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                    <input type="text" x-model="d.nombre" @change="guardar()" placeholder="Nombre del actor"
                        class="rounded-lg border-gray-300 text-sm" />
                </div>
                <textarea x-model="d.interes_o_rol" @change="guardar()" rows="2" placeholder="Interés o rol en el programa"
                    class="mt-2 block w-full rounded-lg border-gray-300 text-sm"></textarea>
                <textarea x-model="d.riesgo_asociado" @change="guardar()" rows="2" placeholder="Riesgo asociado (opcional) — alimenta un Supuesto"
                    class="mt-2 block w-full rounded-lg border-gray-300 text-sm"></textarea>
                <div class="mt-2 flex justify-end">
                    <button wire:click="eliminarInvolucrado({{ $inv->id }})" class="text-xs text-red-500 hover:text-red-700">Eliminar</button>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">Sin involucrados registrados.</p>
        @endforelse

        <button wire:click="agregarInvolucrado" type="button" class="text-sm text-indigo-600 hover:text-indigo-800">+ Involucrado</button>
    </div>
</x-forms.section>
```

**Step 2: Verificación rápida**

Run: `grep -n "Análisis de Involucrados" resources/views/livewire/mml/definicion-problema.blade.php`
Expected: aparece la sección.

**Step 3: Commit**

```bash
git add resources/views/livewire/mml/definicion-problema.blade.php
git commit -m "feat(mml): sección Análisis de Involucrados en Etapa 1 (M02 req 5)"
```

---

## Task 4: Regresión + verificación

**Step 1: Suite Etapa 1 + Mml**

Run: `./vendor/bin/sail artisan test --filter="DefinicionProblema|Mml"`
Expected: PASS (Etapa 1 original + ficha + involucrados, sin regresión).

**Step 2: Pint**

Run: `./vendor/bin/sail bin pint --dirty`
Expected: passed / auto-fix commiteable.

**Step 3: Browser checkpoint**

Con Vite dev corriendo: abrir Etapa 1 (`/mml/{programa}/etapa/1`), confirmar:
- Sección "Análisis de Involucrados" con botón "+ Involucrado".
- Agregar → aparece fila; llenar categoría/nombre/interés/riesgo → guardar (recargar y persiste).
- Eliminar quita la fila.

**Step 4: Suite completa (baseline)**

Run: `./vendor/bin/sail artisan test`
Expected: baseline + tests nuevos, 0 fallos nuevos.

---

## Notas de cierre

- Post-deploy: `sail artisan migrate` (1 migración privada). Sin BD pública.
- Actualizar `docs/sistema/brechas/README.md`: marcar **M02 req 5** como ✅ RESUELTA + entrada §6.
- PR a `desarrollo`.
