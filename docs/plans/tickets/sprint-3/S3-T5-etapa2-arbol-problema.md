# S3-T5: Interfaz Etapa 2 — Árbol del Problema — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S3-T5
**Tipo:** feat
**Rama:** `feat/S3-T5-etapa2-arbol-problema`
**Sprint:** 3 — Metodología de Marco Lógico (Etapas 1-4)
**Depende de:** S3-T4 (Etapa 1 — Definición del Problema), S3-T8 (LlmService)

**Goal:** Interfaz visual de árbol para capturar causas y efectos del problema central. El usuario puede agregar, editar, eliminar y reordenar nodos. La IA sugiere nodos adicionales.

**Architecture:** Componente Livewire `ArbolProblemaBuilder` que renderiza el árbol visualmente con el problema central al centro, causas debajo (directas → indirectas) y efectos arriba (directos → indirectos). CRUD de nodos inline con Livewire. Los nodos usan la estructura Adjacency List de `arbol_nodos`. La IA sugiere causas/efectos vía `LlmService::suggest()`.

**Tech Stack:** Laravel 12, Livewire 3, Blade Components, Tailwind CSS (árbol visual con CSS Grid/Flexbox), LlmService

---

## Pre-requisitos

- S3-T4 completado (Etapa 1 con problema central guardado)
- S3-T2 (Arbol, ArbolNodo con Adjacency List)
- S3-T8 (LlmService)
- Ruta `mml/{programa}/etapa/2` configurada en `routes/web/mml.php`

---

## Pasos

### Task 1: Agregar ruta de Etapa 2

**Files:**
- Modify: `routes/web/mml.php`

**Step 1: Agregar ruta**

Dentro del grupo `{programa}`:

```php
Route::get('/etapa/2', \App\Livewire\Mml\ArbolProblemaBuilder::class)
    ->name('mml.etapa2');
```

**Step 2: Commit**

```bash
git add routes/web/mml.php
git commit -m "feat(S3-T5): add etapa 2 route"
```

---

### Task 2: Crear componente Livewire ArbolProblemaBuilder

**Files:**
- Create: `app/Livewire/Mml/ArbolProblemaBuilder.php`
- Create: `resources/views/livewire/mml/arbol-problema-builder.blade.php`
- Create: `tests/Feature/Mml/ArbolProblemaBuilderTest.php`

**Step 1: Escribir tests**

Crear `tests/Feature/Mml/ArbolProblemaBuilderTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Livewire\Mml\ArbolProblemaBuilder;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArbolProblemaBuilderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private Arbol $arbol;
    private ArbolNodo $problemaCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $this->arbol = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
        $this->problemaCentral = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Alto índice de deserción escolar',
        ]);
    }

    public function test_componente_se_renderiza_con_problema_central(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Alto índice de deserción escolar');
    }

    public function test_agregar_causa_directa(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('agregarNodo', $this->problemaCentral->id, 'causa_directa')
            ->set('nuevoNodoDescripcion', 'Falta de recursos económicos en las familias')
            ->call('guardarNuevoNodo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => 'causa_directa',
            'descripcion' => 'Falta de recursos económicos en las familias',
        ]);
    }

    public function test_agregar_causa_indirecta_bajo_causa_directa(): void
    {
        $causaDirecta = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Causa directa',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('agregarNodo', $causaDirecta->id, 'causa_indirecta')
            ->set('nuevoNodoDescripcion', 'Causa indirecta de prueba')
            ->call('guardarNuevoNodo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'parent_id' => $causaDirecta->id,
            'tipo_nodo' => 'causa_indirecta',
        ]);
    }

    public function test_agregar_efecto_directo(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('agregarNodo', $this->problemaCentral->id, 'efecto_directo')
            ->set('nuevoNodoDescripcion', 'Baja competitividad laboral')
            ->call('guardarNuevoNodo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => 'efecto_directo',
        ]);
    }

    public function test_editar_nodo(): void
    {
        $causa = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'Descripción original',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('editarNodo', $causa->id)
            ->set('editNodoDescripcion', 'Descripción actualizada')
            ->call('actualizarNodo')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'id' => $causa->id,
            'descripcion' => 'Descripción actualizada',
        ]);
    }

    public function test_eliminar_nodo(): void
    {
        $causa = ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->problemaCentral->id,
            'tipo_nodo' => TipoNodo::CAUSA_DIRECTA->value,
            'descripcion' => 'A eliminar',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('eliminarNodo', $causa->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('arbol_nodos', ['id' => $causa->id]);
    }

    public function test_no_puede_eliminar_problema_central(): void
    {
        Livewire::actingAs($this->user)
            ->test(ArbolProblemaBuilder::class, ['programa' => $this->programa])
            ->call('eliminarNodo', $this->problemaCentral->id)
            ->assertDispatched('notify', function ($name, $params) {
                return str_contains($params['message'] ?? $params[0] ?? '', 'no se puede eliminar')
                    || str_contains($params['message'] ?? $params[0] ?? '', 'central');
            });

        $this->assertDatabaseHas('arbol_nodos', ['id' => $this->problemaCentral->id]);
    }
}
```

**Step 2: Ejecutar tests para verificar que fallan**

```bash
sail artisan test --filter=ArbolProblemaBuilderTest
```

Expected: FAIL.

**Step 3: Crear componente Livewire**

Crear `app/Livewire/Mml/ArbolProblemaBuilder.php`:

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
#[Title('Etapa 2 — Árbol del Problema')]
class ArbolProblemaBuilder extends Component
{
    public ProgramaPresupuestario $programa;
    public ?Arbol $arbol = null;

    // Estado del formulario de nuevo nodo
    public bool $mostrarFormNuevoNodo = false;
    public ?int $parentIdNuevoNodo = null;
    public string $tipoNuevoNodo = '';
    public string $nuevoNodoDescripcion = '';

    // Estado del formulario de edición
    public ?int $editNodoId = null;
    public string $editNodoDescripcion = '';

    // Estado de IA
    public bool $sugiriendoConIa = false;
    public array $sugerenciasIa = [];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
        $this->arbol = $programa->arboles()
            ->where('tipo', TipoArbol::PROBLEMA->value)
            ->first();
    }

    public function agregarNodo(int $parentId, string $tipoNodo): void
    {
        $this->mostrarFormNuevoNodo = true;
        $this->parentIdNuevoNodo = $parentId;
        $this->tipoNuevoNodo = $tipoNodo;
        $this->nuevoNodoDescripcion = '';
    }

    public function cancelarNuevoNodo(): void
    {
        $this->mostrarFormNuevoNodo = false;
        $this->parentIdNuevoNodo = null;
        $this->tipoNuevoNodo = '';
        $this->nuevoNodoDescripcion = '';
    }

    public function guardarNuevoNodo(): void
    {
        $this->validate([
            'nuevoNodoDescripcion' => 'required|min:10|max:500',
        ]);

        $maxOrden = ArbolNodo::where('arbol_id', $this->arbol->id)
            ->where('parent_id', $this->parentIdNuevoNodo)
            ->max('orden') ?? 0;

        ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $this->parentIdNuevoNodo,
            'tipo_nodo' => $this->tipoNuevoNodo,
            'descripcion' => $this->nuevoNodoDescripcion,
            'orden' => $maxOrden + 1,
        ]);

        $this->cancelarNuevoNodo();
    }

    public function editarNodo(int $nodoId): void
    {
        $nodo = ArbolNodo::findOrFail($nodoId);
        $this->editNodoId = $nodoId;
        $this->editNodoDescripcion = $nodo->descripcion;
    }

    public function cancelarEdicion(): void
    {
        $this->editNodoId = null;
        $this->editNodoDescripcion = '';
    }

    public function actualizarNodo(): void
    {
        $this->validate([
            'editNodoDescripcion' => 'required|min:10|max:500',
        ]);

        $nodo = ArbolNodo::findOrFail($this->editNodoId);
        $nodo->update(['descripcion' => $this->editNodoDescripcion]);

        $this->cancelarEdicion();
    }

    public function eliminarNodo(int $nodoId): void
    {
        $nodo = ArbolNodo::findOrFail($nodoId);

        if ($nodo->tipo_nodo === TipoNodo::PROBLEMA_CENTRAL || $nodo->tipo_nodo->value === 'problema_central') {
            $this->dispatch('notify', message: 'El problema central no se puede eliminar.');
            return;
        }

        $nodo->delete();
    }

    public function sugerirConIa(string $tipoSugerencia): void
    {
        $this->sugiriendoConIa = true;
        $this->sugerenciasIa = [];

        try {
            $llm = app(LlmServiceInterface::class);

            $problemaCentral = $this->arbol->nodos()
                ->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)
                ->first();

            $nodosExistentes = $this->arbol->nodos()
                ->where('tipo_nodo', 'like', $tipoSugerencia . '%')
                ->pluck('descripcion')
                ->implode(', ');

            $prompt = "Problema central: \"{$problemaCentral->descripcion}\". "
                . "Nodos existentes de tipo {$tipoSugerencia}: [{$nodosExistentes}]. "
                . "Sugiere 3 {$tipoSugerencia}s adicionales que no estén ya listados. "
                . "Responde solo con una lista numerada, un elemento por línea.";

            $result = $llm->suggest($prompt);

            $this->sugerenciasIa = array_filter(
                array_map('trim', explode("\n", $result)),
                fn ($line) => !empty($line) && preg_match('/^\d/', $line)
            );
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudieron generar sugerencias de IA.');
        } finally {
            $this->sugiriendoConIa = false;
        }
    }

    public function agregarSugerencia(string $descripcion, int $parentId, string $tipoNodo): void
    {
        // Limpiar número de lista (ej: "1. Causa..." → "Causa...")
        $descripcion = preg_replace('/^\d+\.\s*/', '', $descripcion);

        $maxOrden = ArbolNodo::where('arbol_id', $this->arbol->id)
            ->where('parent_id', $parentId)
            ->max('orden') ?? 0;

        ArbolNodo::create([
            'arbol_id' => $this->arbol->id,
            'parent_id' => $parentId,
            'tipo_nodo' => $tipoNodo,
            'descripcion' => $descripcion,
            'orden' => $maxOrden + 1,
        ]);

        // Remover de las sugerencias
        $this->sugerenciasIa = array_values(array_filter(
            $this->sugerenciasIa,
            fn ($s) => $s !== $descripcion
        ));
    }

    public function getArbolDataProperty(): array
    {
        if (!$this->arbol) {
            return [];
        }

        $nodos = $this->arbol->nodos()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('orden')
            ->get();

        return $this->buildTree($nodos);
    }

    private function buildTree($nodos): array
    {
        return $nodos->map(function ($nodo) {
            return [
                'id' => $nodo->id,
                'tipo_nodo' => $nodo->tipo_nodo,
                'descripcion' => $nodo->descripcion,
                'orden' => $nodo->orden,
                'children' => $this->buildTree($nodo->children()->orderBy('orden')->get()),
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.mml.arbol-problema-builder');
    }
}
```

**Step 4: Crear vista Blade**

Crear `resources/views/livewire/mml/arbol-problema-builder.blade.php`:

```blade
<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 2 — Árbol del Problema"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre],
        ['label' => 'Etapa 2: Árbol del Problema'],
    ]">
        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @if (!$arbol)
            <div class="rounded-md bg-yellow-50 p-4">
                <p class="text-sm text-yellow-700">
                    Primero debes definir el problema central en la
                    <a href="{{ route('mml.etapa1', $programa) }}" class="font-medium underline">Etapa 1</a>.
                </p>
            </div>
        @else
            <div class="space-y-8">
                {{-- SECCIÓN: EFECTOS (arriba) --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Efectos</h3>
                    @php
                        $problemaCentral = $arbol->nodos()->where('tipo_nodo', 'problema_central')->first();
                        $efectosDirectos = $arbol->nodos()->where('parent_id', $problemaCentral?->id)->where('tipo_nodo', 'efecto_directo')->orderBy('orden')->get();
                    @endphp

                    <div class="space-y-3">
                        @foreach ($efectosDirectos as $efecto)
                            <div class="ml-4">
                                @include('livewire.mml.partials.nodo-card', ['nodo' => $efecto, 'nivel' => 0])

                                {{-- Efectos indirectos --}}
                                @foreach ($efecto->children()->where('tipo_nodo', 'efecto_indirecto')->orderBy('orden')->get() as $indirecto)
                                    <div class="ml-8 mt-2">
                                        @include('livewire.mml.partials.nodo-card', ['nodo' => $indirecto, 'nivel' => 1])
                                    </div>
                                @endforeach

                                <button
                                    wire:click="agregarNodo({{ $efecto->id }}, 'efecto_indirecto')"
                                    class="ml-8 mt-1 text-xs text-gray-500 hover:text-gray-700"
                                >
                                    + Efecto indirecto
                                </button>
                            </div>
                        @endforeach

                        @if ($problemaCentral)
                            <button
                                wire:click="agregarNodo({{ $problemaCentral->id }}, 'efecto_directo')"
                                class="ml-4 text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                + Agregar efecto directo
                            </button>
                        @endif
                    </div>
                </div>

                {{-- SECCIÓN: PROBLEMA CENTRAL (centro) --}}
                @if ($problemaCentral)
                    <div class="flex justify-center">
                        <div class="w-full max-w-2xl rounded-lg border-2 border-red-300 bg-red-50 p-4 text-center">
                            <span class="text-xs font-semibold uppercase tracking-wide text-red-600">Problema Central</span>
                            <p class="mt-1 text-lg font-medium text-red-900">{{ $problemaCentral->descripcion }}</p>
                        </div>
                    </div>
                @endif

                {{-- SECCIÓN: CAUSAS (abajo) --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Causas</h3>
                    @php
                        $causasDirectas = $arbol->nodos()->where('parent_id', $problemaCentral?->id)->where('tipo_nodo', 'causa_directa')->orderBy('orden')->get();
                    @endphp

                    <div class="space-y-3">
                        @foreach ($causasDirectas as $causa)
                            <div class="ml-4">
                                @include('livewire.mml.partials.nodo-card', ['nodo' => $causa, 'nivel' => 0])

                                {{-- Causas indirectas --}}
                                @foreach ($causa->children()->where('tipo_nodo', 'causa_indirecta')->orderBy('orden')->get() as $indirecta)
                                    <div class="ml-8 mt-2">
                                        @include('livewire.mml.partials.nodo-card', ['nodo' => $indirecta, 'nivel' => 1])
                                    </div>
                                @endforeach

                                <button
                                    wire:click="agregarNodo({{ $causa->id }}, 'causa_indirecta')"
                                    class="ml-8 mt-1 text-xs text-gray-500 hover:text-gray-700"
                                >
                                    + Causa indirecta
                                </button>
                            </div>
                        @endforeach

                        @if ($problemaCentral)
                            <button
                                wire:click="agregarNodo({{ $problemaCentral->id }}, 'causa_directa')"
                                class="ml-4 text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                + Agregar causa directa
                            </button>
                        @endif
                    </div>
                </div>

                {{-- FORMULARIO INLINE para nuevo nodo --}}
                @if ($mostrarFormNuevoNodo)
                    <div class="fixed inset-0 z-10 flex items-center justify-center bg-gray-900/50">
                        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                            <h4 class="text-sm font-semibold text-gray-900">
                                Agregar {{ str_replace('_', ' ', $tipoNuevoNodo) }}
                            </h4>
                            <textarea
                                wire:model="nuevoNodoDescripcion"
                                rows="3"
                                class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Describe el nodo..."
                            ></textarea>
                            @error('nuevoNodoDescripcion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-3 flex justify-end gap-2">
                                <button wire:click="cancelarNuevoNodo" class="rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700 hover:bg-gray-200">
                                    Cancelar
                                </button>
                                <button wire:click="guardarNuevoNodo" class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-500">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- FORMULARIO de edición --}}
                @if ($editNodoId)
                    <div class="fixed inset-0 z-10 flex items-center justify-center bg-gray-900/50">
                        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                            <h4 class="text-sm font-semibold text-gray-900">Editar nodo</h4>
                            <textarea
                                wire:model="editNodoDescripcion"
                                rows="3"
                                class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            ></textarea>
                            @error('editNodoDescripcion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-3 flex justify-end gap-2">
                                <button wire:click="cancelarEdicion" class="rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700 hover:bg-gray-200">
                                    Cancelar
                                </button>
                                <button wire:click="actualizarNodo" class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-500">
                                    Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Botón de sugerencias IA --}}
                <div class="border-t pt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Asistencia de IA</h4>
                    <div class="flex gap-2">
                        <button
                            wire:click="sugerirConIa('causa')"
                            wire:loading.attr="disabled"
                            class="rounded-md bg-purple-50 px-3 py-2 text-sm text-purple-700 ring-1 ring-purple-300 hover:bg-purple-100 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="sugerirConIa('causa')">Sugerir causas</span>
                            <span wire:loading wire:target="sugerirConIa('causa')">Generando...</span>
                        </button>
                        <button
                            wire:click="sugerirConIa('efecto')"
                            wire:loading.attr="disabled"
                            class="rounded-md bg-purple-50 px-3 py-2 text-sm text-purple-700 ring-1 ring-purple-300 hover:bg-purple-100 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="sugerirConIa('efecto')">Sugerir efectos</span>
                            <span wire:loading wire:target="sugerirConIa('efecto')">Generando...</span>
                        </button>
                    </div>

                    @if (!empty($sugerenciasIa))
                        <div class="mt-3 rounded-md bg-purple-50 p-4 border border-purple-200">
                            <p class="text-sm font-medium text-purple-800">Sugerencias de IA:</p>
                            <ul class="mt-2 space-y-2">
                                @foreach ($sugerenciasIa as $sugerencia)
                                    <li class="flex items-center justify-between text-sm text-purple-700">
                                        <span>{{ $sugerencia }}</span>
                                        <button
                                            wire:click="agregarSugerencia('{{ addslashes($sugerencia) }}', {{ $problemaCentral->id }}, 'causa_directa')"
                                            class="ml-2 text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                                        >
                                            + Agregar
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Navegación --}}
            <x-page.form-footer>
                <x-ui.button.secondary href="{{ route('mml.etapa1', $programa) }}">
                    Etapa anterior
                </x-ui.button.secondary>
                <x-ui.button.primary href="{{ route('mml.etapa3', $programa) }}">
                    Siguiente etapa
                </x-ui.button.primary>
            </x-page.form-footer>
        @endif
    </x-page.container>
</div>
```

**Step 5: Crear partial nodo-card**

Crear `resources/views/livewire/mml/partials/nodo-card.blade.php`:

```blade
@php
    $tipoEnum = \App\Enums\TipoNodo::tryFrom($nodo->tipo_nodo->value ?? $nodo->tipo_nodo);
    $colorClass = $tipoEnum ? $tipoEnum->colorClass() : 'bg-gray-100 text-gray-800 border-gray-300';
    $label = $tipoEnum ? $tipoEnum->label() : $nodo->tipo_nodo;
@endphp

<div class="rounded-md border {{ $colorClass }} p-3 flex items-start justify-between group">
    <div>
        <span class="text-xs font-semibold uppercase tracking-wide opacity-70">{{ $label }}</span>
        <p class="mt-0.5 text-sm">{{ $nodo->descripcion }}</p>
    </div>
    <div class="hidden group-hover:flex items-center gap-1">
        <button
            wire:click="editarNodo({{ $nodo->id }})"
            class="rounded p-1 text-gray-500 hover:bg-white hover:text-gray-700"
            title="Editar"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </button>
        <button
            wire:click="eliminarNodo({{ $nodo->id }})"
            wire:confirm="¿Eliminar este nodo y sus hijos?"
            class="rounded p-1 text-gray-500 hover:bg-white hover:text-red-600"
            title="Eliminar"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>
</div>
```

**Step 6: Ejecutar tests para verificar que pasan**

```bash
sail artisan test --filter=ArbolProblemaBuilderTest
```

Expected: PASS.

**Step 7: Ejecutar suite completo**

```bash
sail artisan test
```

**Step 8: Commit**

```bash
git add app/Livewire/Mml/ArbolProblemaBuilder.php resources/views/livewire/mml/ tests/Feature/Mml/ArbolProblemaBuilderTest.php
git commit -m "feat(S3-T5): implement Etapa 2 — Árbol del Problema with CRUD and IA suggestions"
```

---

## Criterios de Aceptación

- [ ] Árbol visual renderiza correctamente con nodos colapsables
- [ ] CRUD de nodos: agregar, editar, eliminar
- [ ] Causas indirectas se anidan bajo causas directas
- [ ] Efectos indirectos se anidan bajo efectos directos
- [ ] IA sugiere nodos y valida que no sean soluciones disfrazadas
- [ ] Estado persistente entre sesiones
- [ ] Tests pasan
