# S3-T6: Interfaz Etapa 3 — Árbol de Objetivos — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S3-T6
**Tipo:** feat
**Rama:** `feat/S3-T6-etapa3-arbol-objetivos`
**Sprint:** 3 — Metodología de Marco Lógico (Etapas 1-4)
**Depende de:** S3-T5 (Etapa 2 — Árbol del Problema), S3-T8 (LlmService)

**Goal:** Interfaz para la transformación automática del árbol de problemas a árbol de objetivos. Cada nodo negativo se convierte en positivo con ayuda de IA. El usuario revisa y aprueba cada transformación.

**Architecture:** Componente Livewire `ArbolObjetivosBuilder` que al montar genera automáticamente el árbol de objetivos desde el árbol de problemas (si no existe aún). Cada nodo transformado se vincula con su nodo origen vía `nodo_origen_id`. La IA sugiere redacciones positivas vía `LlmService::transform()`. Vista lado a lado: problemas (tachado) → objetivos (positivo).

**Tech Stack:** Laravel 12, Livewire 3, Blade Components, Tailwind CSS, LlmService

---

## Pre-requisitos

- S3-T5 completado (Árbol de problemas con nodos)
- S3-T2 (ArbolNodo con `nodo_origen_id`)
- S3-T8 (LlmService con método `transform()`)

---

## Pasos

### Task 1: Agregar ruta de Etapa 3

**Files:**
- Modify: `routes/web/mml.php`

**Step 1: Agregar ruta**

```php
Route::get('/etapa/3', \App\Livewire\Mml\ArbolObjetivosBuilder::class)
    ->name('mml.etapa3');
```

**Step 2: Commit**

```bash
git add routes/web/mml.php
git commit -m "feat(S3-T6): add etapa 3 route"
```

---

### Task 2: Crear prompt Blade para transformación

**Files:**
- Create: `resources/views/prompts/mml/transformar-a-positivo.blade.php`

**Step 1: Crear el prompt**

```blade
Eres un experto en Metodología de Marco Lógico (MML).

Transforma el siguiente enunciado negativo (del árbol de problemas) en su equivalente positivo (para el árbol de objetivos).

TIPO DE NODO ORIGINAL: {{ $tipoOriginal }}
ENUNCIADO NEGATIVO: "{{ $textoNegativo }}"

REGLAS:
1. Convierte la situación negativa en una situación deseada positiva
2. No uses "No" o "Falta de" — redacta en positivo
3. Mantén el mismo nivel de especificidad
4. El resultado debe ser una condición alcanzable y verificable

Responde SOLO con el texto transformado, sin explicaciones.
```

**Step 2: Commit**

```bash
git add resources/views/prompts/mml/transformar-a-positivo.blade.php
git commit -m "feat(S3-T6): add prompt for positive transformation"
```

---

### Task 3: Crear componente Livewire ArbolObjetivosBuilder

**Files:**
- Create: `app/Livewire/Mml/ArbolObjetivosBuilder.php`
- Create: `resources/views/livewire/mml/arbol-objetivos-builder.blade.php`
- Create: `tests/Feature/Mml/ArbolObjetivosBuilderTest.php`

**Step 1: Escribir tests**

Crear `tests/Feature/Mml/ArbolObjetivosBuilderTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Livewire\Mml\ArbolObjetivosBuilder;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArbolObjetivosBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private Arbol $arbolProblema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        // Crear árbol de problemas con nodos
        $this->arbolProblema = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
        $central = ArbolNodo::create([
            'arbol_id' => $this->arbolProblema->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Alto índice de deserción escolar',
        ]);
        ArbolNodo::create([
            'arbol_id' => $this->arbolProblema->id,
            'parent_id' => $central->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Falta de recursos económicos',
            'orden' => 1,
        ]);
        ArbolNodo::create([
            'arbol_id' => $this->arbolProblema->id,
            'parent_id' => $central->id,
            'tipo_nodo' => TipoNodo::EFECTO_DIRECTO->value,
            'descripcion' => 'Baja competitividad laboral',
            'orden' => 1,
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 3');
    }

    public function test_genera_arbol_objetivos_automaticamente(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $this->assertDatabaseHas('arboles', [
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => 'objetivos',
        ]);

        // Debe crear nodos equivalentes
        $arbolObj = Arbol::where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo', 'objetivos')->first();

        $this->assertNotNull($arbolObj);
        $this->assertEquals(3, $arbolObj->nodos()->count()); // central + causa + efecto
    }

    public function test_nodos_vinculados_via_nodo_origen_id(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $arbolObj = Arbol::where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo', 'objetivos')->first();

        $nodosCentral = $arbolObj->nodos()->where('tipo_nodo', 'objetivo_central')->first();

        $this->assertNotNull($nodosCentral->nodo_origen_id);
    }

    public function test_editar_nodo_transformado(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $arbolObj = Arbol::where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo', 'objetivos')->first();
        $nodo = $arbolObj->nodos()->where('tipo_nodo', 'objetivo_central')->first();

        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa])
            ->call('editarNodo', $nodo->id)
            ->set('editDescripcion', 'Reducir el índice de deserción escolar')
            ->call('guardarEdicion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'id' => $nodo->id,
            'descripcion' => 'Reducir el índice de deserción escolar',
        ]);
    }

    public function test_no_regenera_si_arbol_objetivos_ya_existe(): void
    {
        // Primera carga: genera el árbol
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $arbolObj = Arbol::where('programa_presupuestario_id', $this->programa->id)
            ->where('tipo', 'objetivos')->first();
        $countOriginal = $arbolObj->nodos()->count();

        // Segunda carga: no debe duplicar
        Livewire::actingAs($this->user)
            ->test(ArbolObjetivosBuilder::class, ['programa' => $this->programa]);

        $this->assertEquals($countOriginal, $arbolObj->fresh()->nodos()->count());
    }
}
```

**Step 2: Ejecutar tests para verificar que fallan**

```bash
sail artisan test --filter=ArbolObjetivosBuilderTest
```

Expected: FAIL.

**Step 3: Crear mapeo de tipos problema → objetivo**

El mapeo se usa internamente en el componente:

```
problema_central → objetivo_central
causa_directa → medio_directo
causa_indirecta → medio_indirecto
efecto_directo → fin_directo
efecto_indirecto → fin_indirecto
```

**Step 4: Crear componente Livewire**

Crear `app/Livewire/Mml/ArbolObjetivosBuilder.php`:

```php
<?php

namespace App\Livewire\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 3 — Árbol de Objetivos')]
class ArbolObjetivosBuilder extends Component
{
    public ProgramaPresupuestario $programa;
    public ?Arbol $arbolProblema = null;
    public ?Arbol $arbolObjetivos = null;

    // Edición
    public ?int $editNodoId = null;
    public string $editDescripcion = '';

    // IA
    public bool $transformandoConIa = false;

    private const MAPEO_TIPOS = [
        'problema_central' => 'objetivo_central',
        'causa_directa' => 'medio_directo',
        'causa_indirecta' => 'medio_indirecto',
        'efecto_directo' => 'fin_directo',
        'efecto_indirecto' => 'fin_indirecto',
    ];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
        $this->arbolProblema = $programa->arboles()
            ->where('tipo', TipoArbol::PROBLEMA->value)->first();

        if ($this->arbolProblema) {
            $this->arbolObjetivos = $programa->arboles()
                ->where('tipo', TipoArbol::OBJETIVOS->value)->first();

            if (!$this->arbolObjetivos) {
                $this->generarArbolObjetivos();
            }
        }
    }

    private function generarArbolObjetivos(): void
    {
        $this->arbolObjetivos = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);

        // Transformar nodos recursivamente
        $nodosRaiz = $this->arbolProblema->nodos()
            ->whereNull('parent_id')
            ->orderBy('orden')
            ->get();

        foreach ($nodosRaiz as $nodoProblema) {
            $this->transformarNodo($nodoProblema, null);
        }
    }

    private function transformarNodo(ArbolNodo $nodoProblema, ?int $parentObjetivoId): void
    {
        $tipoOriginal = is_string($nodoProblema->tipo_nodo)
            ? $nodoProblema->tipo_nodo
            : $nodoProblema->tipo_nodo->value;

        $tipoObjetivo = self::MAPEO_TIPOS[$tipoOriginal] ?? null;

        if (!$tipoObjetivo) {
            return;
        }

        // Crear nodo de objetivo con descripción placeholder
        $nodoObjetivo = ArbolNodo::create([
            'arbol_id' => $this->arbolObjetivos->id,
            'parent_id' => $parentObjetivoId,
            'tipo_nodo' => $tipoObjetivo,
            'descripcion' => '[Pendiente de transformación] ' . $nodoProblema->descripcion,
            'nodo_origen_id' => $nodoProblema->id,
            'orden' => $nodoProblema->orden,
        ]);

        // Procesar hijos
        foreach ($nodoProblema->children()->orderBy('orden')->get() as $hijo) {
            $this->transformarNodo($hijo, $nodoObjetivo->id);
        }
    }

    public function transformarConIa(int $nodoObjetivoId): void
    {
        $nodoObj = ArbolNodo::findOrFail($nodoObjetivoId);
        $nodoOrigen = $nodoObj->nodoOrigen;

        if (!$nodoOrigen) {
            return;
        }

        $this->transformandoConIa = true;

        try {
            $llm = app(LlmServiceInterface::class);

            $tipoOriginal = is_string($nodoOrigen->tipo_nodo)
                ? $nodoOrigen->tipo_nodo
                : $nodoOrigen->tipo_nodo->value;

            $promptText = view('prompts.mml.transformar-a-positivo', [
                'tipoOriginal' => $tipoOriginal,
                'textoNegativo' => $nodoOrigen->descripcion,
            ])->render();

            $result = $llm->transform($nodoOrigen->descripcion, $promptText);

            $nodoObj->update(['descripcion' => $result]);
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo transformar con IA. Edita manualmente.');
        } finally {
            $this->transformandoConIa = false;
        }
    }

    public function editarNodo(int $nodoId): void
    {
        $nodo = ArbolNodo::findOrFail($nodoId);
        $this->editNodoId = $nodoId;
        $this->editDescripcion = $nodo->descripcion;
    }

    public function cancelarEdicion(): void
    {
        $this->editNodoId = null;
        $this->editDescripcion = '';
    }

    public function guardarEdicion(): void
    {
        $this->validate([
            'editDescripcion' => 'required|min:10|max:500',
        ]);

        $nodo = ArbolNodo::findOrFail($this->editNodoId);
        $nodo->update(['descripcion' => $this->editDescripcion]);
        $this->cancelarEdicion();
    }

    public function render()
    {
        $paresNodos = [];

        if ($this->arbolObjetivos) {
            $nodosObjetivo = $this->arbolObjetivos->nodos()
                ->with('nodoOrigen')
                ->orderBy('orden')
                ->get();

            foreach ($nodosObjetivo as $nodoObj) {
                $paresNodos[] = [
                    'objetivo' => $nodoObj,
                    'problema' => $nodoObj->nodoOrigen,
                ];
            }
        }

        return view('livewire.mml.arbol-objetivos-builder', [
            'paresNodos' => $paresNodos,
        ]);
    }
}
```

**Step 5: Crear vista Blade**

Crear `resources/views/livewire/mml/arbol-objetivos-builder.blade.php`:

```blade
<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 3 — Árbol de Objetivos"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre],
        ['label' => 'Etapa 3: Árbol de Objetivos'],
    ]">
        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @if (!$arbolProblema)
            <div class="rounded-md bg-yellow-50 p-4">
                <p class="text-sm text-yellow-700">
                    Primero debes completar el árbol de problemas en la
                    <a href="{{ route('mml.etapa2', $programa) }}" class="font-medium underline">Etapa 2</a>.
                </p>
            </div>
        @else
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Cada nodo del árbol de problemas se transforma en su equivalente positivo.
                    Revisa y aprueba cada transformación, o usa la IA para sugerir la redacción.
                </p>

                {{-- Vista lado a lado --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <h3 class="text-sm font-semibold text-red-700 uppercase tracking-wide">Problema (Original)</h3>
                    </div>
                    <div class="text-center">
                        <h3 class="text-sm font-semibold text-green-700 uppercase tracking-wide">Objetivo (Transformado)</h3>
                    </div>
                </div>

                @foreach ($paresNodos as $par)
                    @php
                        $nodoObj = $par['objetivo'];
                        $nodoProb = $par['problema'];
                        $tipoEnum = \App\Enums\TipoNodo::tryFrom($nodoObj->tipo_nodo->value ?? $nodoObj->tipo_nodo);
                        $esPendiente = str_starts_with($nodoObj->descripcion, '[Pendiente');
                    @endphp

                    <div class="grid grid-cols-2 gap-4 items-start">
                        {{-- Columna Problema --}}
                        <div class="rounded-md border border-red-200 bg-red-50 p-3">
                            <span class="text-xs font-semibold uppercase text-red-500">
                                {{ $nodoProb ? (\App\Enums\TipoNodo::tryFrom($nodoProb->tipo_nodo->value ?? $nodoProb->tipo_nodo)?->label() ?? '') : 'N/A' }}
                            </span>
                            <p class="mt-1 text-sm text-red-800 line-through">
                                {{ $nodoProb?->descripcion ?? 'Sin origen' }}
                            </p>
                        </div>

                        {{-- Columna Objetivo --}}
                        <div class="rounded-md border {{ $esPendiente ? 'border-yellow-300 bg-yellow-50' : 'border-green-200 bg-green-50' }} p-3">
                            <span class="text-xs font-semibold uppercase {{ $esPendiente ? 'text-yellow-600' : 'text-green-600' }}">
                                {{ $tipoEnum?->label() ?? '' }}
                            </span>

                            @if ($editNodoId === $nodoObj->id)
                                {{-- Modo edición --}}
                                <textarea
                                    wire:model="editDescripcion"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                ></textarea>
                                @error('editDescripcion')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-2 flex gap-2">
                                    <button wire:click="guardarEdicion" class="text-xs text-green-700 font-medium">Guardar</button>
                                    <button wire:click="cancelarEdicion" class="text-xs text-gray-500">Cancelar</button>
                                </div>
                            @else
                                <p class="mt-1 text-sm {{ $esPendiente ? 'text-yellow-800 italic' : 'text-green-800' }}">
                                    {{ $nodoObj->descripcion }}
                                </p>
                                <div class="mt-2 flex gap-2">
                                    <button
                                        wire:click="editarNodo({{ $nodoObj->id }})"
                                        class="text-xs text-indigo-600 hover:text-indigo-800"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        wire:click="transformarConIa({{ $nodoObj->id }})"
                                        wire:loading.attr="disabled"
                                        class="text-xs text-purple-600 hover:text-purple-800 disabled:opacity-50"
                                    >
                                        <span wire:loading.remove wire:target="transformarConIa({{ $nodoObj->id }})">Transformar con IA</span>
                                        <span wire:loading wire:target="transformarConIa({{ $nodoObj->id }})">Transformando...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Navegación --}}
            <x-page.form-footer>
                <x-ui.button.secondary href="{{ route('mml.etapa2', $programa) }}">
                    Etapa anterior
                </x-ui.button.secondary>
                <x-ui.button.primary href="{{ route('mml.etapa4', $programa) }}">
                    Siguiente etapa
                </x-ui.button.primary>
            </x-page.form-footer>
        @endif
    </x-page.container>
</div>
```

**Step 6: Ejecutar tests para verificar que pasan**

```bash
sail artisan test --filter=ArbolObjetivosBuilderTest
```

Expected: PASS.

**Step 7: Commit**

```bash
git add app/Livewire/Mml/ArbolObjetivosBuilder.php resources/views/livewire/mml/arbol-objetivos-builder.blade.php resources/views/prompts/mml/transformar-a-positivo.blade.php tests/Feature/Mml/ArbolObjetivosBuilderTest.php
git commit -m "feat(S3-T6): implement Etapa 3 — Árbol de Objetivos with auto-transform and IA"
```

---

## Criterios de Aceptación

- [ ] Transformación automática genera todos los nodos
- [ ] Vinculación `nodo_origen_id` entre árbol de problemas y objetivos
- [ ] Usuario puede editar cada nodo transformado
- [ ] IA sugiere redacción positiva cuando la transformación es ambigua
- [ ] Vista lado a lado: árbol de problemas vs árbol de objetivos
- [ ] No regenera si ya existe el árbol de objetivos
- [ ] Tests pasan
