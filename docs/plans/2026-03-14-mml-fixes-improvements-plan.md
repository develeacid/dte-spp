# MML Fixes & Improvements Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix critical bugs in the MML wizard (effects added as causes, error 500 on finalize), improve AI assistant capabilities (indirect causes, auto-generated tree, formula generation), redesign MIR editor with read/edit modes, and build a hierarchical indicator dashboard.

**Architecture:** Three-phase approach ordered by criticality. Phase 1 fixes blocking bugs (2 confirmed: wrong node type in blade, missing method name in service call). Phase 2 extends AI functionality in existing Livewire components and restructures MIR editor into read/edit modes. Phase 3 builds new dashboard views with collapsible hierarchy and chart integration.

**Tech Stack:** Laravel 12, Livewire 3, Alpine.js, Tailwind CSS, PostgreSQL with pgvector, LLM API via LlmService, Chart.js for dashboard graphs.

**Test baseline:** 426 passed, 7 skipped. Run `./vendor/bin/sail artisan test` after each task.

---

## Phase 1 — Critical Fixes

### Task 1: Fix effects being added as causes (Step 2)

**Root Cause:** `arbol-problema-builder.blade.php` line 230 hardcodes `'causa_directa'` as the third argument to `agregarSugerencia()`, regardless of whether the user clicked "Sugerir causas" or "Sugerir efectos". The component has no property tracking which type of suggestion was last generated.

**Files:**
- Modify: `app/Livewire/Mml/ArbolProblemaBuilder.php:19-34` (add property), `:119-153` (set property in sugerirConIa)
- Modify: `resources/views/livewire/mml/arbol-problema-builder.blade.php:229-235` (use dynamic type)
- Test: `tests/Feature/Mml/ArbolProblemaBuilderTest.php` (add 2 tests)

**Step 1: Write the failing tests**

Add to `tests/Feature/Mml/ArbolProblemaBuilderTest.php`:

```php
/** @test */
public function sugerir_efectos_agrega_como_efecto_directo()
{
    $this->mock(\App\Contracts\LlmServiceInterface::class, function ($mock) {
        $mock->shouldReceive('isDegraded')->andReturn(false);
        $mock->shouldReceive('suggest')->once()->andReturn("1. Efecto sugerido por IA");
    });

    Livewire::actingAs($this->user)
        ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
        ->call('sugerirConIa', 'efecto')
        ->call('agregarSugerencia', 'Efecto sugerido por IA', $this->problemaCentral->id, 'efecto_directo');

    $this->assertDatabaseHas('arbol_nodos', [
        'arbol_id' => $this->arbol->id,
        'tipo_nodo' => 'efecto_directo',
        'descripcion' => 'Efecto sugerido por IA',
    ]);
}

/** @test */
public function sugerir_causas_agrega_como_causa_directa()
{
    $this->mock(\App\Contracts\LlmServiceInterface::class, function ($mock) {
        $mock->shouldReceive('isDegraded')->andReturn(false);
        $mock->shouldReceive('suggest')->once()->andReturn("1. Causa sugerida por IA");
    });

    Livewire::actingAs($this->user)
        ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
        ->call('sugerirConIa', 'causa')
        ->call('agregarSugerencia', 'Causa sugerida por IA', $this->problemaCentral->id, 'causa_directa');

    $this->assertDatabaseHas('arbol_nodos', [
        'arbol_id' => $this->arbol->id,
        'tipo_nodo' => 'causa_directa',
        'descripcion' => 'Causa sugerida por IA',
    ]);
}
```

**Step 2: Run tests to verify they fail**

Run: `./vendor/bin/sail artisan test --filter="ArbolProblemaBuilderTest"`
Expected: Tests may pass on the PHP side since `agregarSugerencia` already accepts the type parameter — but the real bug is in the Blade template. The PHP tests confirm the backend works correctly; the fix is ensuring the Blade passes the right type.

**Step 3: Add `$tipoSugerencia` property to component**

In `app/Livewire/Mml/ArbolProblemaBuilder.php`:

Add property after line 34:
```php
public string $tipoSugerencia = 'causa_directa';
```

In `sugerirConIa()` method, after line 122 (`$this->sugerenciasIa = [];`), add:
```php
$this->tipoSugerencia = match($tipoSugerencia) {
    'causa' => 'causa_directa',
    'efecto' => 'efecto_directo',
    default => $tipoSugerencia,
};
```

Note: The blade calls `sugerirConIa('causa')` and `sugerirConIa('efecto')` (short names), but `agregarSugerencia()` expects full enum values like `'causa_directa'` and `'efecto_directo'`. The match maps between them.

**Step 4: Fix the Blade template**

In `resources/views/livewire/mml/arbol-problema-builder.blade.php`, replace line 230:

```blade
{{-- OLD (hardcoded): --}}
wire:click="agregarSugerencia('{{ addslashes($sugerencia) }}', {{ $problemaCentral->id }}, 'causa_directa')"

{{-- NEW (dynamic): --}}
wire:click="agregarSugerencia('{{ addslashes($sugerencia) }}', {{ $problemaCentral->id }}, '{{ $tipoSugerencia }}')"
```

**Step 5: Run all tests**

Run: `./vendor/bin/sail artisan test --filter="ArbolProblemaBuilderTest"`
Expected: All tests PASS including the 2 new ones.

**Step 6: Commit**

```bash
git add app/Livewire/Mml/ArbolProblemaBuilder.php \
       resources/views/livewire/mml/arbol-problema-builder.blade.php \
       tests/Feature/Mml/ArbolProblemaBuilderTest.php
git commit -m "fix(mml): use dynamic node type for AI suggestions in problem tree

The 'Add to tree' button was hardcoding 'causa_directa' for all AI suggestions,
causing effect suggestions to be added as causes instead of effects.

Resolves DTE-XX"
```

---

### Task 2: Fix error 500 on "Finalizar Planeación y Crear MIR" (Step 6)

**Root Cause:** `AlineacionEstrategica.php` line 154 calls `->prellenarDesdeEAP($this->programa)` but `MirPrellenadoService` only has a method named `->prellenar()`. This causes a `BadMethodCallException` (error 500).

**Files:**
- Modify: `app/Livewire/Mml/AlineacionEstrategica.php:142-159` (fix method name + add defensive validation)
- Test: `tests/Feature/Mml/AlineacionEstrategicaTest.php` (add finalization test)

**Step 1: Write the failing test**

Add to `tests/Feature/Mml/AlineacionEstrategicaTest.php`:

```php
/** @test */
public function finalizar_planeacion_creates_mir_and_redirects()
{
    // Setup: need poblacion, arbol de objetivos, alternativa seleccionada, and PED alignment
    $this->programa->poblacion()->create([
        'referencia_cantidad' => 10000,
        'potencial_cantidad' => 5000,
        'objetivo_cantidad' => 2000,
        'unidad_medida' => 'personas',
    ]);

    // Create objectives tree with required nodes
    $arbolObj = \App\Models\Mml\Arbol::create([
        'programa_presupuestario_id' => $this->programa->id,
        'tipo' => 'objetivos',
    ]);
    $objetivoCentral = \App\Models\Mml\ArbolNodo::create([
        'arbol_id' => $arbolObj->id,
        'tipo_nodo' => 'objetivo_central',
        'descripcion' => 'Objetivo central de prueba',
        'orden' => 1,
    ]);
    $finDirecto = \App\Models\Mml\ArbolNodo::create([
        'arbol_id' => $arbolObj->id,
        'parent_id' => $objetivoCentral->id,
        'tipo_nodo' => 'fin_directo',
        'descripcion' => 'Fin directo de prueba',
        'orden' => 1,
    ]);
    $medioDirecto = \App\Models\Mml\ArbolNodo::create([
        'arbol_id' => $arbolObj->id,
        'parent_id' => $objetivoCentral->id,
        'tipo_nodo' => 'medio_directo',
        'descripcion' => 'Medio directo de prueba',
        'orden' => 1,
    ]);

    // Create selected alternative
    $alternativa = \App\Models\Mml\Alternativa::create([
        'programa_presupuestario_id' => $this->programa->id,
        'nombre' => 'Alternativa 1',
        'seleccionada' => true,
    ]);
    $alternativa->nodos()->attach($medioDirecto->id);

    // Create PED chain and alignment
    $chain = $this->createPedChain();
    $fin = $this->programa->mirNiveles()->create([
        'tipo_nivel' => 'fin',
        'resumen_narrativo' => 'FIN placeholder',
        'orden' => 1,
        'ped_objetivo_estrategico_id' => $chain['objetivo']->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
        ->set('objetivoEstrategicoId', $chain['objetivo']->id)
        ->call('finalizarPlaneacion')
        ->assertRedirect(route('mml.mir', $this->programa));

    $this->programa->refresh();
    $this->assertNotNull($this->programa->planeacion_completada_at);
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter="finalizar_planeacion_creates_mir"`
Expected: FAIL with `BadMethodCallException: Method prellenarDesdeEAP does not exist`

**Step 3: Fix the method call**

In `app/Livewire/Mml/AlineacionEstrategica.php`, lines 154-155, replace:

```php
// OLD:
app(\App\Services\Mml\MirPrellenadoService::class)
    ->prellenarDesdeEAP($this->programa);

// NEW:
app(\App\Services\Mml\MirPrellenadoService::class)
    ->prellenar($this->programa);
```

**Step 4: Add defensive validation before finalization**

In `finalizarPlaneacion()`, before the `$this->programa->update(...)` call (around line 148), add validation:

```php
// Validate all prerequisites
$arbolObjetivos = $this->programa->arboles()->where('tipo', 'objetivos')->first();
if (!$arbolObjetivos || $arbolObjetivos->nodos()->count() === 0) {
    $this->addError('finalizacion', 'El árbol de objetivos debe estar completo antes de finalizar.');
    return;
}

$alternativa = $this->programa->alternativas()->where('seleccionada', true)->first();
if (!$alternativa) {
    $this->addError('finalizacion', 'Debe seleccionar una alternativa antes de finalizar.');
    return;
}

if (!$this->programa->poblacion) {
    $this->addError('finalizacion', 'Debe completar el embudo de poblaciones antes de finalizar.');
    return;
}
```

**Step 5: Run all tests**

Run: `./vendor/bin/sail artisan test --filter="AlineacionEstrategicaTest"`
Expected: All tests PASS.

**Step 6: Commit**

```bash
git add app/Livewire/Mml/AlineacionEstrategica.php \
       tests/Feature/Mml/AlineacionEstrategicaTest.php
git commit -m "fix(mml): fix method name in finalizarPlaneacion and add validation

Called prellenarDesdeEAP() which doesn't exist — corrected to prellenar().
Added defensive checks for objetivos tree, selected alternative, and poblacion.

Resolves DTE-XX"
```

---

### Task 3: Fix embudo layout with large numbers (Step 5)

**Root Cause:** The funnel visualization uses fixed-width containers. When numbers exceed 5 digits (e.g., 1,234,567), the text overflows the container boundaries. The `fmt()` function works correctly but the CSS doesn't accommodate long formatted strings.

**Files:**
- Modify: `resources/views/livewire/mml/embudo-poblaciones.blade.php:57,71,85` (responsive text + container)
- Test: Manual visual test (no automated test needed for CSS)

**Step 1: Fix number display with responsive text sizing**

In `resources/views/livewire/mml/embudo-poblaciones.blade.php`:

Replace the three number display lines (57, 71, 85) that use `text-xl` with responsive sizing:

```blade
{{-- Line 57 (Referencia) --}}
<p class="text-base sm:text-xl font-bold text-blue-800 truncate" x-text="fmt(ref) + ' ' + unidad"></p>

{{-- Line 71 (Potencial) --}}
<p class="text-base sm:text-xl font-bold text-amber-800 truncate" x-text="fmt(pot) + ' ' + unidad"></p>

{{-- Line 85 (Objetivo) --}}
<p class="text-base sm:text-xl font-bold text-green-800 truncate" x-text="fmt(obj) + ' ' + unidad"></p>
```

Also update the `fmt()` function in the Alpine data object (line 42) to abbreviate very large numbers:

```javascript
fmt(n) {
    if (!n) return '0';
    const num = Number(n);
    if (num >= 1_000_000) return (num / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (num >= 100_000) return (num / 1_000).toFixed(0) + 'K';
    return num.toLocaleString('es-MX');
}
```

Also ensure the funnel containers use `min-w-0` and `overflow-hidden` on the parent flex containers to prevent overflow. Search for the funnel shape divs and add `min-w-0` where needed.

**Step 2: Run all tests**

Run: `./vendor/bin/sail artisan test --filter="EmbudoPoblacionesTest"`
Expected: All existing tests PASS.

**Step 3: Commit**

```bash
git add resources/views/livewire/mml/embudo-poblaciones.blade.php
git commit -m "fix(mml): handle large numbers in population funnel display

Add responsive text sizing, number abbreviation for 100K+/1M+, and overflow
protection to prevent layout breaking with 5+ digit numbers.

Resolves DTE-XX"
```

---

## Phase 2 — AI Functional Improvements

### Task 4: Add AI suggestions for indirect causes (Step 2)

**Context:** Currently the AI assistant only suggests `causa_directa` and `efecto_directo`. Administrators request indirect cause suggestions that are contextual to a specific direct cause.

**Files:**
- Modify: `app/Livewire/Mml/ArbolProblemaBuilder.php` (extend sugerirConIa, add sugerirCausasIndirectas)
- Modify: `resources/views/livewire/mml/arbol-problema-builder.blade.php` (add button per causa_directa node)
- Modify: `resources/views/livewire/mml/partials/nodo-card.blade.php` (if buttons are in partial)
- Test: `tests/Feature/Mml/ArbolProblemaBuilderTest.php`

**Step 1: Write the failing test**

```php
/** @test */
public function sugerir_causas_indirectas_genera_sugerencias_para_causa_directa()
{
    $causaDirecta = ArbolNodo::create([
        'arbol_id' => $this->arbol->id,
        'parent_id' => $this->problemaCentral->id,
        'tipo_nodo' => 'causa_directa',
        'descripcion' => 'Falta de capacitación',
        'orden' => 1,
    ]);

    $this->mock(\App\Contracts\LlmServiceInterface::class, function ($mock) {
        $mock->shouldReceive('isDegraded')->andReturn(false);
        $mock->shouldReceive('suggest')->once()->andReturn(
            "1. Presupuesto insuficiente para formación\n2. Ausencia de programas de desarrollo profesional"
        );
    });

    Livewire::actingAs($this->user)
        ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
        ->call('sugerirCausasIndirectas', $causaDirecta->id)
        ->assertSet('sugerenciasIa', fn ($val) => count($val) === 2)
        ->assertSet('tipoSugerencia', 'causa_indirecta')
        ->assertSet('parentIdSugerencia', $causaDirecta->id);
}
```

**Step 2: Run test to verify it fails**

Run: `./vendor/bin/sail artisan test --filter="sugerir_causas_indirectas"`
Expected: FAIL — method `sugerirCausasIndirectas` doesn't exist.

**Step 3: Implement the method**

In `app/Livewire/Mml/ArbolProblemaBuilder.php`:

Add property:
```php
public ?int $parentIdSugerencia = null;
```

Add method:
```php
public function sugerirCausasIndirectas(int $causaDirectaId): void
{
    try {
        $this->sugiriendoConIa = true;
        $this->sugerenciasIa = [];
        $this->tipoSugerencia = 'causa_indirecta';
        $this->parentIdSugerencia = $causaDirectaId;

        $llm = app(LlmServiceInterface::class);
        $arbol = Arbol::findOrFail($this->arbolId);

        $problemaCentral = $arbol->nodos()->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)->firstOrFail();
        $causaDirecta = ArbolNodo::findOrFail($causaDirectaId);
        $existentes = $causaDirecta->children()->pluck('descripcion')->implode('; ');

        $prompt = "Dado el problema central: \"{$problemaCentral->descripcion}\" "
            . "y la causa directa: \"{$causaDirecta->descripcion}\", "
            . "sugiere 3 causas indirectas (causas raíz que originan esta causa directa). "
            . ($existentes ? "Ya existen estas causas indirectas: {$existentes}. No las repitas. " : '')
            . "Responde solo con la lista numerada, sin explicaciones.";

        $result = $llm->suggest($prompt);
        $this->sugerenciasIa = collect(explode("\n", $result))
            ->map(fn ($l) => trim(preg_replace('/^\d+[\.\)\-]\s*/', '', trim($l))))
            ->filter(fn ($l) => strlen($l) > 5)
            ->values()
            ->toArray();
    } catch (\Throwable $e) {
        report($e);
        session()->flash('error', 'No se pudieron generar sugerencias.');
    } finally {
        $this->sugiriendoConIa = false;
    }
}
```

**Step 4: Update agregarSugerencia to use parentIdSugerencia**

Modify `agregarSugerencia()` so when `$tipoSugerencia === 'causa_indirecta'`, the parent is `$this->parentIdSugerencia` instead of `$problemaCentralId`:

```php
public function agregarSugerencia(string $texto, int $problemaCentralId, string $tipo): void
{
    $texto = trim(preg_replace('/^\d+[\.\)\-]\s*/', '', $texto));

    $parentId = ($tipo === 'causa_indirecta' && $this->parentIdSugerencia)
        ? $this->parentIdSugerencia
        : $problemaCentralId;

    $maxOrden = ArbolNodo::where('arbol_id', $this->arbolId)
        ->where('parent_id', $parentId)
        ->where('tipo_nodo', $tipo)
        ->max('orden') ?? 0;

    ArbolNodo::create([
        'arbol_id' => $this->arbolId,
        'parent_id' => $parentId,
        'tipo_nodo' => $tipo,
        'descripcion' => $texto,
        'orden' => $maxOrden + 1,
    ]);

    $this->sugerenciasIa = array_values(array_filter(
        $this->sugerenciasIa,
        fn ($s) => trim(preg_replace('/^\d+[\.\)\-]\s*/', '', $s)) !== $texto
    ));
}
```

**Step 5: Add UI button in Blade**

In the template section that renders each `causa_directa` node (inside the causes tree), add a small button:

```blade
@if($nodo->tipo_nodo->value === 'causa_directa')
    <button
        wire:click="sugerirCausasIndirectas({{ $nodo->id }})"
        wire:loading.attr="disabled"
        class="mt-1 inline-flex items-center gap-1 text-xs text-purple-600 hover:text-purple-800"
    >
        <x-heroicon-m-sparkles class="w-3 h-3" />
        Sugerir causas indirectas
    </button>
@endif
```

**Step 6: Run all tests**

Run: `./vendor/bin/sail artisan test --filter="ArbolProblemaBuilderTest"`
Expected: All tests PASS.

**Step 7: Commit**

```bash
git add app/Livewire/Mml/ArbolProblemaBuilder.php \
       resources/views/livewire/mml/arbol-problema-builder.blade.php \
       tests/Feature/Mml/ArbolProblemaBuilderTest.php
git commit -m "feat(mml): add AI suggestions for indirect causes in problem tree

New sugerirCausasIndirectas() method generates contextual indirect cause
suggestions based on the central problem and a specific direct cause.

Resolves DTE-XX"
```

---

### Task 5: Auto-generate example tree with AI (Step 2)

**Context:** Administrators want a one-click button to generate a complete example tree (2 direct causes, 2 direct effects, 2 indirect causes per direct cause = 10 nodes) based on the central problem. Must respect "AI proposes, user decides" — show preview before saving.

**Files:**
- Modify: `app/Livewire/Mml/ArbolProblemaBuilder.php` (add generarArbolEjemplo, confirmarArbolEjemplo)
- Modify: `resources/views/livewire/mml/arbol-problema-builder.blade.php` (add button + preview modal)
- Test: `tests/Feature/Mml/ArbolProblemaBuilderTest.php`

**Step 1: Write the failing test**

```php
/** @test */
public function generar_arbol_ejemplo_produce_preview_sin_persistir()
{
    $this->mock(\App\Contracts\LlmServiceInterface::class, function ($mock) {
        $mock->shouldReceive('isDegraded')->andReturn(false);
        $mock->shouldReceive('suggest')->once()->andReturn(json_encode([
            'causas_directas' => [
                ['descripcion' => 'Causa directa 1', 'indirectas' => ['Indirecta 1A', 'Indirecta 1B']],
                ['descripcion' => 'Causa directa 2', 'indirectas' => ['Indirecta 2A', 'Indirecta 2B']],
            ],
            'efectos_directos' => ['Efecto directo 1', 'Efecto directo 2'],
        ]));
    });

    Livewire::actingAs($this->user)
        ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
        ->call('generarArbolEjemplo')
        ->assertSet('arbolEjemploPreview', fn ($val) => !empty($val))
        ->assertSet('mostrarPreviewArbol', true);

    // Verify nothing was persisted to database
    $this->assertEquals(1, ArbolNodo::where('arbol_id', $this->arbol->id)->count()); // only problema_central
}

/** @test */
public function confirmar_arbol_ejemplo_persiste_nodos()
{
    // Pre-set the preview data (simulating what generarArbolEjemplo sets)
    Livewire::actingAs($this->user)
        ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
        ->set('arbolEjemploPreview', [
            'causas_directas' => [
                ['descripcion' => 'Causa directa 1', 'indirectas' => ['Indirecta 1A', 'Indirecta 1B']],
                ['descripcion' => 'Causa directa 2', 'indirectas' => ['Indirecta 2A', 'Indirecta 2B']],
            ],
            'efectos_directos' => ['Efecto directo 1', 'Efecto directo 2'],
        ])
        ->set('mostrarPreviewArbol', true)
        ->call('confirmarArbolEjemplo');

    // 1 problema_central + 2 causas + 4 indirectas + 2 efectos = 9 nodes total
    // (problema_central already existed)
    $this->assertEquals(9, ArbolNodo::where('arbol_id', $this->arbol->id)->count());
}
```

**Step 2: Run tests to verify failure**

Run: `./vendor/bin/sail artisan test --filter="generar_arbol_ejemplo\|confirmar_arbol_ejemplo"`

**Step 3: Implement methods**

Add properties to `ArbolProblemaBuilder.php`:
```php
public array $arbolEjemploPreview = [];
public bool $mostrarPreviewArbol = false;
public bool $generandoArbol = false;
```

Add methods:
```php
public function generarArbolEjemplo(): void
{
    try {
        $this->generandoArbol = true;
        $arbol = Arbol::findOrFail($this->arbolId);
        $problemaCentral = $arbol->nodos()
            ->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)
            ->firstOrFail();

        $llm = app(LlmServiceInterface::class);
        $prompt = "Dado el problema central: \"{$problemaCentral->descripcion}\", "
            . "genera un árbol de problemas completo en formato JSON con exactamente esta estructura:\n"
            . "{\n"
            . "  \"causas_directas\": [\n"
            . "    {\"descripcion\": \"...\", \"indirectas\": [\"...\", \"...\"]},\n"
            . "    {\"descripcion\": \"...\", \"indirectas\": [\"...\", \"...\"]}\n"
            . "  ],\n"
            . "  \"efectos_directos\": [\"...\", \"...\"]\n"
            . "}\n"
            . "Exactamente 2 causas directas, 2 causas indirectas por cada directa, y 2 efectos directos. "
            . "Las causas y efectos deben ser específicos, relevantes y no genéricos. "
            . "Responde SOLO con el JSON, sin texto adicional.";

        $result = $llm->suggest($prompt);
        $this->arbolEjemploPreview = json_decode($result, true) ?? [];
        $this->mostrarPreviewArbol = !empty($this->arbolEjemploPreview);
    } catch (\Throwable $e) {
        report($e);
        session()->flash('error', 'No se pudo generar el árbol de ejemplo.');
    } finally {
        $this->generandoArbol = false;
    }
}

public function confirmarArbolEjemplo(): void
{
    if (empty($this->arbolEjemploPreview)) return;

    $arbol = Arbol::findOrFail($this->arbolId);
    $problemaCentral = $arbol->nodos()
        ->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)
        ->firstOrFail();

    $orden = 1;
    foreach ($this->arbolEjemploPreview['causas_directas'] ?? [] as $causa) {
        $nodo = ArbolNodo::create([
            'arbol_id' => $this->arbolId,
            'parent_id' => $problemaCentral->id,
            'tipo_nodo' => 'causa_directa',
            'descripcion' => $causa['descripcion'],
            'orden' => $orden++,
        ]);
        $subOrden = 1;
        foreach ($causa['indirectas'] ?? [] as $indirecta) {
            ArbolNodo::create([
                'arbol_id' => $this->arbolId,
                'parent_id' => $nodo->id,
                'tipo_nodo' => 'causa_indirecta',
                'descripcion' => $indirecta,
                'orden' => $subOrden++,
            ]);
        }
    }

    $orden = 1;
    foreach ($this->arbolEjemploPreview['efectos_directos'] ?? [] as $efecto) {
        ArbolNodo::create([
            'arbol_id' => $this->arbolId,
            'parent_id' => $problemaCentral->id,
            'tipo_nodo' => 'efecto_directo',
            'descripcion' => $efecto,
            'orden' => $orden++,
        ]);
    }

    $this->arbolEjemploPreview = [];
    $this->mostrarPreviewArbol = false;
    session()->flash('message', 'Árbol de ejemplo generado exitosamente.');
}

public function cancelarArbolEjemplo(): void
{
    $this->arbolEjemploPreview = [];
    $this->mostrarPreviewArbol = false;
}
```

**Step 4: Add UI (button + preview modal)**

In the AI assistant sidebar of `arbol-problema-builder.blade.php`, add above the existing suggestion buttons:

```blade
{{-- Auto-generate tree button --}}
<div class="mb-4 pb-4 border-b border-gray-200">
    <button
        wire:click="generarArbolEjemplo"
        wire:loading.attr="disabled"
        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 disabled:opacity-50 transition"
    >
        <span wire:loading.remove wire:target="generarArbolEjemplo">
            <x-heroicon-m-sparkles class="w-4 h-4" />
        </span>
        <span wire:loading wire:target="generarArbolEjemplo" class="animate-spin">
            <x-heroicon-m-arrow-path class="w-4 h-4" />
        </span>
        <span wire:loading.remove wire:target="generarArbolEjemplo">Generar árbol de ejemplo</span>
        <span wire:loading wire:target="generarArbolEjemplo">Generando...</span>
    </button>
    <p class="mt-1.5 text-xs text-gray-500 text-center">
        Genera 2 causas, 2 efectos y causas indirectas basadas en el problema central
    </p>
</div>

{{-- Preview modal --}}
@if($mostrarPreviewArbol && !empty($arbolEjemploPreview))
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Vista previa del árbol propuesto</h3>

        <div class="space-y-3">
            <h4 class="text-sm font-medium text-orange-700">Causas directas:</h4>
            @foreach($arbolEjemploPreview['causas_directas'] ?? [] as $causa)
                <div class="ml-2 p-2 bg-orange-50 rounded border border-orange-200">
                    <p class="text-sm font-medium">{{ $causa['descripcion'] }}</p>
                    <div class="ml-4 mt-1 space-y-1">
                        @foreach($causa['indirectas'] ?? [] as $indirecta)
                            <p class="text-xs text-gray-600">↳ {{ $indirecta }}</p>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <h4 class="text-sm font-medium text-purple-700 mt-4">Efectos directos:</h4>
            @foreach($arbolEjemploPreview['efectos_directos'] ?? [] as $efecto)
                <div class="ml-2 p-2 bg-purple-50 rounded border border-purple-200">
                    <p class="text-sm">{{ $efecto }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex gap-3 justify-end">
            <button wire:click="cancelarArbolEjemplo"
                class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                Cancelar
            </button>
            <button wire:click="confirmarArbolEjemplo"
                class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                Aceptar árbol propuesto
            </button>
        </div>
    </div>
</div>
@endif
```

**Step 5: Run all tests**

Run: `./vendor/bin/sail artisan test --filter="ArbolProblemaBuilderTest"`

**Step 6: Commit**

```bash
git add app/Livewire/Mml/ArbolProblemaBuilder.php \
       resources/views/livewire/mml/arbol-problema-builder.blade.php \
       tests/Feature/Mml/ArbolProblemaBuilderTest.php
git commit -m "feat(mml): add AI-generated example tree with preview modal

One-click generates a complete problem tree (2 direct causes, 2 effects,
2 indirect causes each) with preview before persisting. Respects 'AI proposes,
user decides' principle.

Resolves DTE-XX"
```

---

### Task 6: MIR Editor — Read/Edit mode separation (Step 7)

**Context:** Users find the MIR editor overwhelming with all inputs visible. Redesign to show a clean structural view by default, with per-level "Edit" buttons.

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php` (add $editandoNivel property, toggleEditarNivel method)
- Modify: `resources/views/livewire/mml/partials/mir-nivel-row.blade.php` (add read/edit modes)
- Modify: `resources/views/livewire/mml/mir-editor.blade.php` (snapshot selector in header)
- Test: `tests/Feature/Mml/MirEditorTest.php`

**Step 1: Write the failing test**

```php
/** @test */
public function mir_editor_defaults_to_read_mode()
{
    // Create MIR levels first
    $fin = $this->programa->mirNiveles()->create([
        'tipo_nivel' => 'fin',
        'resumen_narrativo' => 'Contribuir al desarrollo',
        'orden' => 1,
    ]);

    Livewire::actingAs($this->user)
        ->test(MirEditor::class, ['programa' => $this->programa])
        ->assertSet('editandoNivelId', null)
        ->assertSeeHtml('Contribuir al desarrollo'); // Read mode shows text
}

/** @test */
public function toggle_editar_nivel_activa_modo_edicion()
{
    $fin = $this->programa->mirNiveles()->create([
        'tipo_nivel' => 'fin',
        'resumen_narrativo' => 'Contribuir al desarrollo',
        'orden' => 1,
    ]);

    Livewire::actingAs($this->user)
        ->test(MirEditor::class, ['programa' => $this->programa])
        ->call('toggleEditarNivel', $fin->id)
        ->assertSet('editandoNivelId', $fin->id);
}
```

**Step 2: Run tests to verify failure**

Run: `./vendor/bin/sail artisan test --filter="mir_editor_defaults_to_read_mode\|toggle_editar_nivel"`

**Step 3: Add property and method to MirEditor**

In `app/Livewire/Mml/MirEditor.php`:

Add property:
```php
public ?int $editandoNivelId = null;
```

Add method:
```php
public function toggleEditarNivel(?int $nivelId): void
{
    $this->editandoNivelId = $this->editandoNivelId === $nivelId ? null : $nivelId;
}
```

**Step 4: Refactor mir-nivel-row.blade.php to support read/edit modes**

The partial receives a new `$editando` boolean prop. When false, show read-only display. When true, show the current editable interface.

In `mir-editor.blade.php`, pass the prop when including the partial:
```blade
@include('livewire.mml.partials.mir-nivel-row', [
    'nivel' => $fin,
    'reglas' => $reglasMap['fin'] ?? [],
    'colorClass' => 'blue',
    'editando' => $editandoNivelId === $fin->id,
])
```

In `mir-nivel-row.blade.php`, wrap the entire content in a conditional:

**Read mode** (default):
- Resumen narrativo: plain text paragraph
- Indicadores: table with columns (Nombre, Tipo, Dimensión, Frecuencia, Fórmula)
- Medios de verificación: inline list under each indicator
- Supuestos: plain text
- "Editar" button at top-right of the row
- Alignment shown as badges

**Edit mode** (when `$editando`):
- Current full interface (textareas, buttons, AI actions)
- "Guardar y cerrar" button that calls `toggleEditarNivel(null)`

This is a significant template refactor. The read mode should be a new `@if(!$editando)` block at the top, followed by `@else` with the existing edit interface.

**Step 5: Run all tests**

Run: `./vendor/bin/sail artisan test --filter="MirEditorTest"`

**Step 6: Commit**

```bash
git add app/Livewire/Mml/MirEditor.php \
       resources/views/livewire/mml/partials/mir-nivel-row.blade.php \
       resources/views/livewire/mml/mir-editor.blade.php \
       tests/Feature/Mml/MirEditorTest.php
git commit -m "feat(mml): add read/edit mode separation for MIR editor

MIR now opens in clean read-only view showing structured data.
Per-level 'Edit' button activates editing for that specific level.
Indicators display as table in read mode, full form in edit mode.

Resolves DTE-XX"
```

---

### Task 7: Fix AI validation inconsistency (Step 7)

**Context:** When validating narrative syntax, accepting the suggestion, and re-validating, the old errors still show. Need to clear validation state when accepting and ensure re-validation uses current text.

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php:368-381` (aceptarSugerencia method)
- Test: `tests/Feature/Mml/MirEditorTest.php`

**Step 1: Write the failing test**

```php
/** @test */
public function aceptar_sugerencia_limpia_estado_validacion()
{
    $fin = $this->programa->mirNiveles()->create([
        'tipo_nivel' => 'fin',
        'resumen_narrativo' => 'Texto original con errores',
        'sintaxis_valida' => false,
        'sintaxis_observacion' => 'No cumple estructura',
        'sintaxis_sugerencia' => 'Contribuir a mejorar X mediante Y',
        'orden' => 1,
    ]);

    Livewire::actingAs($this->user)
        ->test(MirEditor::class, ['programa' => $this->programa])
        ->call('aceptarSugerencia', $fin->id);

    $fin->refresh();
    $this->assertEquals('Contribuir a mejorar X mediante Y', $fin->resumen_narrativo);
    $this->assertNull($fin->sintaxis_valida);
    $this->assertNull($fin->sintaxis_observacion);
    $this->assertNull($fin->sintaxis_sugerencia);
    $this->assertNull($fin->sintaxis_validada_at);
}
```

**Step 2: Run test to verify failure**

Run: `./vendor/bin/sail artisan test --filter="aceptar_sugerencia_limpia_estado"`

**Step 3: Fix aceptarSugerencia method**

In `app/Livewire/Mml/MirEditor.php`, modify `aceptarSugerencia()`:

```php
public function aceptarSugerencia(int $nivelId): void
{
    $nivel = MirNivel::findOrFail($nivelId);

    if ($nivel->sintaxis_sugerencia) {
        $nivel->update([
            'resumen_narrativo' => $nivel->sintaxis_sugerencia,
            // Clear ALL validation state so re-validation uses fresh text
            'sintaxis_valida' => null,
            'sintaxis_observacion' => null,
            'sintaxis_sugerencia' => null,
            'sintaxis_validada_at' => null,
        ]);
    }
}
```

**Step 4: Run all tests**

Run: `./vendor/bin/sail artisan test --filter="MirEditorTest"`

**Step 5: Commit**

```bash
git add app/Livewire/Mml/MirEditor.php \
       tests/Feature/Mml/MirEditorTest.php
git commit -m "fix(mml): clear validation state when accepting AI syntax suggestion

Accepting a suggestion now resets sintaxis_valida, observacion, sugerencia,
and validada_at so re-validation works against the new text, not cached results.

Resolves DTE-XX"
```

---

### Task 8: AI formula generation (Step 7)

**Context:** Users need help generating calculation formulas for complex indicators like "Tasa de crecimiento del PIB del sector agroindustrial". Add a `sugerirFormula()` method that uses AI context.

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php` (add sugerirFormula method)
- Modify: `resources/views/livewire/mml/partials/mir-nivel-row.blade.php` (add button next to formula input)
- Test: `tests/Feature/Mml/MirEditorTest.php`

**Step 1: Write the failing test**

```php
/** @test */
public function sugerir_formula_genera_formula_para_indicador()
{
    $fin = $this->programa->mirNiveles()->create([
        'tipo_nivel' => 'fin',
        'resumen_narrativo' => 'Contribuir al crecimiento del sector agroindustrial',
        'orden' => 1,
    ]);
    $indicador = $fin->indicadores()->create([
        'nombre' => 'Tasa de crecimiento del PIB agroindustrial',
        'tipo' => 'estrategico',
        'dimension' => 'eficacia',
        'frecuencia' => 'anual',
        'orden' => 1,
    ]);

    $this->mock(\App\Contracts\LlmServiceInterface::class, function ($mock) {
        $mock->shouldReceive('isDegraded')->andReturn(false);
        $mock->shouldReceive('suggest')->once()->andReturn(
            '((PIB agroindustrial año actual - PIB agroindustrial año anterior) / PIB agroindustrial año anterior) × 100'
        );
    });

    Livewire::actingAs($this->user)
        ->test(MirEditor::class, ['programa' => $this->programa])
        ->call('sugerirFormula', $indicador->id);

    $indicador->refresh();
    $this->assertNotNull($indicador->formula_texto);
    $this->assertStringContainsString('PIB agroindustrial', $indicador->formula_texto);
}
```

**Step 2: Run test, verify failure**

**Step 3: Implement method**

In `app/Livewire/Mml/MirEditor.php`:

```php
public function sugerirFormula(int $indicadorId): void
{
    $indicador = \App\Models\Mml\Indicador::with('mirNivel')->findOrFail($indicadorId);
    $nivel = $indicador->mirNivel;

    $llm = app(LlmServiceInterface::class);
    $prompt = "Para el indicador \"{$indicador->nombre}\" "
        . "(tipo: {$indicador->tipo->value}, dimensión: {$indicador->dimension->value}) "
        . "del nivel MIR \"{$nivel->tipo_nivel->label()}: {$nivel->resumen_narrativo}\", "
        . "sugiere una fórmula de cálculo clara y precisa. "
        . "La fórmula debe usar nombres de variables descriptivos. "
        . "Responde SOLO con la fórmula, sin explicaciones.";

    try {
        $formula = $llm->suggest($prompt);
        $indicador->update(['formula_texto' => trim($formula)]);
    } catch (\Throwable $e) {
        report($e);
        session()->flash('error', 'No se pudo generar la fórmula.');
    }
}
```

**Step 4: Add button in Blade**

In `mir-nivel-row.blade.php`, next to the formula textarea (around line 205-210 in the edit mode section):

```blade
<button wire:click="sugerirFormula({{ $indicador->id }})"
    wire:loading.attr="disabled"
    wire:target="sugerirFormula({{ $indicador->id }})"
    class="inline-flex items-center gap-1 text-xs text-purple-600 hover:text-purple-800"
    title="Sugerir fórmula con IA">
    <x-heroicon-m-sparkles class="w-3.5 h-3.5" />
    <span wire:loading.remove wire:target="sugerirFormula({{ $indicador->id }})">Sugerir fórmula</span>
    <span wire:loading wire:target="sugerirFormula({{ $indicador->id }})">Generando...</span>
</button>
```

**Step 5: Run tests, commit**

```bash
git add app/Livewire/Mml/MirEditor.php \
       resources/views/livewire/mml/partials/mir-nivel-row.blade.php \
       tests/Feature/Mml/MirEditorTest.php
git commit -m "feat(mml): add AI-powered formula suggestion for MIR indicators

New sugerirFormula() method generates contextual calculation formulas
based on indicator name, type, dimension, and MIR level context.

Resolves DTE-XX"
```

---

### Task 9: Improve Step 6 alignment — PED base with ODS/PND references

**Context:** Strategic alignment search should clearly establish PED as the base level. When a PED objective is selected, automatically show related PND and ODS as non-editable reference badges.

**Files:**
- Modify: `app/Livewire/Mml/AlineacionEstrategica.php` (load PND/ODS relations on selection)
- Modify: `resources/views/livewire/mml/alineacion-estrategica.blade.php` (visual improvement)
- Test: `tests/Feature/Mml/AlineacionEstrategicaTest.php`

**Step 1: Write the failing test**

```php
/** @test */
public function seleccionar_sugerencia_carga_relaciones_pnd_ods()
{
    $chain = $this->createPedChain();

    // Create FIN level needed for guardar()
    $this->programa->mirNiveles()->create([
        'tipo_nivel' => 'fin',
        'resumen_narrativo' => 'FIN test',
        'orden' => 1,
    ]);

    Livewire::actingAs($this->user)
        ->test(AlineacionEstrategica::class, ['programa' => $this->programa])
        ->set('ejeId', $chain['eje']->id)
        ->set('temaId', $chain['tema']->id)
        ->set('objetivoEstrategicoId', $chain['objetivo']->id)
        ->assertViewHas('pndRelacionados')
        ->assertViewHas('odsRelacionados');
}
```

**Step 2: Implement in component**

Add computed properties or pass via render() the PND and ODS relationships for the currently selected PED objective. In `render()`:

```php
$pndRelacionados = collect();
$odsRelacionados = collect();

if ($this->objetivoEstrategicoId) {
    $pedObj = PedObjetivoEstrategico::with(['alineacionesPnd', 'alineacionesOds'])->find($this->objetivoEstrategicoId);
    if ($pedObj) {
        $pndRelacionados = $pedObj->alineacionesPnd ?? collect();
        $odsRelacionados = $pedObj->alineacionesOds ?? collect();
    }
}

return view('livewire.mml.alineacion-estrategica', compact('pndRelacionados', 'odsRelacionados', ...));
```

Note: The exact relationship names depend on the model. Check `PedObjetivoEstrategico` for its relationships to PND and ODS entities. If no direct relationship exists, the alignment may go through a pivot table — adjust query accordingly.

**Step 3: Update Blade template**

After the PED selection section, add a reference panel:

```blade
@if($objetivoEstrategicoId && ($pndRelacionados->isNotEmpty() || $odsRelacionados->isNotEmpty()))
<div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
    <h4 class="text-sm font-medium text-gray-700 mb-3">Alineaciones de referencia</h4>

    @if($pndRelacionados->isNotEmpty())
    <div class="mb-3">
        <span class="text-xs font-semibold text-blue-700 uppercase tracking-wide">PND</span>
        <div class="mt-1 flex flex-wrap gap-2">
            @foreach($pndRelacionados as $pnd)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ $pnd->clave ?? $pnd->nombre }}
            </span>
            @endforeach
        </div>
    </div>
    @endif

    @if($odsRelacionados->isNotEmpty())
    <div>
        <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">ODS</span>
        <div class="mt-1 flex flex-wrap gap-2">
            @foreach($odsRelacionados as $ods)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                {{ $ods->numero ?? '' }}. {{ $ods->nombre }}
            </span>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif
```

**Step 4: Run tests, commit**

```bash
git add app/Livewire/Mml/AlineacionEstrategica.php \
       resources/views/livewire/mml/alineacion-estrategica.blade.php \
       tests/Feature/Mml/AlineacionEstrategicaTest.php
git commit -m "feat(mml): show PND/ODS reference badges when PED alignment is selected

When a PED strategic objective is selected in Step 6, automatically loads
and displays related PND and ODS alignments as read-only reference badges.

Resolves DTE-XX"
```

---

## Phase 3 — UX / Dashboard

### Task 10: Hierarchical collapsible indicator dashboard

**Context:** Replace the flat list panel with a hierarchical view: FIN → PROPOSITO → COMPONENTE (with nested ACTIVIDAD). Each level collapses/expands. Indicators show progress bars with semaphore colors.

**Files:**
- Create: `app/Livewire/Tracking/DashboardIndicadores.php`
- Create: `resources/views/livewire/tracking/dashboard-indicadores.blade.php`
- Create: `resources/views/livewire/tracking/partials/indicador-detalle.blade.php`
- Modify: `routes/web/tracking.php` (add route)
- Test: `tests/Feature/Tracking/DashboardIndicadoresTest.php`

**Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Tracking;

use App\Livewire\Tracking\DashboardIndicadores;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardIndicadoresTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_hierarchical_mir_structure()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::factory()->create();

        $fin = $programa->mirNiveles()->create([
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'FIN test', 'orden' => 1,
        ]);
        $proposito = $programa->mirNiveles()->create([
            'tipo_nivel' => 'proposito', 'resumen_narrativo' => 'PROPOSITO test', 'orden' => 2,
        ]);
        $componente = $programa->mirNiveles()->create([
            'tipo_nivel' => 'componente', 'resumen_narrativo' => 'COMP test', 'orden' => 3,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardIndicadores::class, ['programa' => $programa])
            ->assertSee('FIN test')
            ->assertSee('PROPOSITO test')
            ->assertSee('COMP test');
    }

    /** @test */
    public function toggle_nivel_expands_and_collapses()
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::factory()->create();
        $fin = $programa->mirNiveles()->create([
            'tipo_nivel' => 'fin', 'resumen_narrativo' => 'FIN test', 'orden' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardIndicadores::class, ['programa' => $programa])
            ->assertSet('expandedNiveles', [])
            ->call('toggleNivel', $fin->id)
            ->assertSet('expandedNiveles', [$fin->id]);
    }
}
```

**Step 2: Create Livewire component**

`app/Livewire/Tracking/DashboardIndicadores.php`:

```php
<?php

namespace App\Livewire\Tracking;

use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard de Indicadores')]
class DashboardIndicadores extends Component
{
    public ProgramaPresupuestario $programa;
    public array $expandedNiveles = [];
    public ?int $indicadorDetalleId = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
    }

    public function toggleNivel(int $nivelId): void
    {
        if (in_array($nivelId, $this->expandedNiveles)) {
            $this->expandedNiveles = array_values(array_diff($this->expandedNiveles, [$nivelId]));
        } else {
            $this->expandedNiveles[] = $nivelId;
        }
    }

    public function verDetalle(int $indicadorId): void
    {
        $this->indicadorDetalleId = $this->indicadorDetalleId === $indicadorId ? null : $indicadorId;
    }

    public function render()
    {
        $fin = $this->programa->mirNiveles()
            ->where('tipo_nivel', 'fin')
            ->with(['indicadores.metasPeriodo.avances', 'indicadores.cremaaValidacion'])
            ->first();

        $proposito = $this->programa->mirNiveles()
            ->where('tipo_nivel', 'proposito')
            ->with(['indicadores.metasPeriodo.avances', 'indicadores.cremaaValidacion'])
            ->first();

        $componentes = $this->programa->mirNiveles()
            ->where('tipo_nivel', 'componente')
            ->with([
                'indicadores.metasPeriodo.avances',
                'indicadores.cremaaValidacion',
                'actividades.indicadores.metasPeriodo.avances',
                'actividades.indicadores.cremaaValidacion',
                'team',
            ])
            ->orderBy('orden')
            ->get();

        return view('livewire.tracking.dashboard-indicadores', compact(
            'fin', 'proposito', 'componentes'
        ));
    }
}
```

**Step 3: Create the Blade view**

`resources/views/livewire/tracking/dashboard-indicadores.blade.php`:

Build the hierarchical collapsible view using Alpine.js for expand/collapse animations. Each nivel row shows:
- Color-coded type badge (FIN blue, PROPOSITO green, COMPONENTE amber, ACTIVIDAD violet)
- Resumen narrativo truncated
- Indicator count badge
- Chevron for expand/collapse
- When expanded: full resumen, indicators with progress bars + semaphore dots
- Click on indicator → detail panel with desglose temporal + chart

Use `$expandedNiveles` array to track which levels are open. Pass to Alpine for smooth transitions.

**Step 4: Create indicator detail partial**

`resources/views/livewire/tracking/partials/indicador-detalle.blade.php`:

Shows:
- Indicator metadata (nombre, tipo, dimensión, sentido, frecuencia, fórmula)
- Desglose table: columns per period (trimestral: T1-T4 or semestral: S1-S2)
- Each cell: meta programada, avance real, % cumplimiento, semaphore circle
- Chart.js canvas for evolution graph (meta vs avance line/bar chart)
- Chart type based on indicator: line for % indicators, grouped bars for absolute values

**Step 5: Add route**

In `routes/web/tracking.php`, add:
```php
Route::get('/{programa}/dashboard-indicadores', DashboardIndicadores::class)
    ->name('tracking.dashboard-indicadores');
```

**Step 6: Run tests, commit**

```bash
git add app/Livewire/Tracking/DashboardIndicadores.php \
       resources/views/livewire/tracking/dashboard-indicadores.blade.php \
       resources/views/livewire/tracking/partials/indicador-detalle.blade.php \
       routes/web/tracking.php \
       tests/Feature/Tracking/DashboardIndicadoresTest.php
git commit -m "feat(tracking): add hierarchical collapsible indicator dashboard

New dashboard shows MIR structure (FIN > PROPOSITO > COMPONENTE > ACTIVIDAD)
with collapsible levels, progress bars, semaphore indicators, and detailed
temporal breakdown with Chart.js graphs per indicator.

Resolves DTE-XX"
```

---

### Task 11: Snapshots in MIR structural view

**Context:** Version selector should be visible in the read-only MIR view (from Task 6). Selecting a historical version shows snapshot data with a warning banner.

**Files:**
- Modify: `app/Livewire/Mml/MirEditor.php` (add $viendoVersionId property, method cargarVersion)
- Modify: `resources/views/livewire/mml/mir-editor.blade.php` (version dropdown in header, banner)
- Test: `tests/Feature/Mml/MirEditorTest.php`

**Step 1: Write the failing test**

```php
/** @test */
public function puede_ver_version_historica_en_modo_lectura()
{
    $fin = $this->programa->mirNiveles()->create([
        'tipo_nivel' => 'fin', 'resumen_narrativo' => 'Texto actual', 'orden' => 1,
    ]);

    $version = \App\Models\Mml\MirVersion::create([
        'programa_presupuestario_id' => $this->programa->id,
        'etiqueta' => 'v1 — Borrador inicial',
        'snapshot' => [
            'niveles' => [
                ['tipo_nivel' => 'fin', 'resumen_narrativo' => 'Texto anterior', 'orden' => 1],
            ],
        ],
        'created_by' => $this->user->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(MirEditor::class, ['programa' => $this->programa])
        ->call('cargarVersion', $version->id)
        ->assertSet('viendoVersionId', $version->id)
        ->assertSet('snapshotData', fn ($val) => $val['niveles'][0]['resumen_narrativo'] === 'Texto anterior');
}
```

**Step 2: Implement**

Add properties to `MirEditor.php`:
```php
public ?int $viendoVersionId = null;
public ?array $snapshotData = null;
```

Add method:
```php
public function cargarVersion(?int $versionId): void
{
    if (!$versionId) {
        $this->viendoVersionId = null;
        $this->snapshotData = null;
        return;
    }
    $version = MirVersion::findOrFail($versionId);
    $this->viendoVersionId = $versionId;
    $this->snapshotData = $version->snapshot;
}

public function volverAVersionActual(): void
{
    $this->viendoVersionId = null;
    $this->snapshotData = null;
}
```

**Step 3: Update Blade**

In the header of `mir-editor.blade.php`, add version selector dropdown:
```blade
<select wire:change="cargarVersion($event.target.value)" class="text-sm border-gray-300 rounded-md">
    <option value="">Versión actual</option>
    @foreach($versiones as $version)
        <option value="{{ $version->id }}" @selected($viendoVersionId === $version->id)>
            {{ $version->etiqueta }} — {{ $version->created_at->format('d/m/Y H:i') }}
        </option>
    @endforeach
</select>
```

When `$viendoVersionId` is set, show warning banner and render from `$snapshotData` instead of live data. Hide all edit buttons.

```blade
@if($viendoVersionId)
<div class="mb-4 p-3 bg-amber-50 border border-amber-300 rounded-lg flex items-center gap-2">
    <x-heroicon-m-clock class="w-5 h-5 text-amber-600" />
    <span class="text-sm text-amber-800 font-medium">Viendo versión histórica.</span>
    <button wire:click="volverAVersionActual" class="ml-auto text-sm text-amber-700 underline">
        Volver a versión actual
    </button>
</div>
@endif
```

**Step 4: Run tests, commit**

```bash
git add app/Livewire/Mml/MirEditor.php \
       resources/views/livewire/mml/mir-editor.blade.php \
       tests/Feature/Mml/MirEditorTest.php
git commit -m "feat(mml): show MIR version history in structural read-only view

Version selector dropdown in header allows viewing historical snapshots.
Warning banner indicates historical view, edit buttons hidden.

Resolves DTE-XX"
```

---

### Task 12: Document AI context flow between steps

**Context:** Administrators need a clear diagram of how AI context is maintained across the 7 MML steps.

**Files:**
- Create: `docs/architecture/ai-context-flow.md`

**Step 1: Write the document**

```markdown
# Flujo de Contexto de IA en el Asistente MML

## Principio Fundamental

Cada paso del MML lee y escribe a la **base de datos**, no a una "sesión" o "chat" de IA.
No existe una sesión persistente de IA entre pasos. Cada llamada al LLM es independiente
y recibe su contexto desde los datos almacenados en la BD.

## Diagrama de Flujo de Contexto

### Paso 1 — Definición del Problema
- **Entrada**: Usuario escribe texto libre
- **IA**: Valida estructura del problema (validateProblema)
- **Persistencia**: ArbolNodo (tipo: problema_central) en BD
- **¿Contexto al paso 2?**: SÍ — se lee de ArbolNodo

### Paso 2 — Árbol del Problema
- **Entrada**: Problema central (de BD, paso 1)
- **IA — Sugerir causas**: Prompt incluye problema_central + causas existentes
- **IA — Sugerir efectos**: Prompt incluye problema_central + efectos existentes
- **IA — Sugerir causas indirectas**: Prompt incluye problema_central + causa directa padre + indirectas existentes
- **IA — Generar árbol ejemplo**: Prompt incluye solo problema_central
- **¿Puede repetir nodos?**: Mitigado — los nodos existentes se incluyen en el prompt con instrucción "no repetir"
- **¿Fusión de nodos similares?**: NO implementado actualmente (backlog)
- **Persistencia**: ArbolNodo con parent_id (relaciones padre-hijo)
- **¿Contexto al paso 3?**: SÍ — todo el árbol se lee de BD

### Paso 3 — Árbol de Objetivos
- **Entrada**: Árbol completo de problemas (de BD, paso 2)
- **IA**: Transforma cada nodo problema → objetivo positivo (transform)
- **Transformación es 1:1**: Cada nodo mantiene nodo_origen_id como referencia
- **Persistencia**: Nuevo Arbol tipo 'objetivos' con ArbolNodo derivados
- **¿Contexto al paso 4?**: SÍ — se lee de BD

### Paso 4 — Selección de Alternativas
- **Entrada**: Árbol de objetivos (de BD, paso 3)
- **IA**: Evalúa coherencia de cada alternativa individual (evaluarConIa)
- **¿Validación cruzada?**: NO — cada alternativa se evalúa independientemente.
  Alternativas contradictorias o similares NO se detectan automáticamente (backlog)
- **Persistencia**: Alternativa con nodos asociados (pivot table)
- **¿Contexto al paso 5?**: Indirecto — la alternativa seleccionada se usa en paso 6-7

### Paso 5 — Embudo de Poblaciones
- **Entrada**: Datos numéricos del usuario
- **IA**: No utilizada en este paso
- **Persistencia**: PoblacionPrograma en BD
- **¿Contexto al paso 6?**: Indirecto — se valida existencia como prerequisito

### Paso 6 — Alineación Estratégica
- **Entrada**: Problema central (de BD, paso 1-2) + catálogo PED con embeddings
- **IA**: Búsqueda semántica (SemanticSearchService) usando embeddings vectoriales
- **Persistencia**: MirNivel.ped_objetivo_estrategico_id
- **Contexto de pasos anteriores**: Usa problema_central como query de búsqueda
- **Finalización**: Genera MIR pre-llenada desde árbol de objetivos + alternativa seleccionada

### Paso 7 — Editor MIR
- **Entrada**: MIR pre-llenada (de paso 6) + todos los datos acumulados
- **IA — Validar sintaxis**: Usa texto del resumen narrativo + tipo de nivel
- **IA — Validar CREMAA**: Usa datos completos del indicador
- **IA — Extraer variables**: Usa texto de la fórmula
- **IA — Sugerir fórmula**: Usa nombre indicador + tipo + dimensión + resumen del nivel
- **IA — Validar lógica**: Usa estructura completa de la MIR
- **IA — Buscar alineación**: Búsqueda semántica similar a paso 6

## Resumen

| Paso | Lee contexto de | Escribe a | IA utilizada |
|------|-----------------|-----------|--------------|
| 1 | - | ArbolNodo (problema_central) | validateProblema |
| 2 | Paso 1 (BD) | ArbolNodo (causas, efectos) | suggest (causas, efectos, indirectas) |
| 3 | Paso 2 (BD) | ArbolNodo (objetivos) | transform |
| 4 | Paso 3 (BD) | Alternativa + pivot | evaluarConIa |
| 5 | - | PoblacionPrograma | - |
| 6 | Pasos 1-5 (BD) | MirNivel (alineación) | SemanticSearch |
| 7 | Paso 6 + todos (BD) | MirNivel, Indicador, etc. | validate, suggest, extract, search |
```

**Step 2: Commit**

```bash
git add docs/architecture/ai-context-flow.md
git commit -m "docs: add AI context flow diagram for MML wizard steps

Documents how context is maintained between steps (via database, not sessions),
which AI methods are used at each step, and known limitations.

Resolves DTE-XX"
```

---

## Execution Order Summary

| Task | Phase | Description | Dependencies |
|------|-------|-------------|-------------|
| 1 | 1 | Fix effects added as causes | None |
| 2 | 1 | Fix error 500 finalizar planeación | None |
| 3 | 1 | Fix embudo large numbers | None |
| 4 | 2 | AI indirect causes | Task 1 (uses $tipoSugerencia) |
| 5 | 2 | Auto-generate example tree | Task 4 (extends same component) |
| 6 | 2 | MIR read/edit modes | None |
| 7 | 2 | Fix AI validation inconsistency | None |
| 8 | 2 | AI formula generation | Task 6 (button goes in edit mode) |
| 9 | 2 | PED alignment with PND/ODS refs | Task 2 (finalization must work) |
| 10 | 3 | Hierarchical indicator dashboard | None |
| 11 | 3 | Snapshots in structural view | Task 6 (read mode must exist) |
| 12 | 3 | AI context flow documentation | None |

**Tasks 1, 2, 3 can run in parallel.** Tasks 6, 7, 12 can run in parallel. Task 10 is independent.
