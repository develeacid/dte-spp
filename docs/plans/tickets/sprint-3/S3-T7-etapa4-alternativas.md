# S3-T7: Interfaz Etapa 4 — Selección de Alternativas — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Ticket:** S3-T7
**Tipo:** feat
**Rama:** `feat/S3-T7-etapa4-alternativas`
**Sprint:** 3 — Metodología de Marco Lógico (Etapas 1-4)
**Depende de:** S3-T3 (Modelos Alternativas), S3-T6 (Etapa 3 — Árbol de Objetivos), S3-T8 (LlmService)

**Goal:** Interfaz para agrupar los medios del árbol de objetivos en alternativas, evaluar su viabilidad con IA y seleccionar la alternativa final con justificación.

**Architecture:** Componente Livewire `SeleccionAlternativas` que lista los medios (directos e indirectos) del árbol de objetivos. El usuario crea alternativas y les asigna nodos vía checkboxes. La IA evalúa viabilidad de cada alternativa. Al seleccionar la ganadora, los nodos no seleccionados se marcan visualmente como "podados".

**Tech Stack:** Laravel 12, Livewire 3, Blade Components, Tailwind CSS, LlmService

---

## Pre-requisitos

- S3-T3 (Modelo Alternativa con pivote)
- S3-T6 (Árbol de objetivos generado)
- S3-T8 (LlmService)

---

## Pasos

### Task 1: Agregar ruta de Etapa 4

**Files:**
- Modify: `routes/web/mml.php`

**Step 1: Agregar ruta**

```php
Route::get('/etapa/4', \App\Livewire\Mml\SeleccionAlternativas::class)
    ->name('mml.etapa4');
```

**Step 2: Commit**

```bash
git add routes/web/mml.php
git commit -m "feat(S3-T7): add etapa 4 route"
```

---

### Task 2: Crear prompt Blade para evaluación de viabilidad

**Files:**
- Create: `resources/views/prompts/mml/evaluar-alternativa.blade.php`

**Step 1: Crear el prompt**

```blade
Eres un experto en Metodología de Marco Lógico (MML) para el sector público mexicano.

Evalúa la viabilidad de la siguiente alternativa para el programa presupuestario "{{ $programa }}":

ALTERNATIVA: "{{ $nombreAlternativa }}"

MEDIOS INCLUIDOS:
@foreach ($medios as $medio)
- {{ $medio }}
@endforeach

OBJETIVO CENTRAL: "{{ $objetivoCentral }}"

Evalúa en tres dimensiones:
1. **Viabilidad técnica**: ¿Es factible implementar estos medios con la capacidad técnica disponible?
2. **Viabilidad institucional**: ¿Está dentro del mandato y competencias de la institución?
3. **Viabilidad presupuestal**: ¿Es razonable el costo estimado?

Responde en formato JSON:
{
    "viabilidad_tecnica": {"calificacion": "alta|media|baja", "justificacion": "..."},
    "viabilidad_institucional": {"calificacion": "alta|media|baja", "justificacion": "..."},
    "viabilidad_presupuestal": {"calificacion": "alta|media|baja", "justificacion": "..."},
    "recomendacion": "..."
}
```

**Step 2: Commit**

```bash
git add resources/views/prompts/mml/evaluar-alternativa.blade.php
git commit -m "feat(S3-T7): add prompt for alternative viability evaluation"
```

---

### Task 3: Crear componente Livewire SeleccionAlternativas

**Files:**
- Create: `app/Livewire/Mml/SeleccionAlternativas.php`
- Create: `resources/views/livewire/mml/seleccion-alternativas.blade.php`
- Create: `tests/Feature/Mml/SeleccionAlternativasTest.php`

**Step 1: Escribir tests**

Crear `tests/Feature/Mml/SeleccionAlternativasTest.php`:

```php
<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Livewire\Mml\SeleccionAlternativas;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeleccionAlternativasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private Arbol $arbolObjetivos;
    private ArbolNodo $objetivoCentral;
    private ArbolNodo $medio1;
    private ArbolNodo $medio2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->arbolObjetivos = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);
        $this->objetivoCentral = ArbolNodo::create([
            'arbol_id' => $this->arbolObjetivos->id,
            'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL->value,
            'descripcion' => 'Reducir la deserción escolar',
        ]);
        $this->medio1 = ArbolNodo::create([
            'arbol_id' => $this->arbolObjetivos->id,
            'parent_id' => $this->objetivoCentral->id,
            'tipo_nodo' => TipoNodo::MEDIO_DIRECTO->value,
            'descripcion' => 'Programa de becas',
            'orden' => 1,
        ]);
        $this->medio2 = ArbolNodo::create([
            'arbol_id' => $this->arbolObjetivos->id,
            'parent_id' => $this->objetivoCentral->id,
            'tipo_nodo' => TipoNodo::MEDIO_DIRECTO->value,
            'descripcion' => 'Mejora de infraestructura escolar',
            'orden' => 2,
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 4');
    }

    public function test_muestra_medios_del_arbol_objetivos(): void
    {
        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->assertSee('Programa de becas')
            ->assertSee('Mejora de infraestructura escolar');
    }

    public function test_crear_alternativa(): void
    {
        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->set('nuevaAlternativaNombre', 'Alternativa A')
            ->call('crearAlternativa')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alternativas', [
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alternativa A',
        ]);
    }

    public function test_asignar_nodos_a_alternativa(): void
    {
        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A',
        ]);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->call('toggleNodo', $alternativa->id, $this->medio1->id)
            ->assertHasNoErrors();

        $this->assertTrue($alternativa->nodos()->where('arbol_nodo_id', $this->medio1->id)->exists());
    }

    public function test_seleccionar_alternativa_con_justificacion(): void
    {
        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A',
        ]);
        $alternativa->nodos()->attach($this->medio1->id);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->set('justificacionSeleccion', 'Mayor viabilidad técnica y presupuestal')
            ->call('seleccionarAlternativa', $alternativa->id)
            ->assertHasNoErrors();

        $alternativa->refresh();
        $this->assertTrue($alternativa->seleccionada);
        $this->assertEquals('Mayor viabilidad técnica y presupuestal', $alternativa->justificacion_seleccion);
    }

    public function test_justificacion_requerida_para_seleccionar(): void
    {
        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A',
        ]);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->set('justificacionSeleccion', '')
            ->call('seleccionarAlternativa', $alternativa->id)
            ->assertHasErrors(['justificacionSeleccion' => 'required']);
    }

    public function test_solo_una_alternativa_seleccionada(): void
    {
        $alt1 = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A', 'seleccionada' => true,
            'justificacion_seleccion' => 'Primera opción',
        ]);
        $alt2 = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt B',
        ]);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->set('justificacionSeleccion', 'Mejor opción')
            ->call('seleccionarAlternativa', $alt2->id);

        $alt1->refresh();
        $alt2->refresh();

        $this->assertFalse($alt1->seleccionada);
        $this->assertTrue($alt2->seleccionada);
    }

    public function test_eliminar_alternativa(): void
    {
        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A',
        ]);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->call('eliminarAlternativa', $alternativa->id);

        $this->assertDatabaseMissing('alternativas', ['id' => $alternativa->id]);
    }
}
```

**Step 2: Ejecutar tests para verificar que fallan**

```bash
sail artisan test --filter=SeleccionAlternativasTest
```

Expected: FAIL.

**Step 3: Crear componente Livewire**

Crear `app/Livewire/Mml/SeleccionAlternativas.php`:

```php
<?php

namespace App\Livewire\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 4 — Selección de Alternativas')]
class SeleccionAlternativas extends Component
{
    public ProgramaPresupuestario $programa;
    public ?Arbol $arbolObjetivos = null;

    public string $nuevaAlternativaNombre = '';
    public string $justificacionSeleccion = '';

    // IA
    public bool $evaluandoConIa = false;
    public array $evaluacionIa = [];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
        $this->arbolObjetivos = $programa->arboles()
            ->where('tipo', TipoArbol::OBJETIVOS->value)
            ->first();
    }

    public function crearAlternativa(): void
    {
        $this->validate([
            'nuevaAlternativaNombre' => 'required|min:3|max:100',
        ]);

        Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => $this->nuevaAlternativaNombre,
        ]);

        $this->nuevaAlternativaNombre = '';
    }

    public function toggleNodo(int $alternativaId, int $nodoId): void
    {
        $alternativa = Alternativa::findOrFail($alternativaId);

        if ($alternativa->nodos()->where('arbol_nodo_id', $nodoId)->exists()) {
            $alternativa->nodos()->detach($nodoId);
        } else {
            $alternativa->nodos()->attach($nodoId);
        }
    }

    public function seleccionarAlternativa(int $alternativaId): void
    {
        $this->validate([
            'justificacionSeleccion' => 'required|min:20|max:1000',
        ]);

        // Deseleccionar todas las demás
        Alternativa::where('programa_presupuestario_id', $this->programa->id)
            ->update(['seleccionada' => false, 'justificacion_seleccion' => null]);

        // Seleccionar la elegida
        $alternativa = Alternativa::findOrFail($alternativaId);
        $alternativa->update([
            'seleccionada' => true,
            'justificacion_seleccion' => $this->justificacionSeleccion,
        ]);

        $this->justificacionSeleccion = '';
        session()->flash('success', "Alternativa \"{$alternativa->nombre}\" seleccionada.");
    }

    public function eliminarAlternativa(int $alternativaId): void
    {
        Alternativa::findOrFail($alternativaId)->delete();
    }

    public function evaluarConIa(int $alternativaId): void
    {
        $this->evaluandoConIa = true;
        $this->evaluacionIa = [];

        try {
            $alternativa = Alternativa::with('nodos')->findOrFail($alternativaId);
            $llm = app(LlmServiceInterface::class);

            $objetivoCentral = $this->arbolObjetivos->nodos()
                ->where('tipo_nodo', TipoNodo::OBJETIVO_CENTRAL->value)
                ->first();

            $promptText = view('prompts.mml.evaluar-alternativa', [
                'programa' => $this->programa->nombre,
                'nombreAlternativa' => $alternativa->nombre,
                'medios' => $alternativa->nodos->pluck('descripcion')->toArray(),
                'objetivoCentral' => $objetivoCentral?->descripcion ?? '',
            ])->render();

            $result = $llm->suggest($promptText);
            $this->evaluacionIa = json_decode($result, true) ?? [];
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo evaluar con IA.');
        } finally {
            $this->evaluandoConIa = false;
        }
    }

    public function getMediosProperty()
    {
        if (!$this->arbolObjetivos) {
            return collect();
        }

        return $this->arbolObjetivos->nodos()
            ->whereIn('tipo_nodo', [
                TipoNodo::MEDIO_DIRECTO->value,
                TipoNodo::MEDIO_INDIRECTO->value,
            ])
            ->orderBy('orden')
            ->get();
    }

    public function getAlternativasProperty()
    {
        return $this->programa->alternativas()
            ->with('nodos')
            ->get();
    }

    public function getAlternativaSeleccionadaProperty(): ?Alternativa
    {
        return $this->programa->alternativas()
            ->where('seleccionada', true)
            ->first();
    }

    public function render()
    {
        return view('livewire.mml.seleccion-alternativas');
    }
}
```

**Step 4: Crear vista Blade**

Crear `resources/views/livewire/mml/seleccion-alternativas.blade.php`:

```blade
<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 4 — Selección de Alternativas"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre],
        ['label' => 'Etapa 4: Alternativas'],
    ]">
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

        @if (!$arbolObjetivos)
            <div class="rounded-md bg-yellow-50 p-4">
                <p class="text-sm text-yellow-700">
                    Primero debes completar el árbol de objetivos en la
                    <a href="{{ route('mml.etapa3', $programa) }}" class="font-medium underline">Etapa 3</a>.
                </p>
            </div>
        @else
            <div class="space-y-6">
                {{-- Medios disponibles --}}
                <x-forms.section
                    title="Medios del Árbol de Objetivos"
                    description="Estos son los medios identificados. Agrúpalos en alternativas para evaluar cuál es la mejor estrategia."
                >
                    <div class="space-y-2">
                        @forelse ($this->medios as $medio)
                            @php
                                $tipoEnum = \App\Enums\TipoNodo::tryFrom($medio->tipo_nodo->value ?? $medio->tipo_nodo);
                                $seleccionada = $this->alternativaSeleccionada;
                                $esPodado = $seleccionada && !$seleccionada->nodos->contains('id', $medio->id);
                            @endphp
                            <div class="flex items-center gap-3 rounded-md border p-3 {{ $esPodado ? 'bg-gray-50 border-gray-200 opacity-50' : 'bg-teal-50 border-teal-200' }}">
                                <span class="text-xs font-semibold uppercase {{ $esPodado ? 'text-gray-400' : 'text-teal-600' }}">
                                    {{ $tipoEnum?->label() ?? '' }}
                                </span>
                                <p class="text-sm {{ $esPodado ? 'text-gray-400 line-through' : 'text-teal-800' }}">
                                    {{ $medio->descripcion }}
                                </p>
                                @if ($esPodado)
                                    <span class="ml-auto text-xs text-gray-400">(podado)</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No hay medios en el árbol de objetivos.</p>
                        @endforelse
                    </div>
                </x-forms.section>

                {{-- Crear alternativa --}}
                <x-forms.section
                    title="Alternativas"
                    description="Crea alternativas y asígnales medios. Luego evalúa su viabilidad."
                >
                    <div class="flex items-end gap-3 mb-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Nombre de la alternativa</label>
                            <input
                                wire:model="nuevaAlternativaNombre"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ej: Alternativa A — Becas y capacitación"
                            />
                            @error('nuevaAlternativaNombre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button
                            wire:click="crearAlternativa"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500"
                        >
                            Crear
                        </button>
                    </div>

                    {{-- Lista de alternativas --}}
                    @foreach ($this->alternativas as $alternativa)
                        <div class="mb-4 rounded-lg border {{ $alternativa->seleccionada ? 'border-green-400 bg-green-50' : 'border-gray-200 bg-white' }} p-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold {{ $alternativa->seleccionada ? 'text-green-800' : 'text-gray-900' }}">
                                    {{ $alternativa->nombre }}
                                    @if ($alternativa->seleccionada)
                                        <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                            Seleccionada
                                        </span>
                                    @endif
                                </h4>
                                <div class="flex gap-2">
                                    <button
                                        wire:click="evaluarConIa({{ $alternativa->id }})"
                                        wire:loading.attr="disabled"
                                        class="text-xs text-purple-600 hover:text-purple-800 disabled:opacity-50"
                                    >
                                        Evaluar con IA
                                    </button>
                                    <button
                                        wire:click="eliminarAlternativa({{ $alternativa->id }})"
                                        wire:confirm="¿Eliminar esta alternativa?"
                                        class="text-xs text-red-600 hover:text-red-800"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </div>

                            {{-- Checkboxes de medios --}}
                            <div class="mt-3 space-y-1">
                                @foreach ($this->medios as $medio)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleNodo({{ $alternativa->id }}, {{ $medio->id }})"
                                            {{ $alternativa->nodos->contains('id', $medio->id) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        {{ $medio->descripcion }}
                                    </label>
                                @endforeach
                            </div>

                            {{-- Botón seleccionar --}}
                            @if (!$alternativa->seleccionada)
                                <div class="mt-3 border-t pt-3">
                                    <label class="block text-sm font-medium text-gray-700">Justificación de selección</label>
                                    <textarea
                                        wire:model="justificacionSeleccion"
                                        rows="2"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                        placeholder="¿Por qué esta alternativa es la mejor opción?"
                                    ></textarea>
                                    @error('justificacionSeleccion')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    <button
                                        wire:click="seleccionarAlternativa({{ $alternativa->id }})"
                                        class="mt-2 rounded-md bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-500"
                                    >
                                        Seleccionar esta alternativa
                                    </button>
                                </div>
                            @else
                                <div class="mt-3 border-t pt-3">
                                    <p class="text-sm text-green-700">
                                        <strong>Justificación:</strong> {{ $alternativa->justificacion_seleccion }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Evaluación IA --}}
                    @if (!empty($evaluacionIa))
                        <div class="rounded-md bg-purple-50 border border-purple-200 p-4 mt-4">
                            <h4 class="text-sm font-semibold text-purple-800">Evaluación de Viabilidad (IA)</h4>
                            <div class="mt-3 grid grid-cols-3 gap-4">
                                @foreach (['viabilidad_tecnica' => 'Técnica', 'viabilidad_institucional' => 'Institucional', 'viabilidad_presupuestal' => 'Presupuestal'] as $key => $label)
                                    @if (isset($evaluacionIa[$key]))
                                        @php
                                            $cal = $evaluacionIa[$key]['calificacion'] ?? 'N/A';
                                            $calColor = match($cal) {
                                                'alta' => 'text-green-700 bg-green-100',
                                                'media' => 'text-yellow-700 bg-yellow-100',
                                                'baja' => 'text-red-700 bg-red-100',
                                                default => 'text-gray-700 bg-gray-100',
                                            };
                                        @endphp
                                        <div class="rounded-md bg-white p-3 border border-gray-100">
                                            <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                                            <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-semibold {{ $calColor }}">
                                                {{ ucfirst($cal) }}
                                            </span>
                                            <p class="mt-1 text-xs text-gray-600">{{ $evaluacionIa[$key]['justificacion'] ?? '' }}</p>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @if (isset($evaluacionIa['recomendacion']))
                                <p class="mt-3 text-sm text-purple-700"><strong>Recomendación:</strong> {{ $evaluacionIa['recomendacion'] }}</p>
                            @endif
                        </div>
                    @endif
                </x-forms.section>
            </div>

            {{-- Navegación --}}
            <x-page.form-footer>
                <x-ui.button.secondary href="{{ route('mml.etapa3', $programa) }}">
                    Etapa anterior
                </x-ui.button.secondary>
                {{-- Futuro: Etapa 5 (MIR) --}}
            </x-page.form-footer>
        @endif
    </x-page.container>
</div>
```

**Step 5: Ejecutar tests para verificar que pasan**

```bash
sail artisan test --filter=SeleccionAlternativasTest
```

Expected: PASS.

**Step 6: Ejecutar suite completo**

```bash
sail artisan test
```

**Step 7: Commit**

```bash
git add app/Livewire/Mml/SeleccionAlternativas.php resources/views/livewire/mml/seleccion-alternativas.blade.php resources/views/prompts/mml/evaluar-alternativa.blade.php tests/Feature/Mml/SeleccionAlternativasTest.php
git commit -m "feat(S3-T7): implement Etapa 4 — Selección de Alternativas with IA evaluation"
```

---

## Criterios de Aceptación

- [ ] Medios del árbol de objetivos listados para agrupación
- [ ] Crear/nombrar alternativas y asignarles nodos
- [ ] IA genera evaluación de viabilidad por alternativa
- [ ] Selección de alternativa final con justificación obligatoria
- [ ] Visualización: ramas podadas se muestran tachadas/grises
- [ ] Solo una alternativa puede estar seleccionada a la vez
- [ ] Tests pasan
