# Plan: S2-T7 — CRUD de Programas Derivados con Interfaz Livewire

**Ticket:** S2-T7
**Tipo:** feat
**Rama:** `feat/S2-T7-crud-programas-derivados`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T4 (Modelos), S2-T6 (Patrones UI), S1-T3 (Permisos)

---

## Contexto

Interfaz de administración para gestionar Programas Derivados (Sectoriales, Especiales, Institucionales, Regionales) y sus objetivos. Reutiliza los patrones de UI establecidos en S2-T6 (CRUD con Livewire, formularios inline, validación reactiva).

**Tipos de Programa Derivado:**

| Tipo          | Prefijo | Descripción                              |
| ------------- | ------- | ---------------------------------------- |
| Sectorial     | OS      | Programas sectoriales del desarrollo     |
| Especial      | OE      | Programas para problemáticas específicas |
| Institucional | OI      | Programas de gestión institucional       |
| Regional      | OR      | Programas de desarrollo regional         |

---

## Pre-requisitos

- S2-T4: Modelos `ProgramaDerivado`, `ProgramaDerivadoObjetivo` con ENUM `tipo_programa_derivado`
- S2-T6: Patrones de componentes Livewire validados
- S1-T3: Permiso `gestionar_catalogos` registrado
- S2-T3: Tabla `ped_planes` con al menos un plan activo

---

## Pasos

### 1. Crear Rutas Protegidas

Editar `routes/web.php`:

```php
<?php

use App\Http\Controllers\ProgramaDerivadoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Programas Derivados Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'permission:gestionar_catalogos'
])->group(function () {

    Route::prefix('programas-derivados')->name('programas-derivados.')->group(function () {
        // Vista principal
        Route::get('/', [ProgramaDerivadoController::class, 'index'])->name('index');

        // CRUD Programas
        Route::post('/', [ProgramaDerivadoController::class, 'store'])->name('store');
        Route::put('/{programa}', [ProgramaDerivadoController::class, 'update'])->name('update');
        Route::delete('/{programa}', [ProgramaDerivadoController::class, 'destroy'])->name('destroy');

        // CRUD Objetivos (nested)
        Route::post('/{programa}/objetivos', [ProgramaDerivadoController::class, 'storeObjetivo'])->name('objetivos.store');
        Route::put('/{programa}/objetivos/{objetivo}', [ProgramaDerivadoController::class, 'updateObjetivo'])->name('objetivos.update');
        Route::delete('/{programa}/objetivos/{objetivo}', [ProgramaDerivadoController::class, 'destroyObjetivo'])->name('objetivos.destroy');
    });
});
```

---

### 2. Crear Form Requests

```bash
sail artisan make:request StoreProgramaDerivadoRequest
sail artisan make:request StoreProgramaDerivadoObjetivoRequest
```

Editar `app/Http/Requests/StoreProgramaDerivadoRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Enums\TipoProgramaDerivado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramaDerivadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo' => [
                'required',
                Rule::enum(TipoProgramaDerivado::class)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del programa es obligatorio.',
            'tipo.required' => 'Debe seleccionar un tipo de programa.',
            'tipo.Illuminate\Validation\Rules\Enum' => 'El tipo seleccionado no es válido.',
        ];
    }
}
```

Editar `app/Http/Requests/StoreProgramaDerivadoObjetivoRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramaDerivadoObjetivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave' => ['required', 'string', 'max:20'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave del objetivo es obligatoria.',
            'descripcion.required' => 'La descripción del objetivo es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ];
    }
}
```

---

### 3. Crear Controlador

```bash
sail artisan make:controller ProgramaDerivadoController
```

Editar `app/Http/Controllers/ProgramaDerivadoController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramaDerivadoObjetivoRequest;
use App\Http\Requests\StoreProgramaDerivadoRequest;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Http\Request;

class ProgramaDerivadoController extends Controller
{
    /**
     * Vista principal de programas derivados.
     */
    public function index()
    {
        $planActivo = PedPlan::where('activo', true)->first();

        $programas = ProgramaDerivado::with(['objetivos'])
            ->when($planActivo, fn($q) => $q->where('ped_plan_id', $planActivo->id))
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        return view('programas-derivados.index', compact('programas', 'planActivo'));
    }

    /**
     * Crear nuevo programa derivado.
     */
    public function store(StoreProgramaDerivadoRequest $request)
    {
        $planActivo = PedPlan::where('activo', true)->first();

        if (!$planActivo) {
            return back()
                ->with('flash.banner', 'No existe un Plan Estatal de Desarrollo activo. Active uno antes de crear programas.')
                ->with('flash.bannerStyle', 'danger');
        }

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $planActivo->id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
        ]);

        return redirect()->route('programas-derivados.index')
            ->with('flash.banner', "Programa '{$programa->nombre}' creado exitosamente.")
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Actualizar programa derivado.
     */
    public function update(StoreProgramaDerivadoRequest $request, ProgramaDerivado $programa)
    {
        $programa->update($request->validated());

        return redirect()->route('programas-derivados.index')
            ->with('flash.banner', 'Programa actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Eliminar programa derivado.
     */
    public function destroy(ProgramaDerivado $programa)
    {
        $nombre = $programa->nombre;
        $objetivos = $programa->objetivos()->count();

        $programa->delete();

        $mensaje = "Programa '{$nombre}' eliminado.";
        if ($objetivos > 0) {
            $mensaje .= " Se eliminaron {$objetivos} objetivos.";
        }

        return redirect()->route('programas-derivados.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // OBJETIVOS
    // ============================================

    /**
     * Crear objetivo para un programa derivado.
     */
    public function storeObjetivo(StoreProgramaDerivadoObjetivoRequest $request, ProgramaDerivado $programa)
    {
        ProgramaDerivadoObjetivo::create([
            'programa_derivado_id' => $programa->id,
            'clave' => $request->clave,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('programas-derivados.index')
            ->with('flash.banner', 'Objetivo agregado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Actualizar objetivo.
     */
    public function updateObjetivo(StoreProgramaDerivadoObjetivoRequest $request, ProgramaDerivado $programa, ProgramaDerivadoObjetivo $objetivo)
    {
        $objetivo->update($request->validated());

        return redirect()->route('programas-derivados.index')
            ->with('flash.banner', 'Objetivo actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Eliminar objetivo.
     */
    public function destroyObjetivo(ProgramaDerivado $programa, ProgramaDerivadoObjetivo $objetivo)
    {
        $objetivo->delete();

        return redirect()->route('programas-derivados.index')
            ->with('flash.banner', 'Objetivo eliminado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }
}
```

---

### 4. Crear Componente Livewire Principal

```bash
sail artisan make:livewire ProgramasDerivadosManager
```

Editar `app/Livewire/ProgramasDerivadosManager.php`:

```php
<?php

namespace App\Livewire;

use App\Enums\TipoProgramaDerivado;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use Livewire\Component;

class ProgramasDerivadosManager extends Component
{
    // Filtros
    public string $filtroTipo = 'todos';

    // Estado de modales
    public bool $showProgramaModal = false;
    public bool $showObjetivoModal = false;
    public bool $showDeleteModal = false;

    // Programa seleccionado
    public ?ProgramaDerivado $programaSeleccionado = null;
    public ?ProgramaDerivadoObjetivo $objetivoSeleccionado = null;

    // Formulario de programa
    public string $programaNombre = '';
    public string $programaDescripcion = '';
    public string $programaTipo = '';
    public string $programaMode = 'create';

    // Formulario de objetivo
    public string $objetivoClave = '';
    public string $objetivoDescripcion = '';
    public string $objetivoMode = 'create';

    // Estado de expansión
    public array $expandedProgramas = [];

    protected function rules(): array
    {
        return [
            'programaNombre' => ['required', 'string', 'max:255'],
            'programaDescripcion' => ['nullable', 'string', 'max:1000'],
            'programaTipo' => ['required', 'in:' . implode(',', TipoProgramaDerivado::values())],
            'objetivoClave' => ['required', 'string', 'max:20'],
            'objetivoDescripcion' => ['required', 'string', 'max:500'],
        ];
    }

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount(): void
    {
        // Expandir primer programa por defecto
        $primerPrograma = ProgramaDerivado::first();
        if ($primerPrograma) {
            $this->expandedProgramas[$primerPrograma->id] = true;
        }
    }

    // ============================================
    // FILTROS
    // ============================================

    public function setFiltroTipo(string $tipo): void
    {
        $this->filtroTipo = $tipo;
    }

    public function getTiposFiltroProperty(): array
    {
        return [
            'todos' => 'Todos',
            TipoProgramaDerivado::SECTORIAL->value => 'Sectoriales',
            TipoProgramaDerivado::ESPECIAL->value => 'Especiales',
            TipoProgramaDerivado::INSTITUCIONAL->value => 'Institucionales',
            TipoProgramaDerivado::REGIONAL->value => 'Regionales',
        ];
    }

    // ============================================
    // EXPANDIR/CONTRAER
    // ============================================

    public function togglePrograma(int $programaId): void
    {
        if (isset($this->expandedProgramas[$programaId])) {
            unset($this->expandedProgramas[$programaId]);
        } else {
            $this->expandedProgramas[$programaId] = true;
        }
    }

    public function expandAll(): void
    {
        $this->programas->each(fn($p) => $this->expandedProgramas[$p->id] = true);
    }

    public function collapseAll(): void
    {
        $this->expandedProgramas = [];
    }

    // ============================================
    // PROGRAMAS CRUD
    // ============================================

    public function createPrograma(): void
    {
        $this->resetProgramaForm();
        $this->programaMode = 'create';
        $this->showProgramaModal = true;
    }

    public function editPrograma(int $programaId): void
    {
        $this->programaSeleccionado = ProgramaDerivado::find($programaId);
        $this->programaNombre = $this->programaSeleccionado->nombre;
        $this->programaDescripcion = $this->programaSeleccionado->descripcion ?? '';
        $this->programaTipo = $this->programaSeleccionado->tipo->value;
        $this->programaMode = 'edit';
        $this->showProgramaModal = true;
    }

    public function savePrograma(): void
    {
        $this->validate([
            'programaNombre' => ['required', 'string', 'max:255'],
            'programaDescripcion' => ['nullable', 'string', 'max:1000'],
            'programaTipo' => ['required', 'in:' . implode(',', TipoProgramaDerivado::values())],
        ]);

        $planActivo = PedPlan::where('activo', true)->first();

        if (!$planActivo && $this->programaMode === 'create') {
            session()->flash('error', 'No existe un PED activo.');
            return;
        }

        if ($this->programaMode === 'create') {
            $programa = ProgramaDerivado::create([
                'ped_plan_id' => $planActivo->id,
                'nombre' => $this->programaNombre,
                'descripcion' => $this->programaDescripcion,
                'tipo' => $this->programaTipo,
            ]);
            $this->expandedProgramas[$programa->id] = true;
            session()->flash('message', "Programa '{$programa->nombre}' creado exitosamente.");
        } else {
            $this->programaSeleccionado->update([
                'nombre' => $this->programaNombre,
                'descripcion' => $this->programaDescripcion,
                'tipo' => $this->programaTipo,
            ]);
            session()->flash('message', 'Programa actualizado exitosamente.');
        }

        $this->showProgramaModal = false;
        $this->resetProgramaForm();
    }

    public function confirmDeletePrograma(int $programaId): void
    {
        $this->programaSeleccionado = ProgramaDerivado::withCount('objetivos')->find($programaId);
        $this->showDeleteModal = true;
    }

    public function deletePrograma(): void
    {
        if ($this->programaSeleccionado) {
            $nombre = $this->programaSeleccionado->nombre;
            $this->programaSeleccionado->delete();
            unset($this->expandedProgramas[$this->programaSeleccionado->id]);
            session()->flash('message', "Programa '{$nombre}' eliminado.");
        }

        $this->showDeleteModal = false;
        $this->programaSeleccionado = null;
    }

    private function resetProgramaForm(): void
    {
        $this->programaNombre = '';
        $this->programaDescripcion = '';
        $this->programaTipo = '';
        $this->programaSeleccionado = null;
        $this->resetErrorBag(['programaNombre', 'programaDescripcion', 'programaTipo']);
    }

    // ============================================
    // OBJETIVOS CRUD
    // ============================================

    public function createObjetivo(int $programaId): void
    {
        $this->programaSeleccionado = ProgramaDerivado::find($programaId);
        $this->resetObjetivoForm();
        $this->objetivoMode = 'create';
        $this->showObjetivoModal = true;
    }

    public function editObjetivo(int $objetivoId): void
    {
        $this->objetivoSeleccionado = ProgramaDerivadoObjetivo::find($objetivoId);
        $this->programaSeleccionado = $this->objetivoSeleccionado->programa;
        $this->objetivoClave = $this->objetivoSeleccionado->clave;
        $this->objetivoDescripcion = $this->objetivoSeleccionado->descripcion;
        $this->objetivoMode = 'edit';
        $this->showObjetivoModal = true;
    }

    public function saveObjetivo(): void
    {
        $this->validate([
            'objetivoClave' => ['required', 'string', 'max:20'],
            'objetivoDescripcion' => ['required', 'string', 'max:500'],
        ]);

        if ($this->objetivoMode === 'create') {
            ProgramaDerivadoObjetivo::create([
                'programa_derivado_id' => $this->programaSeleccionado->id,
                'clave' => $this->objetivoClave,
                'descripcion' => $this->objetivoDescripcion,
            ]);
            session()->flash('message', 'Objetivo creado exitosamente.');
        } else {
            $this->objetivoSeleccionado->update([
                'clave' => $this->objetivoClave,
                'descripcion' => $this->objetivoDescripcion,
            ]);
            session()->flash('message', 'Objetivo actualizado exitosamente.');
        }

        $this->showObjetivoModal = false;
        $this->resetObjetivoForm();
    }

    public function deleteObjetivo(int $objetivoId): void
    {
        $objetivo = ProgramaDerivadoObjetivo::find($objetivoId);
        $objetivo->delete();
        session()->flash('message', 'Objetivo eliminado.');
    }

    private function resetObjetivoForm(): void
    {
        $this->objetivoClave = '';
        $this->objetivoDescripcion = '';
        $this->objetivoSeleccionado = null;
        $this->resetErrorBag(['objetivoClave', 'objetivoDescripcion']);
    }

    // ============================================
    // HELPERS
    // ============================================

    public function getPlanActivoProperty(): ?PedPlan
    {
        return PedPlan::where('activo', true)->first();
    }

    public function getProgramasProperty()
    {
        return ProgramaDerivado::with('objetivos')
            ->when($this->filtroTipo !== 'todos', fn($q) => $q->where('tipo', $this->filtroTipo))
            ->when($this->planActivo, fn($q) => $q->where('ped_plan_id', $this->planActivo->id))
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();
    }

    public function getStatsProperty(): array
    {
        $query = ProgramaDerivado::when($this->planActivo, fn($q) => $q->where('ped_plan_id', $this->planActivo->id));

        return [
            'total' => $query->count(),
            'sectoriales' => (clone $query)->where('tipo', TipoProgramaDerivado::SECTORIAL)->count(),
            'especiales' => (clone $query)->where('tipo', TipoProgramaDerivado::ESPECIAL)->count(),
            'institucionales' => (clone $query)->where('tipo', TipoProgramaDerivado::INSTITUCIONAL)->count(),
            'regionales' => (clone $query)->where('tipo', TipoProgramaDerivado::REGIONAL)->count(),
            'objetivos' => ProgramaDerivadoObjetivo::when($this->planActivo, function($q) {
                $q->whereIn('programa_derivado_id',
                    ProgramaDerivado::where('ped_plan_id', $this->planActivo->id)->pluck('id')
                );
            })->count(),
        ];
    }

    public function render()
    {
        return view('livewire.programas-derivados-manager');
    }
}
```

---

### 5. Crear Vistas Blade

Crear `resources/views/programas-derivados/index.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Programas Derivados
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:programas-derivados-manager />
        </div>
    </div>
</x-app-layout>
```

Crear `resources/views/livewire/programas-derivados-manager.blade.php`:

```blade
<div class="space-y-6">

    {{-- Alertas --}}
    @if(session('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Verificar PED Activo --}}
    @if(!$this->planActivo)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>No hay Plan Estatal de Desarrollo activo.</strong>
                        Debe activar un PED antes de crear programas derivados.
                    </p>
                    <a href="{{ route('ped.index') }}" class="mt-2 inline-flex items-center text-sm text-yellow-700 underline hover:text-yellow-600">
                        Ir a gestión de PED
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Header con Stats --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Programas Derivados</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Gestione los programas sectoriales, especiales, institucionales y regionales.
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3">
                @php($stats = $this->stats)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    {{ $stats['total'] }} programas
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    {{ $stats['objetivos'] }} objetivos
                </span>
            </div>
        </div>

        {{-- Stats por tipo --}}
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['tipo' => 'sectoriales', 'color' => 'blue', 'label' => 'Sectoriales'],
                ['tipo' => 'especiales', 'color' => 'green', 'label' => 'Especiales'],
                ['tipo' => 'institucionales', 'color' => 'purple', 'label' => 'Institucionales'],
                ['tipo' => 'regionales', 'color' => 'orange', 'label' => 'Regionales'],
            ] as $item)
                <button wire:click="setFiltroTipo('{{ $item['tipo'] }}')"
                        class="p-4 rounded-lg border-2 transition-all {{ $filtroTipo === $item['tipo'] ? 'border-' . $item['color'] . '-500 bg-' . $item['color'] . '-50' : 'border-gray-200 hover:border-gray-300' }}">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats[$item['tipo']] }}</div>
                    <div class="text-sm text-gray-500">{{ $item['label'] }}</div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Filtros y Acciones --}}
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

            {{-- Filtro por tipo --}}
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Filtrar:</span>
                <select wire:model="filtroTipo"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                    @foreach($this->tiposFiltro as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center space-x-2">
                <button wire:click="expandAll" class="text-sm text-gray-600 hover:text-gray-900">
                    Expandir todos
                </button>
                <span class="text-gray-300">|</span>
                <button wire:click="collapseAll" class="text-sm text-gray-600 hover:text-gray-900">
                    Contraer todos
                </button>
                <span class="text-gray-300">|</span>
                <button wire:click="createPrograma"
                        @if(!$this->planActivo) disabled @endif
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Programa
                </button>
            </div>
        </div>
    </div>

    {{-- Listado de Programas --}}
    <div class="space-y-4">
        @forelse($this->programas as $programa)
            <div class="bg-white rounded-lg shadow overflow-hidden">

                {{-- Header del Programa --}}
                <div class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50"
                     wire:click="togglePrograma({{ $programa->id }})">

                    <div class="flex items-center space-x-4">
                        {{-- Icono de expandir --}}
                        <span class="transform transition-transform duration-200 {{ isset($expandedProgramas[$programa->id]) ? 'rotate-90' : '' }}">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>

                        {{-- Badge de tipo --}}
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $programa->tipo->colorClass() }}">
                            {{ $programa->prefijoClave() }}
                        </span>

                        {{-- Nombre --}}
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $programa->nombre }}</h4>
                            <p class="text-sm text-gray-500">
                                {{ $programa->tipo->label() }} · {{ $programa->objetivos->count() }} objetivos
                            </p>
                        </div>
                    </div>

                    {{-- Acciones --}}
                    <div class="flex items-center space-x-2">
                        <button wire:click.stop="editPrograma({{ $programa->id }})"
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            Editar
                        </button>
                        <button wire:click.stop="confirmDeletePrograma({{ $programa->id }})"
                                class="text-red-600 hover:text-red-900 text-sm font-medium">
                            Eliminar
                        </button>
                    </div>
                </div>

                {{-- Contenido expandido (Objetivos) --}}
                <div wire:show="{{ isset($expandedProgramas[$programa->id]) }}"
                     x-show="{{ isset($expandedProgramas[$programa->id]) ? 'true' : 'false' }}"
                     class="border-t bg-gray-50 p-4">

                    <div class="flex items-center justify-between mb-4">
                        <h5 class="text-sm font-medium text-gray-700">Objetivos</h5>
                        <button wire:click="createObjetivo({{ $programa->id }})"
                                class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Agregar Objetivo
                        </button>
                    </div>

                    @if($programa->objetivos->isEmpty())
                        <p class="text-sm text-gray-500 text-center py-4">
                            Este programa no tiene objetivos. Agregue el primero.
                        </p>
                    @else
                        <div class="space-y-2">
                            @foreach($programa->objetivos as $objetivo)
                                <div class="flex items-center justify-between p-3 bg-white rounded border hover:shadow-sm transition">
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ $programa->prefijoClave() }}.{{ $objetivo->clave }}
                                        </span>
                                        <span class="text-sm text-gray-700">{{ Str::limit($objetivo->descripcion, 60) }}</span>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <button wire:click="editObjetivo({{ $objetivo->id }})"
                                                class="text-xs text-indigo-600 hover:text-indigo-900">
                                            Editar
                                        </button>
                                        <button wire:click="deleteObjetivo({{ $objetivo->id }})"
                                                wire:confirm="¿Eliminar este objetivo?"
                                                class="text-xs text-red-600 hover:text-red-900">
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay programas derivados</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($filtroTipo !== 'todos')
                        No se encontraron programas del tipo seleccionado.
                    @else
                        Comience creando un nuevo programa derivado.
                    @endif
                </p>
                @if($this->planActivo)
                    <button wire:click="createPrograma"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Crear Primer Programa
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Modal: Crear/Editar Programa --}}
    <x-dialog-modal wire:model="showProgramaModal" maxWidth="lg">
        <x-slot name="title">
            {{ $programaMode === 'create' ? 'Crear Programa Derivado' : 'Editar Programa Derivado' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="programaNombre" value="Nombre del Programa" />
                    <x-input id="programaNombre"
                             type="text"
                             class="mt-1 block w-full"
                             wire:model="programaNombre"
                             placeholder="Ej: Programa Sectorial de Educación" />
                    @error('programaNombre')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="programaTipo" value="Tipo de Programa" />
                    <select id="programaTipo"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            wire:model="programaTipo">
                        <option value="">Seleccione un tipo...</option>
                        @foreach(\App\Enums\TipoProgramaDerivado::cases() as $tipo)
                            <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                        @endforeach
                    </select>
                    @error('programaTipo')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if($programaTipo)
                        <p class="mt-2 text-sm text-gray-500">
                            {{ \App\Enums\TipoProgramaDerivado::tryFrom($programaTipo)?->descripcion() }}
                        </p>
                    @endif
                </div>

                <div>
                    <x-label for="programaDescripcion" value="Descripción (opcional)" />
                    <textarea id="programaDescripcion"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              rows="3"
                              wire:model="programaDescripcion"
                              maxlength="1000"
                              placeholder="Descripción general del programa..."></textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ strlen($programaDescripcion) }}/1000 caracteres</p>
                    @error('programaDescripcion')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showProgramaModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>
            <x-button wire:click="savePrograma" class="bg-indigo-600 text-white">
                {{ $programaMode === 'create' ? 'Crear Programa' : 'Guardar Cambios' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: Crear/Editar Objetivo --}}
    <x-dialog-modal wire:model="showObjetivoModal" maxWidth="md">
        <x-slot name="title">
            {{ $objetivoMode === 'create' ? 'Crear Objetivo' : 'Editar Objetivo' }}
        </x-slot>

        <x-slot name="content">
            @if($programaSeleccionado)
                <p class="mb-4 text-sm text-gray-600">
                    Programa: <strong>{{ $programaSeleccionado->nombre }}</strong>
                </p>
            @endif

            <div class="space-y-4">
                <div>
                    <x-label for="objetivoClave" value="Clave del Objetivo" />
                    <div class="mt-1 flex items-center space-x-2">
                        @if($programaSeleccionado)
                            <span class="text-gray-500">{{ $programaSeleccionado->prefijoClave() }}.</span>
                        @endif
                        <x-input id="objetivoClave"
                                 type="text"
                                 class="flex-1"
                                 wire:model="objetivoClave"
                                 placeholder="1, 2, 1.1" />
                    </div>
                    @error('objetivoClave')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="objetivoDescripcion" value="Descripción del Objetivo" />
                    <textarea id="objetivoDescripcion"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              rows="3"
                              wire:model="objetivoDescripcion"
                              maxlength="500"></textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ strlen($objetivoDescripcion) }}/500 caracteres</p>
                    @error('objetivoDescripcion')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showObjetivoModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>
            <x-button wire:click="saveObjetivo" class="bg-indigo-600 text-white">
                {{ $objetivoMode === 'create' ? 'Crear Objetivo' : 'Guardar Cambios' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: Confirmar Eliminación --}}
    <x-dialog-modal wire:model="showDeleteModal" maxWidth="sm">
        <x-slot name="title">
            Confirmar Eliminación
        </x-slot>

        <x-slot name="content">
            @if($programaSeleccionado)
                <p class="text-gray-700">
                    ¿Está seguro de eliminar el programa <strong>"{{ $programaSeleccionado->nombre }}"</strong>?
                </p>

                @if($programaSeleccionado->objetivos_count > 0)
                    <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Este programa tiene <strong>{{ $programaSeleccionado->objetivos_count }} objetivos</strong>
                                    que serán eliminados también.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showDeleteModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>
            <x-danger-button wire:click="deletePrograma">
                Eliminar Programa
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

</div>
```

---

### 6. Agregar Método de Color al Enum

Editar `app/Enums/TipoProgramaDerivado.php`:

```php
<?php

namespace App\Enums;

enum TipoProgramaDerivado: string
{
    case SECTORIAL = 'sectorial';
    case ESPECIAL = 'especial';
    case INSTITUCIONAL = 'institucional';
    case REGIONAL = 'regional';

    public function label(): string
    {
        return match($this) {
            self::SECTORIAL => 'Programa Sectorial',
            self::ESPECIAL => 'Programa Especial',
            self::INSTITUCIONAL => 'Programa Institucional',
            self::REGIONAL => 'Programa Regional',
        };
    }

    public function descripcion(): string
    {
        return match($this) {
            self::SECTORIAL => 'Programas que abordan temas sectoriales específicos del desarrollo estatal.',
            self::ESPECIAL => 'Programas diseñados para atender problemáticas específicas o emergentes.',
            self::INSTITUCIONAL => 'Programas que orientan la gestión interna de una institución.',
            self::REGIONAL => 'Programas enfocados al desarrollo de regiones geográficas específicas.',
        };
    }

    public function prefijo(): string
    {
        return match($this) {
            self::SECTORIAL => 'OS',
            self::ESPECIAL => 'OE',
            self::INSTITUCIONAL => 'OI',
            self::REGIONAL => 'OR',
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::SECTORIAL => 'bg-blue-100 text-blue-800',
            self::ESPECIAL => 'bg-green-100 text-green-800',
            self::INSTITUCIONAL => 'bg-purple-100 text-purple-800',
            self::REGIONAL => 'bg-orange-100 text-orange-800',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

---

### 7. Agregar Navegación al Menú

Editar `resources/views/navigation-menu.blade.php`:

```blade
@can('gestionar_catalogos')
    <x-nav-link href="{{ route('ped.index') }}" :active="request()->routeIs('ped.*')">
        {{ __('Plan Estatal') }}
    </x-nav-link>

    <x-nav-link href="{{ route('programas-derivados.index') }}" :active="request()->routeIs('programas-derivados.*')">
        {{ __('Programas Derivados') }}
    </x-nav-link>
@endcan
```

---

### 8. Crear Tests Funcionales

```bash
sail artisan make:test ProgramasDerivadosCrudTest
```

Editar `tests/Feature/ProgramasDerivadosCrudTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\TipoProgramaDerivado;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramasDerivadosCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ============================================
    // Tests de Autorización
    // ============================================

    public function test_usuario_sin_permiso_no_puede_acceder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('programas-derivados.index'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        // Crear PED activo
        PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $response = $this->actingAs($user)->get(route('programas-derivados.index'));

        $response->assertOk();
        $response->assertSee('Programas Derivados');
    }

    public function test_sin_ped_activo_muestra_advertencia(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('programas-derivados.index'));

        $response->assertOk();
        $response->assertSee('No hay Plan Estatal de Desarrollo activo');
    }

    // ============================================
    // Tests de CRUD de Programas
    // ============================================

    public function test_crear_programa_derivado(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('createPrograma')
            ->set('programaNombre', 'Programa Sectorial de Educación')
            ->set('programaTipo', TipoProgramaDerivado::SECTORIAL->value)
            ->set('programaDescripcion', 'Descripción del programa')
            ->call('savePrograma')
            ->assertSessionHas('message');

        $this->assertDatabaseHas('programas_derivados', [
            'nombre' => 'Programa Sectorial de Educación',
            'tipo' => TipoProgramaDerivado::SECTORIAL->value,
        ]);
    }

    public function test_editar_programa_derivado(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Original',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('editPrograma', $programa->id)
            ->set('programaNombre', 'Programa Editado')
            ->call('savePrograma');

        $this->assertEquals('Programa Editado', $programa->fresh()->nombre);
    }

    public function test_eliminar_programa_derivado(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa a Eliminar',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('confirmDeletePrograma', $programa->id)
            ->call('deletePrograma');

        $this->assertDatabaseMissing('programas_derivados', ['id' => $programa->id]);
    }

    // ============================================
    // Tests de CRUD de Objetivos
    // ============================================

    public function test_crear_objetivo_en_programa(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('createObjetivo', $programa->id)
            ->set('objetivoClave', '1')
            ->set('objetivoDescripcion', 'Objetivo de prueba')
            ->call('saveObjetivo');

        $this->assertDatabaseHas('programas_derivados_objetivos', [
            'programa_derivado_id' => $programa->id,
            'clave' => '1',
            'descripcion' => 'Objetivo de prueba',
        ]);
    }

    public function test_eliminar_objetivo(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $objetivo = $programa->objetivos()->create([
            'clave' => '1',
            'descripcion' => 'Objetivo a eliminar',
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('deleteObjetivo', $objetivo->id);

        $this->assertDatabaseMissing('programas_derivados_objetivos', ['id' => $objetivo->id]);
    }

    // ============================================
    // Tests de Filtros
    // ============================================

    public function test_filtro_por_tipo_sectorial(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $sectorial = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Sectorial',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $especial = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Especial',
            'tipo' => TipoProgramaDerivado::ESPECIAL,
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->set('filtroTipo', TipoProgramaDerivado::SECTORIAL->value)
            ->assertSee('Programa Sectorial')
            ->assertDontSee('Programa Especial');
    }

    // ============================================
    // Tests de Validación
    // ============================================

    public function test_validacion_tipo_requerido(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('createPrograma')
            ->set('programaNombre', 'Programa sin tipo')
            ->set('programaTipo', '')
            ->call('savePrograma')
            ->assertHasErrors(['programaTipo']);
    }

    public function test_validacion_tipo_invalido(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('createPrograma')
            ->set('programaNombre', 'Programa con tipo inválido')
            ->set('programaTipo', 'tipo_inexistente')
            ->call('savePrograma')
            ->assertHasErrors(['programaTipo']);
    }

    public function test_validacion_descripcion_max_500_caracteres(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa Test',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('createObjetivo', $programa->id)
            ->set('objetivoClave', '1')
            ->set('objetivoDescripcion', str_repeat('a', 501))
            ->call('saveObjetivo')
            ->assertHasErrors(['objetivoDescripcion']);
    }

    // ============================================
    // Tests de Cascade Delete
    // ============================================

    public function test_eliminar_programa_elimina_objetivos(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $plan->id,
            'nombre' => 'Programa con Objetivos',
            'tipo' => TipoProgramaDerivado::SECTORIAL,
        ]);

        $objetivo = $programa->objetivos()->create([
            'clave' => '1',
            'descripcion' => 'Objetivo test',
        ]);

        Livewire::actingAs($user)
            ->test('programas-derivados-manager')
            ->call('confirmDeletePrograma', $programa->id)
            ->assertSet('programaSeleccionado.objetivos_count', 1)
            ->call('deletePrograma');

        $this->assertDatabaseMissing('programas_derivados', ['id' => $programa->id]);
        $this->assertDatabaseMissing('programas_derivados_objetivos', ['id' => $objetivo->id]);
    }
}
```

---

### 9. Ejecutar y Verificar

```bash
# Compilar assets
sail npm run build

# Ejecutar tests
sail artisan test --filter ProgramasDerivadosCrudTest

# Acceder a la aplicación
# http://localhost/programas-derivados
```

Verificación manual:

1. Iniciar sesión como usuario con permiso `gestionar_catalogos`
2. Verificar que sin PED activo se muestra advertencia
3. Crear/activar un PED
4. Crear programa derivado de cada tipo
5. Agregar objetivos a cada programa
6. Editar y eliminar programas y objetivos
7. Probar filtros por tipo
8. Verificar eliminación en cascada

---

## Criterios de Aceptación

- [ ] Ruta `/programas-derivados` protegida con middleware `permission:gestionar_catalogos`
- [ ] Componente `ProgramasDerivadosManager` muestra listado con filtros
- [ ] Filtro por tipo funciona correctamente (todos, sectorial, especial, institucional, regional)
- [ ] CRUD completo de programas derivados
- [ ] CRUD de objetivos anidados por programa
- [ ] Vinculación automática al PED activo (`ped_plan_id`)
- [ ] Validación: nombre requerido, tipo validado contra Enum PHP
- [ ] Advertencia si no hay PED activo
- [ ] Confirmación de eliminación con conteo de objetivos
- [ ] Tests pasan (12 assertions)
- [ ] Usuario sin permiso recibe 403
- [ ] Responsive en pantallas >= 768px

---

## Notas

### Patrones Reutilizados de S2-T6

| Patrón              | S2-T6 (PED)                  | S2-T7 (Programas Derivados)       |
| ------------------- | ---------------------------- | --------------------------------- |
| Modales Livewire    | `PedPlanForm`, `PedNodoForm` | Integrado en componente principal |
| Validación reactiva | Form Requests + wire:model   | Igual                             |
| Expansión de nodos  | Alpine.js x-show             | Livewire + Alpine híbrido         |
| Cascade delete      | Advertencia de hijos         | Advertencia + conteo de objetivos |
| Filtros             | No aplica                    | Tabs por tipo + dropdown          |

### Diferencias con S2-T6

| Aspecto             | S2-T6          | S2-T7                 |
| ------------------- | -------------- | --------------------- |
| Niveles jerárquicos | 6              | 2                     |
| ENUM nativo         | No             | Sí (tipo de programa) |
| Filtros             | No             | Por tipo              |
| Vista               | Árbol completo | Lista expandible      |
| Dependencia         | Ninguna        | PED activo            |

---
