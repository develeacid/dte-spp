# Plan: S2-T6 — CRUD de PED con Interfaz Livewire

**Ticket:** S2-T6
**Tipo:** feat
**Rama:** `feat/S2-T6-crud-ped-livewire`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T3 (Modelos PED), S1-T3 (Permisos)

---

## Contexto

Interfaz de administración para que el planeador capture y edite toda la jerarquía del PED (6 niveles). Es el primer CRUD del sistema y establece los patrones de UI/UX para futuros módulos.

**Estructura de 6 niveles:**

```
Plan
  └── Eje
        └── Tema
              └── Objetivo Estratégico
                    └── Estrategia
                          └── Línea de Acción
```

**Decisiones de diseño:**

- **Vista de árbol colapsable** con Alpine.js para performance
- **Formularios inline** (no modales) para edición rápida
- **Validación reactiva** sin recarga de página
- **Confirmación inteligente** al eliminar nodos con hijos

---

## Pre-requisitos

- S2-T3: Modelos `PedPlan`, `PedEje`, `PedTema`, `PedObjetivoEstrategico`, `PedEstrategia`, `PedLineaAccion`
- S1-T3: Permiso `gestionar_catalogos` registrado en Spatie
- Jetstream con Livewire configurado

---

## Pasos

### 1. Crear Rutas Protegidas

Editar `routes/web.php`:

```php
<?php

use App\Http\Controllers\PedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PED Management Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'permission:gestionar_catalogos'
])->group(function () {

    Route::prefix('ped')->name('ped.')->group(function () {
        // Vista principal del árbol
        Route::get('/', [PedController::class, 'index'])->name('index');

        // API endpoints para Livewire (AJAX)
        Route::post('/plan', [PedController::class, 'storePlan'])->name('plan.store');
        Route::put('/plan/{plan}', [PedController::class, 'updatePlan'])->name('plan.update');
        Route::delete('/plan/{plan}', [PedController::class, 'destroyPlan'])->name('plan.destroy');

        Route::post('/eje', [PedController::class, 'storeEje'])->name('eje.store');
        Route::put('/eje/{eje}', [PedController::class, 'updateEje'])->name('eje.update');
        Route::delete('/eje/{eje}', [PedController::class, 'destroyEje'])->name('eje.destroy');

        Route::post('/tema', [PedController::class, 'storeTema'])->name('tema.store');
        Route::put('/tema/{tema}', [PedController::class, 'updateTema'])->name('tema.update');
        Route::delete('/tema/{tema}', [PedController::class, 'destroyTema'])->name('tema.destroy');

        Route::post('/objetivo', [PedController::class, 'storeObjetivo'])->name('objetivo.store');
        Route::put('/objetivo/{objetivo}', [PedController::class, 'updateObjetivo'])->name('objetivo.update');
        Route::delete('/objetivo/{objetivo}', [PedController::class, 'destroyObjetivo'])->name('objetivo.destroy');

        Route::post('/estrategia', [PedController::class, 'storeEstrategia'])->name('estrategia.store');
        Route::put('/estrategia/{estrategia}', [PedController::class, 'updateEstrategia'])->name('estrategia.update');
        Route::delete('/estrategia/{estrategia}', [PedController::class, 'destroyEstrategia'])->name('estrategia.destroy');

        Route::post('/linea', [PedController::class, 'storeLinea'])->name('linea.store');
        Route::put('/linea/{linea}', [PedController::class, 'updateLinea'])->name('linea.update');
        Route::delete('/linea/{linea}', [PedController::class, 'destroyLinea'])->name('linea.destroy');
    });
});
```

---

### 2. Crear Form Requests para Validación

```bash
sail artisan make:request StorePedPlanRequest
sail artisan make:request StorePedEjeRequest
sail artisan make:request StorePedTemaRequest
sail artisan make:request StorePedObjetivoRequest
sail artisan make:request StorePedEstrategiaRequest
sail artisan make:request StorePedLineaAccionRequest
```

Editar `app/Http/Requests/StorePedPlanRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePedPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización manejada por middleware
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'nivel_gobierno' => ['required', 'in:estatal,municipal'],
            'periodo_inicio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo_fin' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                'gt:periodo_inicio'
            ],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del plan es obligatorio.',
            'periodo_fin.gt' => 'El año de fin debe ser posterior al año de inicio.',
        ];
    }
}
```

Editar `app/Http/Requests/StorePedEjeRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedEjeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ped_plan_id' => ['required', 'exists:ped_planes,id'],
            'numero' => ['required', 'string', 'max:10'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.required' => 'El número de eje es obligatorio.',
            'nombre.required' => 'El nombre del eje es obligatorio.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ];
    }
}
```

Crear request base reutilizable `app/Http/Requests/StorePedNodoRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base request para nodos del PED con estructura similar.
 * Extender para cada nivel específico.
 */
abstract class StorePedNodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function commonRules(): array
    {
        return [
            'clave' => ['required', 'string', 'max:40'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave es obligatoria.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ];
    }
}
```

Editar `app/Http/Requests/StorePedLineaAccionRequest.php`:

```php
<?php

namespace App\Http\Requests;

class StorePedLineaAccionRequest extends StorePedNodoRequest
{
    public function rules(): array
    {
        return array_merge(parent::commonRules(), [
            'ped_estrategia_id' => ['required', 'exists:ped_estrategias,id'],
        ]);
    }
}
```

---

### 3. Crear Controlador

```bash
sail artisan make:controller PedController
```

Editar `app/Http/Controllers/PedController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePedEjeRequest;
use App\Http\Requests\StorePedEstrategiaRequest;
use App\Http\Requests\StorePedLineaAccionRequest;
use App\Http\Requests\StorePedObjetivoRequest;
use App\Http\Requests\StorePedPlanRequest;
use App\Http\Requests\StorePedTemaRequest;
use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Illuminate\Http\Request;

class PedController extends Controller
{
    /**
     * Vista principal del árbol PED.
     */
    public function index()
    {
        $planes = PedPlan::with([
            'ejes.temas.objetivosEstrategicos.estrategias.lineasAccion'
        ])->orderBy('activo', 'desc')->orderBy('periodo_inicio', 'desc')->get();

        return view('ped.index', compact('planes'));
    }

    // ============================================
    // PLAN
    // ============================================

    public function storePlan(StorePedPlanRequest $request)
    {
        $plan = PedPlan::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', "Plan '{$plan->nombre}' creado exitosamente.")
            ->with('flash.bannerStyle', 'success');
    }

    public function updatePlan(StorePedPlanRequest $request, PedPlan $plan)
    {
        $plan->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', "Plan actualizado exitosamente.")
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyPlan(PedPlan $plan)
    {
        $nombre = $plan->nombre;
        $hijos = $plan->ejes()->count();

        $plan->delete();

        $mensaje = "Plan '{$nombre}' eliminado.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} ejes y todos sus descendientes.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // EJE
    // ============================================

    public function storeEje(StorePedEjeRequest $request)
    {
        PedEje::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Eje creado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateEje(StorePedEjeRequest $request, PedEje $eje)
    {
        $eje->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Eje actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyEje(PedEje $eje)
    {
        $hijos = $eje->temas()->count();
        $eje->delete();

        $mensaje = "Eje '{$eje->numero}' eliminado.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} temas y sus descendientes.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // TEMA
    // ============================================

    public function storeTema(StorePedTemaRequest $request)
    {
        PedTema::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Tema creado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateTema(StorePedTemaRequest $request, PedTema $tema)
    {
        $tema->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Tema actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyTema(PedTema $tema)
    {
        $hijos = $tema->objetivosEstrategicos()->count();
        $tema->delete();

        $mensaje = "Tema eliminado.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} objetivos y sus descendientes.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // OBJETIVO ESTRATÉGICO
    // ============================================

    public function storeObjetivo(StorePedObjetivoRequest $request)
    {
        PedObjetivoEstrategico::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Objetivo Estratégico creado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateObjetivo(StorePedObjetivoRequest $request, PedObjetivoEstrategico $objetivo)
    {
        $objetivo->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Objetivo Estratégico actualizado exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyObjetivo(PedObjetivoEstrategico $objetivo)
    {
        $hijos = $objetivo->estrategias()->count();
        $objetivo->delete();

        $mensaje = "Objetivo Estratégico eliminado.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} estrategias y sus líneas de acción.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // ESTRATEGIA
    // ============================================

    public function storeEstrategia(StorePedEstrategiaRequest $request)
    {
        PedEstrategia::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Estrategia creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateEstrategia(StorePedEstrategiaRequest $request, PedEstrategia $estrategia)
    {
        $estrategia->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Estrategia actualizada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyEstrategia(PedEstrategia $estrategia)
    {
        $hijos = $estrategia->lineasAccion()->count();
        $estrategia->delete();

        $mensaje = "Estrategia eliminada.";
        if ($hijos > 0) {
            $mensaje .= " Se eliminaron {$hijos} líneas de acción.";
        }

        return redirect()->route('ped.index')
            ->with('flash.banner', $mensaje)
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // LÍNEA DE ACCIÓN
    // ============================================

    public function storeLinea(StorePedLineaAccionRequest $request)
    {
        PedLineaAccion::create($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Línea de Acción creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function updateLinea(StorePedLineaAccionRequest $request, PedLineaAccion $linea)
    {
        $linea->update($request->validated());

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Línea de Acción actualizada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyLinea(PedLineaAccion $linea)
    {
        $linea->delete();

        return redirect()->route('ped.index')
            ->with('flash.banner', 'Línea de Acción eliminada.')
            ->with('flash.bannerStyle', 'success');
    }
}
```

---

### 4. Crear Componente Livewire Principal

```bash
sail artisan make:livewire PedTree
```

Editar `app/Livewire/PedTree.php`:

```php
<?php

namespace App\Livewire;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Livewire\Component;

class PedTree extends Component
{
    public ?int $selectedPlanId = null;
    public ?int $selectedEjeId = null;
    public ?int $selectedTemaId = null;
    public ?int $selectedObjetivoId = null;
    public ?int $selectedEstrategiaId = null;
    public ?int $selectedLineaId = null;

    public string $activeTab = 'plan';
    public array $expandedNodes = [];

    protected $listeners = [
        'planCreated' => '$refresh',
        'planUpdated' => '$refresh',
        'planDeleted' => '$refresh',
        'nodeCreated' => '$refresh',
        'nodeUpdated' => '$refresh',
        'nodeDeleted' => '$refresh',
    ];

    public function mount(?int $planId = null)
    {
        $this->selectedPlanId = $planId;

        // Expandir automáticamente el plan activo
        $activo = PedPlan::where('activo', true)->first();
        if ($activo) {
            $this->expandedNodes["plan-{$activo->id}"] = true;
        }
    }

    /**
     * Toggle expand/collapse de un nodo.
     */
    public function toggleNode(string $nodeKey): void
    {
        if (isset($this->expandedNodes[$nodeKey])) {
            unset($this->expandedNodes[$nodeKey]);
        } else {
            $this->expandedNodes[$nodeKey] = true;
        }
    }

    /**
     * Selecciona un nodo para edición.
     */
    public function selectNode(string $type, int $id): void
    {
        match($type) {
            'plan' => $this->selectedPlanId = $id,
            'eje' => $this->selectedEjeId = $id,
            'tema' => $this->selectedTemaId = $id,
            'objetivo' => $this->selectedObjetivoId = $id,
            'estrategia' => $this->selectedEstrategiaId = $id,
            'linea' => $this->selectedLineaId = $id,
        };

        $this->activeTab = $type;
    }

    /**
     * Obtiene la información de hijos dependientes para confirmación de eliminación.
     */
    public function getDependentsCount(string $type, int $id): array
    {
        return match($type) {
            'plan' => ['ejes' => PedPlan::find($id)?->ejes()->count() ?? 0],
            'eje' => ['temas' => PedEje::find($id)?->temas()->count() ?? 0],
            'tema' => ['objetivos' => PedTema::find($id)?->objetivosEstrategicos()->count() ?? 0],
            'objetivo' => ['estrategias' => PedObjetivoEstrategico::find($id)?->estrategias()->count() ?? 0],
            'estrategia' => ['lineas' => PedEstrategia::find($id)?->lineasAccion()->count() ?? 0],
            'linea' => [],
        };
    }

    public function render()
    {
        $planes = PedPlan::with([
            'ejes.temas.objetivosEstrategicos.estrategias.lineasAccion'
        ])
        ->orderBy('activo', 'desc')
        ->orderBy('periodo_inicio', 'desc')
        ->get();

        return view('livewire.ped-tree', [
            'planes' => $planes,
        ]);
    }
}
```

---

### 5. Crear Componentes Livewire para Formularios

```bash
sail artisan make:livewire PedPlanForm
sail artisan make:livewire PedEjeForm
sail artisan make:livewire PedNodoForm
```

Editar `app/Livewire/PedPlanForm.php`:

```php
<?php

namespace App\Livewire;

use App\Models\PedPlan;
use Livewire\Component;

class PedPlanForm extends Component
{
    public ?PedPlan $plan = null;
    public bool $showModal = false;
    public string $mode = 'create'; // 'create' or 'edit'

    public string $nombre = '';
    public string $nivel_gobierno = 'estatal';
    public int $periodo_inicio = 2025;
    public int $periodo_fin = 2030;
    public bool $activo = false;

    protected function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'nivel_gobierno' => ['required', 'in:estatal,municipal'],
            'periodo_inicio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo_fin' => ['required', 'integer', 'min:2000', 'max:2100', 'gt:periodo_inicio'],
            'activo' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $this->reset();
        $this->mode = 'create';
        $this->showModal = true;
    }

    public function edit(PedPlan $plan): void
    {
        $this->plan = $plan;
        $this->nombre = $plan->nombre;
        $this->nivel_gobierno = $plan->nivel_gobierno;
        $this->periodo_inicio = $plan->periodo_inicio;
        $this->periodo_fin = $plan->periodo_fin;
        $this->activo = $plan->activo;
        $this->mode = 'edit';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->mode === 'create') {
            $plan = PedPlan::create([
                'nombre' => $this->nombre,
                'nivel_gobierno' => $this->nivel_gobierno,
                'periodo_inicio' => $this->periodo_inicio,
                'periodo_fin' => $this->periodo_fin,
                'activo' => $this->activo,
            ]);

            $this->dispatch('planCreated');
            session()->flash('message', "Plan '{$plan->nombre}' creado exitosamente.");
        } else {
            $this->plan->update([
                'nombre' => $this->nombre,
                'nivel_gobierno' => $this->nivel_gobierno,
                'periodo_inicio' => $this->periodo_inicio,
                'periodo_fin' => $this->periodo_fin,
                'activo' => $this->activo,
            ]);

            $this->dispatch('planUpdated');
            session()->flash('message', 'Plan actualizado exitosamente.');
        }

        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->plan) {
            $this->plan->delete();
            $this->dispatch('planDeleted');
            session()->flash('message', 'Plan eliminado exitosamente.');
        }

        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.ped-plan-form');
    }
}
```

Editar `app/Livewire/PedNodoForm.php`:

```php
<?php

namespace App\Livewire;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedTema;
use Livewire\Component;

class PedNodoForm extends Component
{
    public string $tipo = 'eje'; // eje, tema, objetivo, estrategia, linea
    public ?int $parentId = null;
    public ?int $nodoId = null;
    public bool $showModal = false;
    public string $mode = 'create';

    public string $numero = '';
    public string $clave = '';
    public string $nombre = '';
    public string $descripcion = '';

    protected function rules(): array
    {
        $rules = [
            'descripcion' => ['required', 'string', 'max:500'],
        ];

        if (in_array($this->tipo, ['eje', 'tema'])) {
            $rules['numero'] = ['required', 'string', 'max:10'];
            $rules['nombre'] = ['required', 'string', 'max:255'];
        } else {
            $rules['clave'] = ['required', 'string', 'max:40'];
        }

        return $rules;
    }

    public function create(string $tipo, int $parentId): void
    {
        $this->reset(['numero', 'clave', 'nombre', 'descripcion']);
        $this->tipo = $tipo;
        $this->parentId = $parentId;
        $this->nodoId = null;
        $this->mode = 'create';
        $this->showModal = true;
    }

    public function edit(string $tipo, int $nodoId): void
    {
        $this->tipo = $tipo;
        $this->nodoId = $nodoId;
        $this->mode = 'edit';

        $nodo = $this->getNodo();

        if ($nodo) {
            $this->parentId = $this->getParentId($nodo);
            $this->numero = $nodo->numero ?? '';
            $this->clave = $nodo->clave ?? '';
            $this->nombre = $nodo->nombre ?? '';
            $this->descripcion = $nodo->descripcion ?? '';
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->buildDataArray();

        if ($this->mode === 'create') {
            $nodo = $this->createNodo($data);
            $this->dispatch('nodeCreated');
            session()->flash('message', "{$this->getTipoLabel()} creado exitosamente.");
        } else {
            $nodo = $this->updateNodo($data);
            $this->dispatch('nodeUpdated');
            session()->flash('message', "{$this->getTipoLabel()} actualizado exitosamente.");
        }

        $this->showModal = false;
    }

    public function delete(): void
    {
        $nodo = $this->getNodo();

        if ($nodo) {
            $dependientes = $this->getDependientesCount($nodo);
            $nodo->delete();
            $this->dispatch('nodeDeleted');

            $mensaje = "{$this->getTipoLabel()} eliminado.";
            if (!empty($dependientes)) {
                $mensaje .= " Se eliminaron {$dependientes} elementos dependientes.";
            }
            session()->flash('message', $mensaje);
        }

        $this->showModal = false;
    }

    public function getDependientes(): array
    {
        if ($this->mode !== 'edit' || !$this->nodoId) {
            return [];
        }

        $nodo = $this->getNodo();
        return $this->getDependientesData($nodo);
    }

    private function getNodo()
    {
        return match($this->tipo) {
            'eje' => PedEje::find($this->nodoId),
            'tema' => PedTema::find($this->nodoId),
            'objetivo' => PedObjetivoEstrategico::find($this->nodoId),
            'estrategia' => PedEstrategia::find($this->nodoId),
            'linea' => PedLineaAccion::find($this->nodoId),
        };
    }

    private function getParentId($nodo): ?int
    {
        return match($this->tipo) {
            'eje' => $nodo->ped_plan_id,
            'tema' => $nodo->ped_eje_id,
            'objetivo' => $nodo->ped_tema_id,
            'estrategia' => $nodo->ped_objetivo_estrategico_id,
            'linea' => $nodo->ped_estrategia_id,
        };
    }

    private function buildDataArray(): array
    {
        $data = ['descripcion' => $this->descripcion];

        if (in_array($this->tipo, ['eje', 'tema'])) {
            $data['numero'] = $this->numero;
            $data['nombre'] = $this->nombre;
        } else {
            $data['clave'] = $this->clave;
        }

        // Agregar FK del padre
        $data = array_merge($data, match($this->tipo) {
            'eje' => ['ped_plan_id' => $this->parentId],
            'tema' => ['ped_eje_id' => $this->parentId],
            'objetivo' => ['ped_tema_id' => $this->parentId],
            'estrategia' => ['ped_objetivo_estrategico_id' => $this->parentId],
            'linea' => ['ped_estrategia_id' => $this->parentId],
        });

        return $data;
    }

    private function createNodo(array $data)
    {
        return match($this->tipo) {
            'eje' => PedEje::create($data),
            'tema' => PedTema::create($data),
            'objetivo' => PedObjetivoEstrategico::create($data),
            'estrategia' => PedEstrategia::create($data),
            'linea' => PedLineaAccion::create($data),
        };
    }

    private function updateNodo(array $data)
    {
        $nodo = $this->getNodo();
        $nodo->update($data);
        return $nodo;
    }

    private function getDependientesCount($nodo): int
    {
        return match($this->tipo) {
            'eje' => $nodo->temas()->count(),
            'tema' => $nodo->objetivosEstrategicos()->count(),
            'objetivo' => $nodo->estrategias()->count(),
            'estrategia' => $nodo->lineasAccion()->count(),
            'linea' => 0,
        };
    }

    private function getDependientesData($nodo): array
    {
        return match($this->tipo) {
            'eje' => ['temas' => $nodo->temas()->count()],
            'tema' => ['objetivos' => $nodo->objetivosEstrategicos()->count()],
            'objetivo' => ['estrategias' => $nodo->estrategias()->count()],
            'estrategia' => ['lineas' => $nodo->lineasAccion()->count()],
            'linea' => [],
        };
    }

    public function getTipoLabel(): string
    {
        return match($this->tipo) {
            'eje' => 'Eje',
            'tema' => 'Tema',
            'objetivo' => 'Objetivo Estratégico',
            'estrategia' => 'Estrategia',
            'linea' => 'Línea de Acción',
        };
    }

    public function render()
    {
        return view('livewire.ped-nodo-form');
    }
}
```

---

### 6. Crear Vistas Blade

Crear `resources/views/ped/index.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Plan Estatal de Desarrollo
            </h2>
            <livewire:ped-plan-form />
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('message'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('message') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <livewire:ped-tree />
                </div>
            </div>
        </div>
    </div>

    <livewire:ped-nodo-form />
</x-app-layout>
```

Crear `resources/views/livewire/ped-tree.blade.php`:

```blade
<div class="ped-tree" x-data="{ expanded: {{ json_encode($expandedNodes) }} }">

    @if($planes->isEmpty())
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay planes</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo Plan Estatal de Desarrollo.</p>
        </div>
    @endif

    @foreach($planes as $plan)
        <div class="border rounded-lg mb-4 {{ $plan->activo ? 'border-green-300 bg-green-50' : 'border-gray-200' }}">

            {{-- PLAN HEADER --}}
            <div class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50"
                 @click="expanded['plan-{{ $plan->id }}'] = !expanded['plan-{{ $plan->id }}']">

                <div class="flex items-center space-x-3">
                    <span class="transform transition-transform duration-200"
                          :class="expanded['plan-{{ $plan->id }}'] ? 'rotate-90' : ''">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>

                    <div>
                        <h3 class="font-semibold text-lg text-gray-900">{{ $plan->nombre }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ $plan->periodo_inicio }} - {{ $plan->periodo_fin }}
                            @if($plan->activo)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Activo
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button wire:click="$dispatch('edit-plan', { id: {{ $plan->id }} })"
                            class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                        Editar
                    </button>
                </div>
            </div>

            {{-- PLAN CONTENT (EJES) --}}
            <div x-show="expanded['plan-{{ $plan->id }}']" class="border-t bg-gray-50">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">Ejes ({{ $plan->ejes->count() }})</span>
                        <button wire:click="$dispatch('create-nodo', { tipo: 'eje', parentId: {{ $plan->id }} })"
                                class="inline-flex items-center px-2 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Agregar Eje
                        </button>
                    </div>

                    @foreach($plan->ejes as $eje)
                        @include('livewire.partials.ped-eje-node', ['eje' => $eje, 'level' => 1])
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
```

Crear `resources/views/livewire/partials/ped-eje-node.blade.php`:

```blade
<div class="ml-4 mb-2" x-data="{ expanded_{{ $eje->id }}: false }">
    <div class="flex items-center justify-between p-2 rounded hover:bg-gray-100 cursor-pointer"
         @click="expanded_{{ $eje->id }} = !expanded_{{ $eje->id }}">

        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200"
                  :class="expanded_{{ $eje->id }} ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>

            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                {{ $eje->numero }}
            </span>
            <span class="text-sm font-medium text-gray-800">{{ $eje->nombre }}</span>
        </div>

        <div class="flex items-center space-x-2">
            <button wire:click="$dispatch('edit-nodo', { tipo: 'eje', nodoId: {{ $eje->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-900">
                Editar
            </button>
        </div>
    </div>

    <div x-show="expanded_{{ $eje->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Temas ({{ $eje->temas->count() }})</span>
            <button wire:click="$dispatch('create-nodo', { tipo: 'tema', parentId: {{ $eje->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-800">
                + Tema
            </button>
        </div>

        @foreach($eje->temas as $tema)
            @include('livewire.partials.ped-tema-node', ['tema' => $tema])
        @endforeach
    </div>
</div>
```

Crear `resources/views/livewire/partials/ped-tema-node.blade.php`:

```blade
<div class="ml-4 mb-1" x-data="{ expanded_{{ $tema->id }}: false }">
    <div class="flex items-center justify-between p-2 rounded hover:bg-gray-100 cursor-pointer"
         @click="expanded_{{ $tema->id }} = !expanded_{{ $tema->id }}">

        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200"
                  :class="expanded_{{ $tema->id }} ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>

            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                {{ $tema->clave_completa }}
            </span>
            <span class="text-sm text-gray-700">{{ $tema->nombre }}</span>
        </div>

        <button wire:click="$dispatch('edit-nodo', { tipo: 'tema', nodoId: {{ $tema->id }} })"
                class="text-xs text-indigo-600 hover:text-indigo-900">
            Editar
        </button>
    </div>

    <div x-show="expanded_{{ $tema->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Objetivos ({{ $tema->objetivosEstrategicos->count() }})</span>
            <button wire:click="$dispatch('create-nodo', { tipo: 'objetivo', parentId: {{ $tema->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-800">
                + Objetivo
            </button>
        </div>

        @foreach($tema->objetivosEstrategicos as $objetivo)
            @include('livewire.partials.ped-objetivo-node', ['objetivo' => $objetivo])
        @endforeach
    </div>
</div>
```

Crear `resources/views/livewire/partials/ped-objetivo-node.blade.php`:

```blade
<div class="ml-4 mb-1" x-data="{ expanded_{{ $objetivo->id }}: false }">
    <div class="flex items-center justify-between p-2 rounded hover:bg-gray-100 cursor-pointer"
         @click="expanded_{{ $objetivo->id }} = !expanded_{{ $objetivo->id }}">

        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200"
                  :class="expanded_{{ $objetivo->id }} ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>

            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                {{ $objetivo->clave_completa }}
            </span>
            <span class="text-sm text-gray-700">{{ Str::limit($objetivo->descripcion, 50) }}</span>
        </div>

        <button wire:click="$dispatch('edit-nodo', { tipo: 'objetivo', nodoId: {{ $objetivo->id }} })"
                class="text-xs text-indigo-600 hover:text-indigo-900">
            Editar
        </button>
    </div>

    <div x-show="expanded_{{ $objetivo->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Estrategias ({{ $objetivo->estrategias->count() }})</span>
            <button wire:click="$dispatch('create-nodo', { tipo: 'estrategia', parentId: {{ $objetivo->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-800">
                + Estrategia
            </button>
        </div>

        @foreach($objetivo->estrategias as $estrategia)
            @include('livewire.partials.ped-estrategia-node', ['estrategia' => $estrategia])
        @endforeach
    </div>
</div>
```

Crear `resources/views/livewire/partials/ped-estrategia-node.blade.php`:

```blade
<div class="ml-4 mb-1" x-data="{ expanded_{{ $estrategia->id }}: false }">
    <div class="flex items-center justify-between p-2 rounded hover:bg-gray-100 cursor-pointer"
         @click="expanded_{{ $estrategia->id }} = !expanded_{{ $estrategia->id }}">

        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200"
                  :class="expanded_{{ $estrategia->id }} ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>

            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                {{ $estrategia->clave_completa }}
            </span>
            <span class="text-sm text-gray-700">{{ Str::limit($estrategia->descripcion, 50) }}</span>
        </div>

        <button wire:click="$dispatch('edit-nodo', { tipo: 'estrategia', nodoId: {{ $estrategia->id }} })"
                class="text-xs text-indigo-600 hover:text-indigo-900">
            Editar
        </button>
    </div>

    <div x-show="expanded_{{ $estrategia->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Líneas de Acción ({{ $estrategia->lineasAccion->count() }})</span>
            <button wire:click="$dispatch('create-nodo', { tipo: 'linea', parentId: {{ $estrategia->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-800">
                + Línea
            </button>
        </div>

        @foreach($estrategia->lineasAccion as $linea)
            @include('livewire.partials.ped-linea-node', ['linea' => $linea])
        @endforeach
    </div>
</div>
```

Crear `resources/views/livewire/partials/ped-linea-node.blade.php`:

```blade
<div class="ml-4 mb-1 p-2 rounded hover:bg-gray-100">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">
                {{ $linea->clave_completa }}
            </span>
            <span class="text-sm text-gray-700">{{ Str::limit($linea->descripcion, 50) }}</span>
        </div>

        <button wire:click="$dispatch('edit-nodo', { tipo: 'linea', nodoId: {{ $linea->id }} })"
                class="text-xs text-indigo-600 hover:text-indigo-900">
            Editar
        </button>
    </div>
</div>
```

---

### 7. Crear Vistas de Formularios

Crear `resources/views/livewire/ped-plan-form.blade.php`:

```blade
<div>
    <button wire:click="create"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nuevo Plan
    </button>

    <x-dialog-modal wire:model="showModal" maxWidth="lg">
        <x-slot name="title">
            {{ $mode === 'create' ? 'Crear Nuevo Plan' : 'Editar Plan' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="nombre" value="Nombre del Plan" />
                    <x-input id="nombre"
                             type="text"
                             class="mt-1 block w-full"
                             wire:model="nombre" />
                    @error('nombre')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-label for="periodo_inicio" value="Año Inicio" />
                        <x-input id="periodo_inicio"
                                 type="number"
                                 class="mt-1 block w-full"
                                 wire:model="periodo_inicio" />
                        @error('periodo_inicio')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-label for="periodo_fin" value="Año Fin" />
                        <x-input id="periodo_fin"
                                 type="number"
                                 class="mt-1 block w-full"
                                 wire:model="periodo_fin" />
                        @error('periodo_fin')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <x-label for="nivel_gobierno" value="Nivel de Gobierno" />
                    <select id="nivel_gobierno"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            wire:model="nivel_gobierno">
                        <option value="estatal">Estatal</option>
                        <option value="municipal">Municipal</option>
                    </select>
                    @error('nivel_gobierno')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <x-checkbox id="activo" wire:model="activo" />
                    <x-label for="activo" class="ml-2" value="Marcar como plan activo" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>

            @if($mode === 'edit')
                <x-danger-button wire:click="delete" wire:confirm="¿Está seguro de eliminar este plan? Esta acción no se puede deshacer." class="mr-3">
                    Eliminar
                </x-danger-button>
            @endif

            <x-button wire:click="save" class="bg-indigo-600 text-white">
                {{ $mode === 'create' ? 'Crear Plan' : 'Guardar Cambios' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
```

Crear `resources/views/livewire/ped-nodo-form.blade.php`:

```blade
<div x-data
     x-on:edit-nodo.window="$wire.edit($event.detail.tipo, $event.detail.nodoId)"
     x-on:create-nodo.window="$wire.create($event.detail.tipo, $event.detail.parentId)">

    <x-dialog-modal wire:model="showModal" maxWidth="md">
        <x-slot name="title">
            {{ $mode === 'create' ? 'Crear ' . $this->getTipoLabel() : 'Editar ' . $this->getTipoLabel() }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                @if(in_array($tipo, ['eje', 'tema']))
                    <div>
                        <x-label for="numero" value="Número" />
                        <x-input id="numero"
                                 type="text"
                                 class="mt-1 block w-full"
                                 wire:model="numero"
                                 placeholder="Ej: 1, 2, 1.1" />
                        @error('numero')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-label for="nombre" value="Nombre" />
                        <x-input id="nombre"
                                 type="text"
                                 class="mt-1 block w-full"
                                 wire:model="nombre" />
                        @error('nombre')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div>
                        <x-label for="clave" value="Clave" />
                        <x-input id="clave"
                                 type="text"
                                 class="mt-1 block w-full"
                                 wire:model="clave"
                                 placeholder="Ej: 1, 2, 1.1" />
                        @error('clave')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <x-label for="descripcion" value="Descripción" />
                    <textarea id="descripcion"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              rows="3"
                              wire:model="descripcion"
                              maxlength="500"></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ strlen($descripcion) }}/500 caracteres
                    </p>
                    @error('descripcion')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Advertencia de dependientes --}}
                @php($dependientes = $this->getDependientes())
                @if(!empty($dependientes) && $mode === 'edit')
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Advertencia:</strong> Este elemento tiene dependientes:
                                </p>
                                <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                                    @foreach($dependientes as $tipo => $cantidad)
                                        <li>{{ $cantidad }} {{ $tipo }}</li>
                                    @endforeach
                                </ul>
                                <p class="mt-1 text-sm text-yellow-700">
                                    Al eliminar este elemento, todos sus dependientes serán eliminados también.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>

            @if($mode === 'edit')
                <x-danger-button wire:click="delete" class="mr-3">
                    Eliminar
                </x-danger-button>
            @endif

            <x-button wire:click="save" class="bg-indigo-600 text-white">
                {{ $mode === 'create' ? 'Crear' : 'Guardar' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
```

---

### 8. Agregar Navegación al Dashboard

Editar `resources/views/navigation-menu.blade.php` (agregar en el menú):

```blade
@can('gestionar_catalogos')
    <x-nav-link href="{{ route('ped.index') }}" :active="request()->routeIs('ped.*')">
    {{ __('Plan Estatal') }}
    </x-nav-link>
@endcan
```

---

### 9. Crear Tests Funcionales

```bash
sail artisan make:test PedCrudTest
```

Editar `tests/Feature/PedCrudTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PedEje;
use App\Models\PedPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PedCrudTest extends TestCase
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

        $response = $this->actingAs($user)->get(route('ped.index'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('ped.index'));

        $response->assertOk();
        $response->assertSee('Plan Estatal de Desarrollo');
    }

    // ============================================
    // Tests de Plan
    // ============================================

    public function test_crear_plan_desde_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        Livewire::actingAs($user)
            ->test('ped-plan-form')
            ->call('create')
            ->set('nombre', 'Plan de Prueba 2025-2030')
            ->set('periodo_inicio', 2025)
            ->set('periodo_fin', 2030)
            ->set('activo', true)
            ->call('save')
            ->assertDispatched('planCreated');

        $this->assertDatabaseHas('ped_planes', [
            'nombre' => 'Plan de Prueba 2025-2030',
            'activo' => true,
        ]);
    }

    public function test_editar_plan_desde_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Original',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => false,
        ]);

        Livewire::actingAs($user)
            ->test('ped-plan-form')
            ->call('edit', $plan)
            ->set('nombre', 'Plan Editado')
            ->call('save')
            ->assertDispatched('planUpdated');

        $this->assertEquals('Plan Editado', $plan->fresh()->nombre);
    }

    public function test_eliminar_plan_desde_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan a Eliminar',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Livewire::actingAs($user)
            ->test('ped-plan-form')
            ->call('edit', $plan)
            ->call('delete')
            ->assertDispatched('planDeleted');

        $this->assertDatabaseMissing('ped_planes', ['id' => $plan->id]);
    }

    // ============================================
    // Tests de Eje
    // ============================================

    public function test_crear_eje_desde_livewire(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Livewire::actingAs($user)
            ->test('ped-nodo-form')
            ->call('create', 'eje', $plan->id)
            ->set('numero', '1')
            ->set('nombre', 'Eje de Prueba')
            ->set('descripcion', 'Descripción del eje')
            ->call('save')
            ->assertDispatched('nodeCreated');

        $this->assertDatabaseHas('ped_ejes', [
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje de Prueba',
        ]);
    }

    public function test_eliminar_eje_elimina_temas_cascade(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => '1',
            'nombre' => 'Eje Test',
        ]);

        $tema = $eje->temas()->create([
            'numero' => '1',
            'nombre' => 'Tema Test',
        ]);

        Livewire::actingAs($user)
            ->test('ped-nodo-form')
            ->call('edit', 'eje', $eje->id)
            ->call('delete');

        $this->assertDatabaseMissing('ped_ejes', ['id' => $eje->id]);
        $this->assertDatabaseMissing('ped_temas', ['id' => $tema->id]);
    }

    // ============================================
    // Tests de Validación
    // ============================================

    public function test_validacion_periodo_fin_mayor_que_inicio(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        Livewire::actingAs($user)
            ->test('ped-plan-form')
            ->call('create')
            ->set('nombre', 'Plan Test')
            ->set('periodo_inicio', 2030)
            ->set('periodo_fin', 2025)
            ->call('save')
            ->assertHasErrors(['periodo_fin']);
    }

    public function test_validacion_descripcion_max_500_caracteres(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $plan = PedPlan::create([
            'nombre' => 'Plan Test',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
        ]);

        Livewire::actingAs($user)
            ->test('ped-nodo-form')
            ->call('create', 'eje', $plan->id)
            ->set('numero', '1')
            ->set('nombre', 'Eje Test')
            ->set('descripcion', str_repeat('a', 501))
            ->call('save')
            ->assertHasErrors(['descripcion']);
    }

    // ============================================
    // Test de Constraint Plan Único Activo
    // ============================================

    public function test_no_puede_haber_dos_planes_activos(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        // Crear primer plan activo
        PedPlan::create([
            'nombre' => 'Plan Activo 1',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);

        // Intentar crear segundo plan activo debe fallar
        $this->expectException(\Illuminate\Database\QueryException::class);

        PedPlan::create([
            'nombre' => 'Plan Activo 2',
            'periodo_inicio' => 2025,
            'periodo_fin' => 2030,
            'activo' => true,
        ]);
    }
}
```

---

### 10. Agregar Middleware de Permiso

Verificar que el middleware de Spatie esté registrado en `bootstrap/app.php` (ya hecho en S1-T3):

```php
$middleware->alias([
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
]);
```

---

### 11. Ejecutar y Verificar

```bash
# Compilar assets
sail npm run build

# Ejecutar tests
sail artisan test --filter PedCrudTest

# Acceder a la aplicación
# http://localhost/ped
```

Verificación manual:

1. Iniciar sesión como usuario con permiso `gestionar_catalogos`
2. Navegar a `/ped`
3. Crear un nuevo plan
4. Agregar ejes, temas, objetivos, estrategias y líneas de acción
5. Editar y eliminar nodos
6. Verificar que la eliminación en cascada funciona correctamente
7. Intentar crear un segundo plan activo (debe fallar)

---

## Criterios de Aceptación

- [ ] Ruta `/ped` protegida con middleware `permission:gestionar_catalogos`
- [ ] Componente `PedTree` renderiza árbol colapsable de 6 niveles
- [ ] Componente `PedPlanForm` permite crear/editar/eliminar planes
- [ ] Componente `PedNodoForm` permite crear/editar/eliminar ejes, temas, objetivos, estrategias y líneas
- [ ] Validación reactiva funciona sin recarga de página
- [ ] Modal de eliminación muestra advertencia de hijos dependientes
- [ ] Alpine.js maneja collapse/expand sin recargas Livewire
- [ ] Tests pasan (10 assertions)
- [ ] Usuario sin permiso recibe 403
- [ ] Responsive en pantallas >= 768px
- [ ] Documentación actualizada

---

## Notas

### Performance con Alpine.js

El collapse/expand se maneja con Alpine.js (`x-show`), no con Livewire. Esto evita recargas innecesarias:

```blade
<div x-data="{ expanded: false }">
    <button @click="expanded = !expanded">Toggle</button>
    <div x-show="expanded">Contenido</div>
</div>
```

### Patrón Reutilizable

Este componente establece el patrón para:

- **S2-T7**: CRUD de Programas Derivados
- **S2-T8**: Gestión de Matriz de Alineación

### Observer para Embeddings

En S2-T10 se implementará un Observer que regenere embeddings cuando se modifiquen nodos del PED.

---
