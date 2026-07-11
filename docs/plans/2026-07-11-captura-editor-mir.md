# Completar la captura del editor MIR — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Cerrar 5 brechas de captura del editor MIR (sentido, valor de línea base, código A1.1, frecuencia TRIANUAL, definición del indicador) cableando UI donde el modelo ya soporta el dato + una columna nueva.

**Architecture:** Cambios concentrados en `App\Livewire\Mml\MirEditor` (setters + validadores), su partial Blade `mir-nivel-row.blade.php`, el enum `FrecuenciaMedicion` + `IndicadorReglasService`, un accessor nuevo en `MirNivel`, una migración para `indicadores.definicion`, y los tres exports (`mir.blade.php`, `MirSheet`, `ficha-tecnica.blade.php`). TDD por ítem.

**Tech Stack:** Laravel 12, Livewire 3, PHP 8.2, PostgreSQL, maatwebsite/excel, PHPUnit vía Sail.

**Design doc:** `docs/plans/2026-07-11-captura-editor-mir-design.md`

**Comando de tests:** `./vendor/bin/sail artisan test`
**Rama:** `feat/captura-editor-mir` (ya creada, design doc commiteado)

---

## Convenciones del codebase (leer antes de empezar)

- Setters del editor siguen el patrón de `guardarFormulaTexto`/`guardarLineaBaseAnio`: reciben `int $indicadorId`, resuelven con `$this->indicadorDelPrograma($id)`, hacen no-op silencioso si es `null` (scoping defensivo), validan con `validator([...], [...])->validate()`, y `$indicador->update(...)`.
- Los tests Livewire montan con `Livewire::test(MirEditor::class, ['programa' => $programa])` y usan `->call('metodo', ...args)`. Ver `tests/Feature/Mml/MirEditorScopingTest.php` para el helper `arbol()`.
- `Rule::in(...)` y `ValidationException` ya están importados en `MirEditor`.
- Los enums exponen `::values()` (array de strings).

---

## Task 1: Sentido editable

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php` (método `guardarIndicador`, ~línea 264-269)
- Modify: `resources/views/livewire/mml/partials/mir-nivel-row.blade.php` (~línea 242-294)
- Test: `tests/Feature/Mml/MirEditorCapturaTest.php` (nuevo, se reusa en tasks 1-2 y 6)

**Step 1: Write the failing test**

Crear `tests/Feature/Mml/MirEditorCapturaTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MirEditorCapturaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($this->user);

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa captura',
            'clave' => 'PT-CAP-'.uniqid(),
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);
    }

    private function componenteConIndicador(): Indicador
    {
        $nivel = MirNivel::factory()->create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => 1,
        ]);

        return Indicador::factory()->create(['mir_nivel_id' => $nivel->id]);
    }

    private function editor()
    {
        return Livewire::test(MirEditor::class, ['programa' => $this->programa]);
    }

    public function test_guardar_indicador_persiste_sentido(): void
    {
        $indicador = $this->componenteConIndicador();

        $this->editor()->call('guardarIndicador', $indicador->id, [
            'nombre' => 'Tasa de variación',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => 'descendente',
        ]);

        $this->assertSame(SentidoIndicador::DESCENDENTE, $indicador->fresh()->sentido);
    }

    public function test_guardar_indicador_rechaza_sentido_invalido(): void
    {
        $indicador = $this->componenteConIndicador();

        $this->editor()->call('guardarIndicador', $indicador->id, [
            'nombre' => 'X',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => 'regular',
        ])->assertHasErrors('sentido');
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=MirEditorCapturaTest`
Expected: FAIL (`sentido` no se persiste / no valida — el update ignora la key).

**Step 3: Implement — agregar `sentido` al validador**

En `app/Livewire/Mml/MirEditor.php::guardarIndicador()`, ampliar el validador (añadir `use App\Enums\SentidoIndicador;` al head de imports si no está):

```php
$validated = validator($data, [
    'nombre' => 'required|string|max:255',
    'tipo' => 'required|in:'.implode(',', $reglas['tipos']),
    'dimension' => 'required|in:'.implode(',', $reglas['dimensiones']),
    'frecuencia' => 'required|in:'.implode(',', $reglas['frecuencias']),
    'sentido' => ['required', Rule::in(SentidoIndicador::values())],
])->validate();
```

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=MirEditorCapturaTest`
Expected: PASS (ambos tests).

**Step 5: Wire the Blade (select en edit-mode)**

En `resources/views/livewire/mml/partials/mir-nivel-row.blade.php`, dentro del `x-data` del indicador (~línea 243-248), añadir `sentido` al objeto `ind`:

```js
ind: {
    nombre: @js($indicador->nombre ?? ''),
    tipo: @js($indicador->tipo?->value ?? $reglas['tipo_default']),
    dimension: @js($indicador->dimension?->value ?? $reglas['dimensiones'][0]),
    frecuencia: @js($indicador->frecuencia?->value ?? $reglas['frecuencias'][0]),
    sentido: @js($indicador->sentido?->value ?? 'ascendente'),
},
```

Y agregar el `<select>` de sentido tras el bloque de Frecuencia (~línea 293, dentro del `grid grid-cols-3`; cambiar el contenedor a `grid grid-cols-2` o dejar `grid-cols-3` y que sentido caiga en el segundo renglón — decisión de layout menor, mantener legible):

```blade
{{-- Sentido --}}
<select x-model="ind.sentido" @change="guardar()" class="rounded border-gray-300 text-xs">
    @foreach (\App\Enums\SentidoIndicador::cases() as $s)
        <option value="{{ $s->value }}">{{ $s->label() }}</option>
    @endforeach
</select>
```

**Step 6: Commit**

```bash
git add app/Livewire/Mml/MirEditor.php resources/views/livewire/mml/partials/mir-nivel-row.blade.php tests/Feature/Mml/MirEditorCapturaTest.php
git commit -m "feat(mml): sentido editable en el editor MIR (M05 #15/#12)"
```

---

## Task 2: Valor de línea base

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php` (nuevo método tras `guardarLineaBaseAnio`, ~línea 570)
- Modify: `resources/views/livewire/mml/partials/mir-nivel-row.blade.php` (~línea 331-343)
- Test: `tests/Feature/Mml/MirEditorCapturaTest.php` (añadir casos)

**Step 1: Write the failing test**

Añadir a `MirEditorCapturaTest`:

```php
public function test_guardar_linea_base_persiste_valor(): void
{
    $indicador = $this->componenteConIndicador();

    $this->editor()->call('guardarLineaBase', $indicador->id, '42.5');

    $this->assertEquals(42.5, (float) $indicador->fresh()->linea_base);
}

public function test_guardar_linea_base_vacia_persiste_null(): void
{
    $indicador = $this->componenteConIndicador();
    $indicador->update(['linea_base' => 10]);

    $this->editor()->call('guardarLineaBase', $indicador->id, '');

    $this->assertNull($indicador->fresh()->linea_base);
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=test_guardar_linea_base`
Expected: FAIL (`guardarLineaBase` no existe → error de método Livewire).

**Step 3: Implement el setter**

En `app/Livewire/Mml/MirEditor.php`, tras `guardarLineaBaseAnio()`:

```php
public function guardarLineaBase(int $indicadorId, ?string $valor): void
{
    $indicador = $this->indicadorDelPrograma($indicadorId);

    if ($indicador === null) {
        return;
    }

    $validated = validator(
        ['linea_base' => $valor === '' ? null : $valor],
        ['linea_base' => 'nullable|numeric']
    )->validate();

    $indicador->update($validated);
}
```

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=test_guardar_linea_base`
Expected: PASS.

**Step 5: Wire the Blade (input valor)**

En `mir-nivel-row.blade.php`, tras el bloque "Año de línea base" (~línea 343), añadir:

```blade
{{-- Valor de línea base (M05 #8/#13) --}}
<div class="mt-1 flex items-center gap-2">
    <label class="text-xs font-medium text-gray-500">Valor de línea base</label>
    <input
        type="number"
        step="0.0001"
        value="{{ $indicador->linea_base }}"
        wire:change="guardarLineaBase({{ $indicador->id }}, $event.target.value)"
        class="w-32 rounded border-gray-300 text-xs"
        placeholder="Ej: 42.5"
    />
</div>
```

**Step 6: Commit**

```bash
git add app/Livewire/Mml/MirEditor.php resources/views/livewire/mml/partials/mir-nivel-row.blade.php tests/Feature/Mml/MirEditorCapturaTest.php
git commit -m "feat(mml): captura del valor de línea base en el editor MIR (M05 #8/#13)"
```

---

## Task 3: Accessor de código jerárquico

**Files:**
- Modify: `app/Models/Mml/MirNivel.php` (nuevo método `codigoMir()`)
- Test: `tests/Feature/Mml/MirNivelCodigoTest.php` (nuevo)

**Step 1: Write the failing test**

Crear `tests/Feature/Mml/MirNivelCodigoTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MirNivelCodigoTest extends TestCase
{
    use RefreshDatabase;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'P', 'clave' => 'PT-COD-'.uniqid(),
            'team_id' => $user->currentTeam->id, 'ejercicio_fiscal' => 2026,
        ]);
    }

    private function nivel(TipoNivelMir $tipo, int $orden, ?int $componenteId = null): MirNivel
    {
        return MirNivel::factory()->create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => $tipo->value,
            'orden' => $orden,
            'componente_id' => $componenteId,
        ]);
    }

    public function test_codigo_fin_y_proposito(): void
    {
        $this->assertSame('F', $this->nivel(TipoNivelMir::FIN, 1)->codigoMir());
        $this->assertSame('P', $this->nivel(TipoNivelMir::PROPOSITO, 1)->codigoMir());
    }

    public function test_codigo_componente(): void
    {
        $this->assertSame('C2', $this->nivel(TipoNivelMir::COMPONENTE, 2)->codigoMir());
    }

    public function test_codigo_actividad_usa_orden_del_componente_padre(): void
    {
        $comp = $this->nivel(TipoNivelMir::COMPONENTE, 3);
        $act = $this->nivel(TipoNivelMir::ACTIVIDAD, 1, $comp->id);

        $this->assertSame('A3.1', $act->codigoMir());
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=MirNivelCodigoTest`
Expected: FAIL (método `codigoMir` no existe).

**Step 3: Implement el accessor**

En `app/Models/Mml/MirNivel.php` (ya importa `App\Support\Mml\Trazabilidad` y `App\Enums\TipoNivelMir`), añadir método público:

```php
/**
 * Código jerárquico del nivel en formato del temario MIR:
 * FIN → 'F', PROPÓSITO → 'P', COMPONENTE → 'C{orden}',
 * ACTIVIDAD → 'A{orden_componente}.{orden}'. Reutiliza la resolución del
 * componente padre de Trazabilidad. Cierra M03 req9 / M04 #8.
 */
public function codigoMir(): string
{
    $traza = Trazabilidad::deNivel($this);

    return match ($this->tipo_nivel) {
        TipoNivelMir::FIN => 'F',
        TipoNivelMir::PROPOSITO => 'P',
        TipoNivelMir::COMPONENTE => 'C'.$this->orden,
        TipoNivelMir::ACTIVIDAD => 'A'.($traza->componenteOrden ?? '?').'.'.$this->orden,
    };
}
```

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=MirNivelCodigoTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Models/Mml/MirNivel.php tests/Feature/Mml/MirNivelCodigoTest.php
git commit -m "feat(mml): accessor codigoMir con formato A1.1 del temario (M03 req9)"
```

---

## Task 4: Código en la fila + exports

**Files:**
- Modify: `resources/views/livewire/mml/partials/mir-nivel-row.blade.php` (celda Nivel, ~línea 11-16)
- Modify: `resources/views/exports/pdf/mir.blade.php` (celda Nivel)
- Modify: `resources/views/exports/pdf/ficha-tecnica.blade.php` (Datos Generales)
- Modify: `app/Exports/Excel/MirSheet.php` (collection + headings)
- Test: `tests/Feature/Mml/MirNivelCodigoTest.php` (añadir aserción sobre MirSheet headings)

**Step 1: Write the failing test (Excel headings)**

Añadir a `MirNivelCodigoTest`:

```php
public function test_mir_sheet_incluye_columna_codigo(): void
{
    $sheet = new \App\Exports\Excel\MirSheet($this->programa, 2026);

    $this->assertContains('Código', $sheet->headings());
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=test_mir_sheet_incluye_columna_codigo`
Expected: FAIL (headings no contiene 'Código').

**Step 3: Implement MirSheet**

En `app/Exports/Excel/MirSheet.php`:
- En `collection()`, añadir `'codigo' => $nivel->codigoMir(),` como primer key en AMBOS `$rows->push([...])` (el de indicadores y el de nivel vacío).
- En `headings()`, anteponer `'Código'`:

```php
public function headings(): array
{
    return [
        'Código', 'Nivel', 'Resumen Narrativo', 'Indicador', 'Fórmula',
        'Medios de Verificación', 'Supuestos', 'Meta', 'Línea Base',
        'Tipo', 'Dimensión', 'Frecuencia',
    ];
}
```

**Step 4: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=test_mir_sheet_incluye_columna_codigo`
Expected: PASS.

**Step 5: Wire los Blades**

`mir-nivel-row.blade.php` — dentro del `<span>` del badge de tipo (~línea 13-15), anteponer el código:

```blade
<span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold {{ $tipoEnum?->colorClass() }} cursor-help">
    <span class="font-mono">{{ $nivel->codigoMir() }}</span>
    {{ $tipoEnum?->label() }}
</span>
```

`mir.blade.php` — en la celda Nivel del `@foreach`:

```blade
<td><strong>{{ $nivel->codigoMir() }}</strong> — {{ $nivel->tipo_nivel->label() }}</td>
```

`ficha-tecnica.blade.php` — en "Datos Generales", tras el renglón "Nivel MIR" (~línea 29):

```blade
<tr><th>Código</th><td>{{ $nivel->codigoMir() }}</td></tr>
```

**Step 6: Run full export tests (regresión)**

Run: `./vendor/bin/sail artisan test --filter=MirAprobadaExportTest`
Expected: PASS (sin regresión en exports existentes).

**Step 7: Commit**

```bash
git add resources/views/livewire/mml/partials/mir-nivel-row.blade.php resources/views/exports/pdf/mir.blade.php resources/views/exports/pdf/ficha-tecnica.blade.php app/Exports/Excel/MirSheet.php tests/Feature/Mml/MirNivelCodigoTest.php
git commit -m "feat(mml): mostrar código A1.1 en fila del editor y exports MIR (M04 #8)"
```

---

## Task 5: Frecuencia TRIANUAL

**Files:**
- Modify: `app/Enums/FrecuenciaMedicion.php`
- Modify: `app/Services/Mml/IndicadorReglasService.php` (`frecuenciasPermitidas`)
- Test: `tests/Feature/Mml/FrecuenciaTrianualTest.php` (nuevo)

**Step 1: Write the failing test**

Crear `tests/Feature/Mml/FrecuenciaTrianualTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoNivelMir;
use App\Services\Mml\IndicadorReglasService;
use Tests\TestCase;

class FrecuenciaTrianualTest extends TestCase
{
    public function test_trianual_existe_con_orden_entre_bianual_y_sexenal(): void
    {
        $this->assertSame('trianual', FrecuenciaMedicion::TRIANUAL->value);
        $this->assertSame('Trianual', FrecuenciaMedicion::TRIANUAL->label());
        $this->assertGreaterThan(FrecuenciaMedicion::BIANUAL->orden(), FrecuenciaMedicion::TRIANUAL->orden());
        $this->assertLessThan(FrecuenciaMedicion::SEXENAL->orden(), FrecuenciaMedicion::TRIANUAL->orden());
    }

    public function test_frecuencias_permitidas_ampliadas_por_nivel(): void
    {
        $this->assertContains(FrecuenciaMedicion::TRIANUAL, IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::FIN));
        $this->assertContains(FrecuenciaMedicion::TRIANUAL, IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::PROPOSITO));
        $this->assertContains(FrecuenciaMedicion::ANUAL, IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::COMPONENTE));
        $this->assertContains(FrecuenciaMedicion::SEMESTRAL, IndicadorReglasService::frecuenciasPermitidas(TipoNivelMir::ACTIVIDAD));
    }
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=FrecuenciaTrianualTest`
Expected: FAIL (`TRIANUAL` no existe → error de constante de enum).

**Step 3: Implement el enum**

En `app/Enums/FrecuenciaMedicion.php`:
- Añadir el case tras `BIANUAL`: `case TRIANUAL = 'trianual';`
- En `label()`: `self::TRIANUAL => 'Trianual',`
- En `orden()`, reordenar para insertar trianual entre bianual y sexenal:

```php
return match ($this) {
    self::MENSUAL => 1,
    self::TRIMESTRAL => 2,
    self::SEMESTRAL => 3,
    self::ANUAL => 4,
    self::BIANUAL => 5,
    self::TRIANUAL => 6,
    self::SEXENAL => 7,
};
```

**Step 4: Implement las reglas**

En `app/Services/Mml/IndicadorReglasService::frecuenciasPermitidas()`:

```php
return match ($nivel) {
    TipoNivelMir::FIN => [FrecuenciaMedicion::ANUAL, FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::TRIANUAL, FrecuenciaMedicion::SEXENAL],
    TipoNivelMir::PROPOSITO => [FrecuenciaMedicion::SEMESTRAL, FrecuenciaMedicion::ANUAL, FrecuenciaMedicion::TRIANUAL],
    TipoNivelMir::COMPONENTE => [FrecuenciaMedicion::TRIMESTRAL, FrecuenciaMedicion::SEMESTRAL, FrecuenciaMedicion::ANUAL],
    TipoNivelMir::ACTIVIDAD => [FrecuenciaMedicion::MENSUAL, FrecuenciaMedicion::TRIMESTRAL, FrecuenciaMedicion::SEMESTRAL],
};
```

**Step 5: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=FrecuenciaTrianualTest`
Expected: PASS.

**Step 6: Regresión B7 (MV vs indicador)**

El reordenamiento de `orden()` toca la validación cruzada B7. Correr los tests de hardening/semaforización:

Run: `./vendor/bin/sail artisan test --filter=HardenMirFieldsTest`
Expected: PASS (ningún dato persiste el orden; es `match`, seguro).

**Step 7: Commit**

```bash
git add app/Enums/FrecuenciaMedicion.php app/Services/Mml/IndicadorReglasService.php tests/Feature/Mml/FrecuenciaTrianualTest.php
git commit -m "feat(mml): frecuencia Trianual + frecuencias por nivel alineadas al temario (M05 #17)"
```

---

## Task 6: Definición del indicador

**Files:**
- Create: `database/migrations/2026_07_11_000001_add_definicion_to_indicadores.php`
- Modify: `app/Models/Mml/Indicador.php` (`$fillable`)
- Modify: `app/Livewire/Mml/MirEditor.php` (nuevo setter `guardarDefinicion`)
- Modify: `resources/views/livewire/mml/partials/mir-nivel-row.blade.php` (textarea)
- Modify: `resources/views/exports/pdf/ficha-tecnica.blade.php` (renglón Definición)
- Test: `tests/Feature/Mml/MirEditorCapturaTest.php` (añadir casos)

**Step 1: Write the failing test**

Añadir a `MirEditorCapturaTest`:

```php
public function test_guardar_definicion_persiste(): void
{
    $indicador = $this->componenteConIndicador();

    $this->editor()->call('guardarDefinicion', $indicador->id, 'Mide el avance físico del componente.');

    $this->assertSame('Mide el avance físico del componente.', $indicador->fresh()->definicion);
}

public function test_guardar_definicion_rechaza_mas_de_240_caracteres(): void
{
    $indicador = $this->componenteConIndicador();

    $this->editor()->call('guardarDefinicion', $indicador->id, str_repeat('a', 241))
        ->assertHasErrors('definicion');

    $this->assertNull($indicador->fresh()->definicion);
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter=test_guardar_definicion`
Expected: FAIL (columna/método no existen).

**Step 3: Crear la migración**

`database/migrations/2026_07_11_000001_add_definicion_to_indicadores.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->text('definicion')->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->dropColumn('definicion');
        });
    }
};
```

**Step 4: Migrar (test DB usa RefreshDatabase, pero migrar la dev también)**

Run: `./vendor/bin/sail artisan migrate`
Expected: `2026_07_11_000001_add_definicion_to_indicadores ... DONE`

**Step 5: Modelo + setter**

- En `app/Models/Mml/Indicador.php`, añadir `'definicion'` a `$fillable` (junto a `'nombre'`).
- En `app/Livewire/Mml/MirEditor.php`, nuevo setter (junto a `guardarFormulaTexto`):

```php
public function guardarDefinicion(int $indicadorId, ?string $texto): void
{
    $indicador = $this->indicadorDelPrograma($indicadorId);

    if ($indicador === null) {
        return;
    }

    $validated = validator(
        ['definicion' => $texto === '' ? null : $texto],
        ['definicion' => 'nullable|string|max:240']
    )->validate();

    $indicador->update($validated);
}
```

**Step 6: Run test to verify it passes**

Run: `./vendor/bin/sail artisan test --filter=test_guardar_definicion`
Expected: PASS.

**Step 7: Wire Blade (editor + ficha)**

`mir-nivel-row.blade.php` — tras el input del nombre del indicador (~línea 259), añadir textarea:

```blade
<textarea
    value="{{ $indicador->definicion }}"
    wire:change="guardarDefinicion({{ $indicador->id }}, $event.target.value)"
    rows="2"
    maxlength="240"
    class="w-full rounded border-gray-300 text-xs"
    placeholder="Definición del indicador (máx. 240 caracteres)"
>{{ $indicador->definicion }}</textarea>
```

`ficha-tecnica.blade.php` — en "Datos Generales", tras "Nombre del Indicador" (~línea 31):

```blade
<tr><th>Definición</th><td>{{ $indicador->definicion }}</td></tr>
```

**Step 8: Commit**

```bash
git add database/migrations/2026_07_11_000001_add_definicion_to_indicadores.php app/Models/Mml/Indicador.php app/Livewire/Mml/MirEditor.php resources/views/livewire/mml/partials/mir-nivel-row.blade.php resources/views/exports/pdf/ficha-tecnica.blade.php tests/Feature/Mml/MirEditorCapturaTest.php
git commit -m "feat(mml): campo Definición del indicador con límite 240ch (M05 #11)"
```

---

## Task 7: Suite completa + verificación

**Step 1: Correr la suite MML + exports completa**

Run: `./vendor/bin/sail artisan test --filter=Mml`
Expected: PASS, sin regresiones.

Run: `./vendor/bin/sail artisan test --filter=Export`
Expected: PASS.

**Step 2: Suite completa (baseline)**

Run: `./vendor/bin/sail artisan test`
Expected: baseline previo + los tests nuevos, 0 fallos nuevos.

**Step 3: Verificación en browser (checkpoint)**

Según [feedback-browser-verification-checkpoints]: abrir el editor MIR (Etapa 7) de un programa con árbol completo y confirmar visualmente:
- Select de Sentido presente y persiste.
- Input de Valor de línea base presente y persiste.
- Badge de código (F/P/C1/A1.1) en cada fila.
- Select de Frecuencia muestra Trianual donde aplica.
- Textarea de Definición con contador/límite 240.
- Regresión: guardar meta con justificación sigue funcionando; exportar MIR PDF/Excel y ficha técnica generan con las columnas nuevas.

**Step 4: Pint (formato)**

Run: `./vendor/bin/sail bin pint --dirty`
Expected: sin cambios pendientes o auto-fix aplicado; commitear si aplica.

---

## Notas de cierre

- No hacer `git push` ni PR hasta que el usuario lo pida (convención del repo).
- El fix de `$mv->descripcion`/`$var->descripcion` en exports queda **fuera de alcance** (bug preexistente, documentado en el design doc).
- Post-deploy: `sail artisan migrate` (1 migración privada nueva). No toca BD pública.
