# Plan: S2-T7 — CRUD de Programas Derivados con Interfaz Livewire (Arquitectura Actualizada)

**Ticket:** S2-T7
**Tipo:** feat
**Rama:** `feat/S2-T7-crud-programas-derivados`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T4 (Modelos), S2-T6 (Patrones UI), S1-T3 (Permisos)

---

## Contexto

Interfaz de administración para gestionar Programas Derivados (Sectoriales, Especiales, Institucionales, Regionales) y sus objetivos. Esta versión actualizada implementa la **nueva arquitectura de Frontend**:

- Uso de `<x-app-layout>` de Jetstream (no se reemplaza).
- Uso de componentes `<x-page.container>`, `<x-page.header>`, `<x-page.form-footer>`.
- Vistas organizadas por dominio: `resources/views/programs/derivados/`.
- Rutas organizadas en `routes/web/programs.php`.

---

## Pre-requisitos

- S2-T4: Modelos `ProgramaDerivado`, `ProgramaDerivadoObjetivo` con ENUM `tipo_programa_derivado`
- Componentes base de página creados (`x-page.container`, `x-page.header`, `x-page.form-footer`)
- S1-T3: Permiso `gestionar_catalogos` registrado
- S2-T3: Tabla `ped_planes` con al menos un plan activo

---

## Pasos

### 1. Crear Archivo de Rutas por Dominio

Crear `routes/web/programs.php`:

```php
<?php

use App\Http\Controllers\Programs\ProgramaDerivadoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Programas
|--------------------------------------------------------------------------
|
| Agrupa las rutas relacionadas con programas presupuestarios y derivados.
|
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'permission:gestionar_catalogos'
])->group(function () {

    // ============================================
    // Programas Derivados
    // ============================================
    Route::prefix('programs/derivados')->name('programs.derivados.')->group(function () {
        
        // Vista principal (Index)
        Route::get('/', [ProgramaDerivadoController::class, 'index'])->name('index');
        
        // CRUD Programas (API endpoints para Livewire)
        Route::post('/', [ProgramaDerivadoController::class, 'store'])->name('store');
        Route::put('/{programa}', [ProgramaDerivadoController::class, 'update'])->name('update');
        Route::delete('/{programa}', [ProgramaDerivadoController::class, 'destroy'])->name('destroy');
        
        // CRUD Objetivos (nested resources)
        Route::post('/{programa}/objetivos', [ProgramaDerivadoController::class, 'storeObjetivo'])->name('objetivos.store');
        Route::put('/{programa}/objetivos/{objetivo}', [ProgramaDerivadoController::class, 'updateObjetivo'])->name('objetivos.update');
        Route::delete('/{programa}/objetivos/{objetivo}', [ProgramaDerivadoController::class, 'destroyObjetivo'])->name('objetivos.destroy');
    });
});
```

**Registrar en `routes/web.php`:**

```php
// ... otras rutas
require __DIR__ . '/web/programs.php';
```

---

### 2. Crear Controlador (Organizado por Dominio)

```bash
mkdir -p app/Http/Controllers/Programs
sail artisan make:controller Programs/ProgramaDerivadoController
```

Editar `app/Http/Controllers/Programs/ProgramaDerivadoController.php`:

```php
<?php

namespace App\Http\Controllers\Programs;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramaDerivadoObjetivoRequest;
use App\Http\Requests\StoreProgramaDerivadoRequest;
use App\Models\PedPlan;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;

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

        return view('programs.derivados.index', compact('programas', 'planActivo'));
    }

    /**
     * Crear nuevo programa derivado.
     */
    public function store(StoreProgramaDerivadoRequest $request)
    {
        $planActivo = PedPlan::where('activo', true)->first();

        if (!$planActivo) {
            return back()
                ->with('flash.banner', 'No existe un Plan Estatal de Desarrollo activo.')
                ->with('flash.bannerStyle', 'danger');
        }

        $programa = ProgramaDerivado::create([
            'ped_plan_id' => $planActivo->id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
        ]);

        return redirect()->route('programs.derivados.index')
            ->with('flash.banner', "Programa '{$programa->nombre}' creado exitosamente.")
            ->with('flash.bannerStyle', 'success');
    }

    /**
     * Actualizar programa derivado.
     */
    public function update(StoreProgramaDerivadoRequest $request, ProgramaDerivado $programa)
    {
        $programa->update($request->validated());

        return redirect()->route('programs.derivados.index')
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

        return redirect()->route('programs.derivados.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // OBJETIVOS
    // ============================================

    public function storeObjetivo(StoreProgramaDerivadoObjetivoRequest $request, ProgramaDerivado $programa)
    {
        ProgramaDerivadoObjetivo::create([
            'programa_derivado_id' => $programa->id,
            'clave' => $request->clave,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('programs.derivados.index')
            ->with('flash.banner', 'Objetivo agregado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateObjetivo(StoreProgramaDerivadoObjetivoRequest $request, ProgramaDerivado $programa, ProgramaDerivadoObjetivo $objetivo)
    {
        $objetivo->update($request->validated());

        return redirect()->route('programs.derivados.index')
            ->with('flash.banner', 'Objetivo actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyObjetivo(ProgramaDerivado $programa, ProgramaDerivadoObjetivo $objetivo)
    {
        $objetivo->delete();

        return redirect()->route('programs.derivados.index')
            ->with('flash.banner', 'Objetivo eliminado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }
}
```

---

### 3. Crear Form Requests

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

### 4. Crear Componente Livewire

```bash
sail artisan make:livewire Programs/ProgramasDerivadosManager
```

Editar `app/Livewire/Programs/ProgramasDerivadosManager.php`:

*(El contenido del componente Livewire permanece igual que en la versión anterior, solo cambian las vistas)*

```php
<?php

namespace App\Livewire\Programs;

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

    protected $listeners = ['refresh' => '$refresh'];

    public function mount(): void
    {
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
        return view('livewire.programs.programas-derivados-manager');
    }
}
```

---

Tienes toda la razón. En las versiones modernas de Jetstream (v5+ para Laravel 11 con Livewire 3), los componentes ya no usan el prefijo `x-jet-`. Los componentes están disponibles directamente o bajo el namespace `x-jetstream::`.

Aquí tienes la corrección del plan con la sintaxis correcta:

---

# Plan: S2-T7 — CRUD de Programas Derivados (Corregido)

**Ticket:** S2-T7
**Tipo:** feat
**Rama:** `feat/S2-T7-crud-programas-derivados`
**Sprint:** 2 — Cascada de Planes

---

## Corrección: Componentes de Jetstream Moderno

En Jetstream moderno (Laravel 11 / Livewire 3):

| Componente | Sintaxis Correcta |
|------------|-------------------|
| Modal | `<x-dialog-modal>` o `<x-jetstream::dialog-modal>` |
| Botón Primario | `<x-primary-button>` |
| Botón Secundario | `<x-secondary-button>` |
| Botón Peligro | `<x-danger-button>` |
| Input | `<x-input>` |
| Label | `<x-label>` |
| Checkbox | `<x-checkbox>` |

---

### 5. Crear Vistas con Sintaxis Correcta

Crear `resources/views/programs/derivados/index.blade.php`:

```blade
<x-app-layout>
    
    {{-- Slot Header de Jetstream --}}
    <x-slot name="header">
        <x-page.header 
            title="Programas Derivados" 
            subtitle="Gestión de programas sectoriales, especiales, institucionales y regionales"
        >
            <x-secondary-button href="{{ route('cascade.ped.index') }}">
                Volver al PED
            </x-secondary-button>
        </x-page.header>
    </x-slot>

    {{-- Contenedor de Página --}}
    <x-page.container 
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Cascada de Planes', 'url' => route('cascade.ped.index')],
            ['label' => 'Programas Derivados']
        ]"
    >
        
        <livewire:programs.programas-derivados-manager />

    </x-page.container>

</x-app-layout>
```

---

Crear `resources/views/livewire/programs/programas-derivados-manager.blade.php`:

```blade
<div class="space-y-6">
    
    {{-- Alertas Flash --}}
    @if(session('message'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Alerta: Sin PED Activo --}}
    @if(!$this->planActivo)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>No hay Plan Estatal de Desarrollo activo.</strong>
                        Debe activar un PED antes de crear programas derivados.
                    </p>
                    <a href="{{ route('cascade.ped.index') }}" class="mt-2 inline-flex items-center text-sm text-yellow-700 underline hover:text-yellow-600">
                        Ir a gestión de PED
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Panel de Estadísticas --}}
    <div class="bg-white shadow sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Resumen</h3>
            
            @php($stats = $this->stats)
            
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                    <div class="text-xs text-gray-500">Total</div>
                </div>
                
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['sectoriales'] }}</div>
                    <div class="text-xs text-gray-500">Sectoriales</div>
                </div>
                
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['especiales'] }}</div>
                    <div class="text-xs text-gray-500">Especiales</div>
                </div>
                
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['institucionales'] }}</div>
                    <div class="text-xs text-gray-500">Institucionales</div>
                </div>
                
                <div class="text-center p-4 bg-orange-50 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600">{{ $stats['regionales'] }}</div>
                    <div class="text-xs text-gray-500">Regionales</div>
                </div>
                
                <div class="text-center p-4 bg-indigo-50 rounded-lg">
                    <div class="text-2xl font-bold text-indigo-600">{{ $stats['objetivos'] }}</div>
                    <div class="text-xs text-gray-500">Objetivos</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de Filtros y Acciones --}}
    <div class="bg-white shadow sm:rounded-lg mb-6">
        <div class="p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                
                {{-- Filtro --}}
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500">Filtrar:</span>
                    <select wire:model.live="filtroTipo" 
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                        @foreach($this->tiposFiltro as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Acciones --}}
                <div class="flex items-center space-x-4">
                    <button wire:click="expandAll" class="text-sm text-gray-600 hover:text-gray-900">
                        Expandir
                    </button>
                    <span class="text-gray-300">|</span>
                    <button wire:click="collapseAll" class="text-sm text-gray-600 hover:text-gray-900">
                        Contraer
                    </button>
                    <span class="text-gray-300">|</span>
                    <button wire:click="createPrograma"
                            @if(!$this->planActivo) disabled @endif
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Programa
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Listado de Programas --}}
    <div class="space-y-4">
        @forelse($this->programas as $programa)
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                
                {{-- Header del Programa --}}
                <div class="p-4 cursor-pointer hover:bg-gray-50 transition"
                     wire:click="togglePrograma({{ $programa->id }})">
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            {{-- Icono de expandir --}}
                            <span class="transform transition-transform duration-200 {{ isset($expandedProgramas[$programa->id]) ? 'rotate-90' : '' }}">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                            
                            {{-- Badge de tipo --}}
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $programa->tipo->colorClass() }}">
                                {{ $programa->tipo->prefijo() }}
                            </span>
                            
                            {{-- Nombre y descripción --}}
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $programa->nombre }}</h4>
                                <p class="text-sm text-gray-500">
                                    {{ $programa->tipo->label() }} · {{ $programa->objetivos->count() }} objetivos
                                </p>
                            </div>
                        </div>
                        
                        {{-- Acciones --}}
                        <div class="flex items-center space-x-3">
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
                </div>

                {{-- Contenido expandido (Objetivos) --}}
                @if(isset($expandedProgramas[$programa->id]))
                    <div class="border-t border-gray-200 bg-gray-50 p-4">
                        
                        <div class="flex items-center justify-between mb-4">
                            <h5 class="text-sm font-medium text-gray-700">Objetivos del Programa</h5>
                            <button wire:click="createObjetivo({{ $programa->id }})"
                                    class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Agregar Objetivo
                            </button>
                        </div>

                        @if($programa->objetivos->isEmpty())
                            <div class="text-center py-8">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">
                                    Este programa no tiene objetivos. Agregue el primero.
                                </p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach($programa->objetivos as $objetivo)
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200 hover:shadow-sm transition">
                                        <div class="flex items-center space-x-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-mono font-medium bg-indigo-100 text-indigo-800">
                                                {{ $programa->tipo->prefijo() }}.{{ $objetivo->clave }}
                                            </span>
                                            <span class="text-sm text-gray-700">
                                                {{ Str::limit($objetivo->descripcion, 60) }}
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-3">
                                            <button wire:click="editObjetivo({{ $objetivo->id }})"
                                                    class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
                                                Editar
                                            </button>
                                            <button wire:click="deleteObjetivo({{ $objetivo->id }})"
                                                    wire:confirm="¿Eliminar este objetivo?"
                                                    class="text-xs text-red-600 hover:text-red-900 font-medium">
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white shadow sm:rounded-lg p-12 text-center">
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
                            class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
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
    <x-dialog-modal wire:model="showProgramaModal" max-width="lg">
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
                            wire:model="programaTipo"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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
                              wire:model="programaDescripcion"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              rows="3"
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
            <x-secondary-button wire:click="$set('showProgramaModal', false)">
                Cancelar
            </x-secondary-button>

            <x-primary-button wire:click="savePrograma" class="ml-3">
                {{ $programaMode === 'create' ? 'Crear Programa' : 'Guardar Cambios' }}
            </x-primary-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: Crear/Editar Objetivo --}}
    <x-dialog-modal wire:model="showObjetivoModal" max-width="md">
        <x-slot name="title">
            {{ $objetivoMode === 'create' ? 'Crear Objetivo' : 'Editar Objetivo' }}
        </x-slot>

        <x-slot name="content">
            @if($programaSeleccionado)
                <p class="mb-4 text-sm text-gray-600">
                    Programa: <strong class="text-gray-900">{{ $programaSeleccionado->nombre }}</strong>
                </p>
            @endif

            <div class="space-y-4">
                <div>
                    <x-label for="objetivoClave" value="Clave del Objetivo" />
                    <div class="mt-1 flex items-center space-x-2">
                        @if($programaSeleccionado)
                            <span class="text-gray-500 text-sm">{{ $programaSeleccionado->tipo->prefijo() }}.</span>
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
                              wire:model="objetivoDescripcion"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              rows="3"
                              maxlength="500"></textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ strlen($objetivoDescripcion) }}/500 caracteres</p>
                    @error('objetivoDescripcion')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showObjetivoModal', false)">
                Cancelar
            </x-secondary-button>

            <x-primary-button wire:click="saveObjetivo" class="ml-3">
                {{ $objetivoMode === 'create' ? 'Crear Objetivo' : 'Guardar Cambios' }}
            </x-primary-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: Confirmar Eliminación --}}
    <x-dialog-modal wire:model="showDeleteModal" max-width="sm">
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
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Este programa tiene <strong>{{ $programaSeleccionado->objetivos_count }} objetivos</strong> que serán eliminados también.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showDeleteModal', false)">
                Cancelar
            </x-secondary-button>

            <x-danger-button wire:click="deletePrograma" class="ml-3">
                Eliminar Programa
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

</div>
```

---

### 7. Actualizar Navegación

Editar `resources/views/navigation-menu.blade.php`:

```blade
@can('gestionar_catalogos')
    <x-nav-link href="{{ route('cascade.ped.index') }}" :active="request()->routeIs('cascade.*')">
        {{ __('Cascada de Planes') }}
    </x-nav-link>
@endcan
```

---

## Resumen de Componentes Jetstream Correctos

| Componente | Uso |
|------------|-----|
| `<x-dialog-modal>` | Modales de confirmación y formularios simples |
| `<x-primary-button>` | Acción principal (Guardar, Crear) |
| `<x-secondary-button>` | Acción secundaria (Cancelar) |
| `<x-danger-button>` | Acción destructiva (Eliminar) |
| `<x-label>` | Etiquetas de formulario |
| `<x-input>` | Campos de texto |
| `<x-checkbox>` | Casillas de verificación |
| `<x-nav-link>` | Enlaces de navegación |

---
