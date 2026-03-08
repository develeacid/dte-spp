# S3-T4: Interfaz Etapa 1 — Definición del Problema — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S3-T4
**Tipo:** feat
**Rama:** `feat/S3-T4-etapa1-definicion-problema`
**Sprint:** 3 — Metodología de Marco Lógico (Etapas 1-4)
**Depende de:** S3-T1 (Programa), S3-T2 (Árboles), S3-T8 (LlmService)

**Goal:** Componente Livewire para capturar el problema central de un programa presupuestario. El usuario escribe la situación no deseada y opcionalmente la valida con IA, que sugiere mejoras de redacción.

**Architecture:** Componente Livewire `DefinicionProblema` que recibe un `ProgramaPresupuestario`. Al guardar, crea el árbol de tipo `problema` y su nodo central. La validación IA usa `LlmService::validate()` con el prompt Blade `prompts.mml.validar-problema`. El flujo es: página completa (no modal) bajo la ruta `mml/{programa}/etapa/1`.

**Tech Stack:** Laravel 12, Livewire 3, Blade Components (x-page.*, x-forms.*), LlmService

---

## Pre-requisitos

- S3-T1 (ProgramaPresupuestario extendido)
- S3-T2 (Arbol, ArbolNodo, TipoNodo)
- S3-T8 (LlmService, prompts Blade)
- Archivo de rutas `routes/web/mml.php` (crear si no existe)

---

## Pasos

### Task 1: Crear archivo de rutas MML

**Files:**
- Create: `routes/web/mml.php`
- Modify: `routes/web.php` (agregar require)

**Step 1: Crear routes/web/mml.php**

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('mml')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {

        // Etapas del MML para un programa
        Route::prefix('{programa}')
            ->group(function () {
                Route::get('/etapa/1', \App\Livewire\Mml\DefinicionProblema::class)
                    ->name('mml.etapa1');
                // Futuras etapas se agregarán aquí
            });
    });
```

**Step 2: Agregar require en web.php**

Agregar dentro del grupo de middleware autenticado:

```php
require __DIR__ . '/web/mml.php';
```

**Step 3: Commit**

```bash
git add routes/web/mml.php routes/web.php
git commit -m "feat(S3-T4): add MML routes file with etapa 1 route"
```

---

### Task 2: Crear componente Livewire DefinicionProblema

**Files:**
- Create: `app/Livewire/Mml/DefinicionProblema.php`
- Create: `resources/views/livewire/mml/definicion-problema.blade.php`

**Step 1: Escribir test del componente**

Crear `tests/Feature/Mml/DefinicionProblemaTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Livewire\Mml\DefinicionProblema;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DefinicionProblemaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 1');
    }

    public function test_guardar_problema_central(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Alto índice de deserción escolar en el estado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arboles', [
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => 'problema',
        ]);

        $this->assertDatabaseHas('arbol_nodos', [
            'tipo_nodo' => 'problema_central',
            'descripcion' => 'Alto índice de deserción escolar en el estado',
        ]);
    }

    public function test_validacion_descripcion_requerida(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', '')
            ->call('guardar')
            ->assertHasErrors(['descripcion' => 'required']);
    }

    public function test_validacion_descripcion_minimo_caracteres(): void
    {
        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Corto')
            ->call('guardar')
            ->assertHasErrors(['descripcion' => 'min']);
    }

    public function test_carga_problema_existente(): void
    {
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Problema existente guardado',
        ]);

        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->assertSet('descripcion', 'Problema existente guardado');
    }

    public function test_actualiza_problema_existente_al_guardar(): void
    {
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::PROBLEMA->value,
        ]);
        $nodo = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            'descripcion' => 'Versión anterior',
        ]);

        Livewire::actingAs($this->user)
            ->test(DefinicionProblema::class, ['programa' => $this->programa])
            ->set('descripcion', 'Versión actualizada del problema')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('arbol_nodos', [
            'id' => $nodo->id,
            'descripcion' => 'Versión actualizada del problema',
        ]);
    }
}
```

**Step 2: Ejecutar tests para verificar que fallan**

```bash
sail artisan test --filter=DefinicionProblemaTest
```

Expected: FAIL.

**Step 3: Crear componente Livewire**

Crear `app/Livewire/Mml/DefinicionProblema.php`:

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
#[Title('Etapa 1 — Definición del Problema')]
class DefinicionProblema extends Component
{
    public ProgramaPresupuestario $programa;
    public string $descripcion = '';
    public string $sugerenciaIa = '';
    public bool $validandoConIa = false;
    public ?array $resultadoValidacion = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        // Cargar problema existente si lo hay
        $arbol = $programa->arboles()->where('tipo', TipoArbol::PROBLEMA->value)->first();
        if ($arbol) {
            $nodoCentral = $arbol->nodos()->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)->first();
            if ($nodoCentral) {
                $this->descripcion = $nodoCentral->descripcion;
            }
        }
    }

    public function validarConIa(): void
    {
        $this->validate([
            'descripcion' => 'required|min:20',
        ]);

        $this->validandoConIa = true;
        $this->resultadoValidacion = null;
        $this->sugerenciaIa = '';

        try {
            $llm = app(LlmServiceInterface::class);
            $promptText = view('prompts.mml.validar-problema', ['texto' => $this->descripcion])->render();
            $result = $llm->validate($this->descripcion, ['no_verbos_solucion', 'situacion_no_deseada', 'claro_concreto']);

            $this->resultadoValidacion = $result->toArray();
            $this->sugerenciaIa = $result->suggestion;
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo conectar con el servicio de IA. Puedes continuar sin validación.');
        } finally {
            $this->validandoConIa = false;
        }
    }

    public function aceptarSugerencia(): void
    {
        if (!empty($this->sugerenciaIa)) {
            $this->descripcion = $this->sugerenciaIa;
            $this->sugerenciaIa = '';
            $this->resultadoValidacion = null;
        }
    }

    public function guardar(): void
    {
        $this->validate([
            'descripcion' => 'required|min:20|max:1000',
        ]);

        $arbol = Arbol::firstOrCreate(
            [
                'programa_presupuestario_id' => $this->programa->id,
                'tipo' => TipoArbol::PROBLEMA->value,
            ]
        );

        ArbolNodo::updateOrCreate(
            [
                'arbol_id' => $arbol->id,
                'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            ],
            [
                'descripcion' => $this->descripcion,
            ]
        );

        session()->flash('success', 'Problema central guardado correctamente.');
    }

    public function render()
    {
        return view('livewire.mml.definicion-problema');
    }
}
```

**Step 4: Crear vista Blade**

Crear `resources/views/livewire/mml/definicion-problema.blade.php`:

```blade
<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 1 — Definición del Problema"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre],
        ['label' => 'Etapa 1: Problema'],
    ]">
        {{-- Mensajes flash --}}
        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        <x-forms.section
            title="Problema Central"
            description="Describe la situación no deseada que el programa busca atender. Debe ser una condición negativa, no la ausencia de una solución."
        >
            <div class="space-y-4">
                {{-- Textarea con contador --}}
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">
                        Descripción del problema
                    </label>
                    <textarea
                        wire:model="descripcion"
                        id="descripcion"
                        rows="4"
                        maxlength="1000"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Ej: Alto índice de deserción escolar en educación media superior en el estado"
                    ></textarea>
                    <div class="mt-1 flex justify-between">
                        <div>
                            @error('descripcion')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ strlen($descripcion) }}/1000 caracteres
                        </p>
                    </div>
                </div>

                {{-- Botón validar con IA --}}
                <div class="flex items-center gap-3">
                    <button
                        wire:click="validarConIa"
                        wire:loading.attr="disabled"
                        wire:target="validarConIa"
                        type="button"
                        class="inline-flex items-center rounded-md bg-purple-50 px-3 py-2 text-sm font-semibold text-purple-700 ring-1 ring-inset ring-purple-300 hover:bg-purple-100 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="validarConIa">
                            Validar con IA
                        </span>
                        <span wire:loading wire:target="validarConIa">
                            Validando...
                        </span>
                    </button>
                    <span class="text-xs text-gray-500">Opcional — la IA sugiere mejoras de redacción</span>
                </div>

                {{-- Resultado de validación IA --}}
                @if ($resultadoValidacion)
                    <div class="rounded-md {{ $resultadoValidacion['is_valid'] ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }} border p-4">
                        <h4 class="text-sm font-medium {{ $resultadoValidacion['is_valid'] ? 'text-green-800' : 'text-amber-800' }}">
                            {{ $resultadoValidacion['is_valid'] ? 'Redacción válida' : 'Se encontraron observaciones' }}
                        </h4>

                        @if (!empty($resultadoValidacion['issues']))
                            <ul class="mt-2 list-disc list-inside text-sm text-amber-700">
                                @foreach ($resultadoValidacion['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if (!empty($sugerenciaIa))
                            <div class="mt-3 rounded bg-white p-3 border border-gray-200">
                                <p class="text-sm font-medium text-gray-700">Sugerencia:</p>
                                <p class="mt-1 text-sm text-gray-600 italic">{{ $sugerenciaIa }}</p>
                                <button
                                    wire:click="aceptarSugerencia"
                                    type="button"
                                    class="mt-2 inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-300 hover:bg-indigo-100"
                                >
                                    Usar esta sugerencia
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </x-forms.section>

        {{-- Footer --}}
        <x-page.form-footer>
            <x-ui.button.secondary href="{{ route('dashboard') }}">
                Cancelar
            </x-ui.button.secondary>
            <x-ui.button.primary wire:click="guardar" wire:loading.attr="disabled">
                Guardar Problema
            </x-ui.button.primary>
        </x-page.form-footer>
    </x-page.container>
</div>
```

**Step 5: Ejecutar tests para verificar que pasan**

```bash
sail artisan test --filter=DefinicionProblemaTest
```

Expected: PASS.

**Step 6: Commit**

```bash
git add app/Livewire/Mml/DefinicionProblema.php resources/views/livewire/mml/definicion-problema.blade.php tests/Feature/Mml/DefinicionProblemaTest.php
git commit -m "feat(S3-T4): implement Etapa 1 — Definición del Problema with IA validation"
```

---

## Criterios de Aceptación

- [ ] Textarea con contador de caracteres
- [ ] Botón de asistencia IA (no obligatorio, el usuario puede omitirlo)
- [ ] Respuesta de IA se muestra como sugerencia editable
- [ ] Al confirmar, se crea el árbol de tipo `problema` y el nodo central
- [ ] Estado guardado: el usuario puede salir y retomar
- [ ] Validaciones de formulario (required, min, max)
- [ ] Tests pasan
