# Plan: S2-T8 — Interfaz de Matriz de Alineación

**Ticket:** S2-T8
**Tipo:** feat
**Rama:** `feat/S2-T8-interfaz-matriz-alineacion`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T5 (Tablas pivote), S2-T6 (PED), S1-T3 (Permisos)

---

## Contexto

Interfaz para que el planeador configure las relaciones de alineación entre los diferentes niveles de la cascada de planes. Permite visualizar la trazabilidad completa desde una Línea de Acción del PED hasta los ODS de la Agenda 2030.

**Cadena de Alineación:**

```
┌─────────────────────────────────────────────────────────────────────┐
│                          AGENDA 2030                                │
│                    ODS Objetivo → ODS Meta                          │
└─────────────────────────────────────────────────────────────────────┘
                              ↑
                    ┌─────────┴─────────┐
                    │ alineacion_pnd_ods │
                    └─────────┬─────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                              PND                                    │
│                    Eje → Objetivo → Estrategia                      │
└─────────────────────────────────────────────────────────────────────┘
                              ↑
                    ┌─────────┴─────────┐
                    │ alineacion_ped_pnd │
                    └─────────┬─────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                              PED                                    │
│  Plan → Eje → Tema → Obj. Estratégico → Estrategia → Línea Acción   │
└─────────────────────────────────────────────────────────────────────┘
                              ↑
               ┌──────────────┴──────────────┐
               │ alineacion_linea_programa   │
               └──────────────┬──────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                       PROGRAMAS DERIVADOS                           │
│                      Programa → Objetivo                            │
└─────────────────────────────────────────────────────────────────────┘
```

---

**Esta versión implementa:**
- Uso de `<x-app-layout>` de Jetstream (no se reemplaza)
- Componentes `<x-page.container>`, `<x-page.header>`, `<x-dialog-modal>`
- Vistas organizadas por dominio: `resources/views/cascade/alineacion/`
- Rutas organizadas en `routes/web/cascade.php`
- Sintaxis moderna de componentes Jetstream (sin prefijo `x-jet-`)

---

## Pre-requisitos

- S2-T5: Tablas `alineacion_ped_pnd`, `alineacion_pnd_ods`, `alineacion_linea_programa`
- S2-T6: PED con estructura completa
- S2-T1, S2-T2: Catálogos ODS y PND cargados
- S2-T4: Programas Derivados con objetivos
- S1-T3: Permiso `gestionar_catalogos`
- Componentes base de página creados (`x-page.container`, `x-page.header`)

---

## Pasos

### 1. Crear Archivo de Rutas por Dominio

Agregar a `routes/web/cascade.php`:

```php
<?php

use App\Http\Controllers\Cascade\MatrizAlineacionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la Cascada de Planes (continuación)
|--------------------------------------------------------------------------
*/

// ... rutas ODS, PND, PED existentes...

// ============================================
// MATRIZ DE ALINEACIÓN
// ============================================
Route::prefix('alineacion')->name('alineacion.')->group(function () {
    
    // Vista principal
    Route::get('/', [MatrizAlineacionController::class, 'index'])->name('index');
    
    // Alineación PED ↔ PND
    Route::prefix('ped-pnd')->name('ped-pnd.')->group(function () {
        Route::post('/', [MatrizAlineacionController::class, 'storePedPnd'])->name('store');
        Route::delete('/{pedObjetivo}/{pndObjetivo}', [MatrizAlineacionController::class, 'destroyPedPnd'])->name('destroy');
    });
    
    // Alineación PND ↔ ODS
    Route::prefix('pnd-ods')->name('pnd-ods.')->group(function () {
        Route::post('/', [MatrizAlineacionController::class, 'storePndOds'])->name('store');
        Route::delete('/{pndObjetivo}/{odsMeta}', [MatrizAlineacionController::class, 'destroyPndOds'])->name('destroy');
    });
    
    // Alineación Línea ↔ Programa Derivado
    Route::prefix('linea-programa')->name('linea-programa.')->group(function () {
        Route::post('/', [MatrizAlineacionController::class, 'storeLineaPrograma'])->name('store');
        Route::delete('/{linea}/{programaObjetivo}', [MatrizAlineacionController::class, 'destroyLineaPrograma'])->name('destroy');
    });
    
    // Búsquedas para selectores
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/ped-objetivos', [MatrizAlineacionController::class, 'searchPedObjetivos'])->name('ped-objetivos');
        Route::get('/pnd-objetivos', [MatrizAlineacionController::class, 'searchPndObjetivos'])->name('pnd-objetivos');
        Route::get('/ods-metas', [MatrizAlineacionController::class, 'searchOdsMetas'])->name('ods-metas');
        Route::get('/lineas-accion', [MatrizAlineacionController::class, 'searchLineasAccion'])->name('lineas-accion');
        Route::get('/programas-objetivos', [MatrizAlineacionController::class, 'searchProgramasObjetivos'])->name('programas-objetivos');
    });
    
    // Vista de cadena completa
    Route::get('/cadena/{lineaAccion}', [MatrizAlineacionController::class, 'showCadena'])->name('cadena.show');
});
```

---

### 2. Crear Controlador (Organizado por Dominio)

```bash
mkdir -p app/Http/Controllers/Cascade
sail artisan make:controller Cascade/MatrizAlineacionController
```

Editar `app/Http/Controllers/Cascade/MatrizAlineacionController.php`:

```php
<?php

namespace App\Http\Controllers\Cascade;

use App\Http\Controllers\Controller;
use App\Models\OdsMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MatrizAlineacionController extends Controller
{
    /**
     * Vista principal de la Matriz de Alineación.
     */
    public function index()
    {
        // Cargar alineaciones existentes con eager loading
        $alineacionesPedPnd = PedObjetivoEstrategico::with(['pndObjetivos.eje', 'tema.eje.plan'])
            ->whereHas('pndObjetivos')
            ->get();

        $alineacionesPndOds = PndObjetivo::with(['odsMetas.objetivo', 'eje'])
            ->whereHas('odsMetas')
            ->get();

        $alineacionesLineaPrograma = PedLineaAccion::with([
            'programasDerivadosObjetivos.programa',
            'estrategia.objetivoEstrategico.tema.eje.plan'
        ])
            ->whereHas('programasDerivadosObjetivos')
            ->get();

        return view('cascade.alineacion.index', compact(
            'alineacionesPedPnd',
            'alineacionesPndOds',
            'alineacionesLineaPrograma'
        ));
    }

    // ============================================
    // PED ↔ PND
    // ============================================

    public function storePedPnd(Request $request)
    {
        $request->validate([
            'ped_objetivo_estrategico_id' => 'required|exists:ped_objetivos_estrategicos,id',
            'pnd_objetivo_id' => 'required|exists:pnd_objetivos,id',
        ]);

        $pedObjetivo = PedObjetivoEstrategico::find($request->ped_objetivo_estrategico_id);
        $pedObjetivo->pndObjetivos()->syncWithoutDetaching([$request->pnd_objetivo_id]);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación PED ↔ PND creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyPedPnd(PedObjetivoEstrategico $pedObjetivo, PndObjetivo $pndObjetivo)
    {
        $pedObjetivo->pndObjetivos()->detach($pndObjetivo->id);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación eliminada.')
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // PND ↔ ODS
    // ============================================

    public function storePndOds(Request $request)
    {
        $request->validate([
            'pnd_objetivo_id' => 'required|exists:pnd_objetivos,id',
            'ods_meta_id' => 'required|exists:ods_metas,id',
        ]);

        $pndObjetivo = PndObjetivo::find($request->pnd_objetivo_id);
        $pndObjetivo->odsMetas()->syncWithoutDetaching([$request->ods_meta_id]);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación PND ↔ ODS creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyPndOds(PndObjetivo $pndObjetivo, OdsMeta $odsMeta)
    {
        $pndObjetivo->odsMetas()->detach($odsMeta->id);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación eliminada.')
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // LÍNEA ↔ PROGRAMA DERIVADO
    // ============================================

    public function storeLineaPrograma(Request $request)
    {
        $request->validate([
            'ped_linea_accion_id' => 'required|exists:ped_lineas_accion,id',
            'programa_derivado_objetivo_id' => 'required|exists:programas_derivados_objetivos,id',
        ]);

        $linea = PedLineaAccion::find($request->ped_linea_accion_id);
        $linea->programasDerivadosObjetivos()->syncWithoutDetaching([$request->programa_derivado_objetivo_id]);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación Línea ↔ Programa creada exitosamente.')
            ->with('flash.bannerStyle', 'success');
    }

    public function destroyLineaPrograma(PedLineaAccion $linea, ProgramaDerivadoObjetivo $programaObjetivo)
    {
        $linea->programasDerivadosObjetivos()->detach($programaObjetivo->id);

        return redirect()->route('cascade.alineacion.index')
            ->with('flash.banner', 'Alineación eliminada.')
            ->with('flash.bannerStyle', 'success');
    }

    // ============================================
    // BÚSQUEDAS PARA SELECTORES
    // ============================================

    public function searchPedObjetivos(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = PedObjetivoEstrategico::with('tema.eje.plan')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->orWhereHas('tema', fn($q) => $q->where('nombre', 'ilike', "%{$search}%"))
            ->limit(20)
            ->get()
            ->map(fn($obj) => [
                'id' => $obj->id,
                'text' => $obj->clave_completa . ' - ' . Str::limit($obj->descripcion, 60),
                'clave' => $obj->clave_completa,
                'plan' => $obj->tema->eje->plan->nombre ?? null,
            ]);

        return response()->json(['results' => $resultados]);
    }

    public function searchPndObjetivos(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = PndObjetivo::with('eje')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->orWhere('clave', 'ilike', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn($obj) => [
                'id' => $obj->id,
                'text' => $obj->clave . ' - ' . Str::limit($obj->descripcion, 60),
                'clave' => $obj->clave,
                'eje' => $obj->eje->nombre ?? null,
            ]);

        return response()->json(['results' => $resultados]);
    }

    public function searchOdsMetas(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = OdsMeta::with('objetivo')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->orWhere('clave', 'ilike', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn($meta) => [
                'id' => $meta->id,
                'text' => $meta->clave . ' - ' . Str::limit($meta->descripcion, 60),
                'clave' => $meta->clave,
                'ods' => 'ODS ' . $meta->objetivo->numero . ': ' . $meta->objetivo->nombre,
            ]);

        return response()->json(['results' => $resultados]);
    }

    public function searchLineasAccion(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = PedLineaAccion::with('estrategia.objetivoEstrategico.tema.eje.plan')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn($linea) => [
                'id' => $linea->id,
                'text' => $linea->clave_completa . ' - ' . Str::limit($linea->descripcion, 60),
                'clave' => $linea->clave_completa,
                'plan' => $linea->plan->nombre ?? null,
            ]);

        return response()->json(['results' => $resultados]);
    }

    public function searchProgramasObjetivos(Request $request)
    {
        $search = $request->get('q', '');

        $resultados = ProgramaDerivadoObjetivo::with('programa')
            ->where('descripcion', 'ilike', "%{$search}%")
            ->limit(20)
            ->get()
            ->map(fn($obj) => [
                'id' => $obj->id,
                'text' => $obj->clave_completa . ' - ' . Str::limit($obj->descripcion, 60),
                'clave' => $obj->clave_completa,
                'programa' => $obj->programa->nombre,
                'tipo' => $obj->programa->tipo->label(),
            ]);

        return response()->json(['results' => $resultados]);
    }

    // ============================================
    // VISTA DE CADENA COMPLETA
    // ============================================

    public function showCadena(PedLineaAccion $lineaAccion)
    {
        $lineaAccion->load([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas.objetivo',
            'estrategia.objetivoEstrategico.tema.eje.plan',
            'programasDerivadosObjetivos.programa',
        ]);

        // Construir cadena completa
        $cadena = [
            'linea_accion' => $lineaAccion,
            'estrategia' => $lineaAccion->estrategia,
            'objetivo_estrategico' => $lineaAccion->estrategia->objetivoEstrategico,
            'tema' => $lineaAccion->estrategia->objetivoEstrategico->tema,
            'eje' => $lineaAccion->estrategia->objetivoEstrategico->tema->eje,
            'plan' => $lineaAccion->estrategia->objetivoEstrategico->tema->eje->plan,
            'pnd_objetivos' => $lineaAccion->estrategia->objetivoEstrategico->pndObjetivos,
            'ods_metas' => $lineaAccion->estrategia->objetivoEstrategico->pndObjetivos->flatMap->odsMetas->unique('id'),
            'programas_objetivos' => $lineaAccion->programasDerivadosObjetivos,
        ];

        return view('cascade.alineacion.cadena', compact('cadena'));
    }
}
```

---

### 3. Crear Componentes Livewire

```bash
sail artisan make:livewire Cascade/MatrizAlineacionManager
sail artisan make:livewire Cascade/AlineacionPedPnd
sail artisan make:livewire Cascade/AlineacionPndOds
sail artisan make:livewire Cascade/AlineacionLineaPrograma
sail artisan make:livewire Cascade/CadenaAlineacion
```

Editar `app/Livewire/Cascade/MatrizAlineacionManager.php`:

```php
<?php

namespace App\Livewire\Cascade;

use Livewire\Component;

class MatrizAlineacionManager extends Component
{
    public string $activeTab = 'ped-pnd';

    protected $listeners = [
        'alineacionCreada' => '$refresh',
        'alineacionEliminada' => '$refresh',
    ];

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getStatsProperty(): array
    {
        return [
            'ped_pnd' => \DB::table('alineacion_ped_pnd')->count(),
            'pnd_ods' => \DB::table('alineacion_pnd_ods')->count(),
            'linea_programa' => \DB::table('alineacion_linea_programa')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.cascade.matriz-alineacion-manager');
    }
}
```

---

### 4. Crear Vistas con Nueva Arquitectura

Crear `resources/views/cascade/alineacion/index.blade.php`:

```blade
<x-app-layout>
    
    {{-- Slot Header de Jetstream --}}
    <x-slot name="header">
        <x-page.header 
            title="Matriz de Alineación" 
            subtitle="Configure las relaciones entre los niveles de la cascada de planeación"
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
            ['label' => 'Matriz de Alineación']
        ]"
    >
        
        <livewire:cascade.matriz-alineacion-manager />

    </x-page.container>

</x-app-layout>
```

---

Crear `resources/views/livewire/cascade/matriz-alineacion-manager.blade.php`:

```blade
<div class="space-y-6">

    {{-- Header con Stats --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Resumen de Alineaciones</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Vincule los diferentes niveles de la cascada de planeación.
                </p>
            </div>

            @php($stats = $this->stats)
            <div class="mt-4 md:mt-0 flex items-center space-x-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                    PED ↔ PND: {{ $stats['ped_pnd'] }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    PND ↔ ODS: {{ $stats['pnd_ods'] }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    Línea ↔ Prog: {{ $stats['linea_programa'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white shadow sm:rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button wire:click="setActiveTab('ped-pnd')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'ped-pnd' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    PED ↔ PND
                </button>
                <button wire:click="setActiveTab('pnd-ods')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'pnd-ods' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    PND ↔ ODS
                </button>
                <button wire:click="setActiveTab('linea-programa')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'linea-programa' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Línea ↔ Programa Derivado
                </button>
            </nav>
        </div>

        <div class="p-6">
            @if($activeTab === 'ped-pnd')
                <livewire:cascade.alineacion-ped-pnd :key="'ped-pnd-' . rand()" />
            @elseif($activeTab === 'pnd-ods')
                <livewire:cascade.alineacion-pnd-ods :key="'pnd-ods-' . rand()" />
            @else
                <livewire:cascade.alineacion-linea-programa :key="'linea-programa-' . rand()" />
            @endif
        </div>
    </div>

    {{-- Diagrama de Cadena --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h4 class="text-sm font-medium text-gray-900 mb-4">Diagrama de Cadena de Alineación</h4>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex flex-col items-center space-y-2 text-sm">
                <div class="bg-rose-100 text-rose-800 px-4 py-2 rounded font-medium">
                    ODS Meta
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <div class="bg-green-100 text-green-800 px-4 py-2 rounded font-medium">
                    PND Objetivo
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <div class="bg-amber-100 text-amber-800 px-4 py-2 rounded font-medium">
                    PED Objetivo Estratégico
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded font-medium">
                    PED Estrategia → Línea de Acción
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <div class="bg-purple-100 text-purple-800 px-4 py-2 rounded font-medium">
                    Programa Derivado Objetivo
                </div>
            </div>
        </div>
    </div>
</div>
```

---

Crear `resources/views/livewire/cascade/alineacion-ped-pnd.blade.php`:

```blade
<div class="space-y-6">

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

    {{-- Botón para mostrar formulario --}}
    <div class="flex justify-end">
        <button wire:click="toggleForm"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Alineación
        </button>
    </div>

    {{-- Formulario de creación --}}
    @if($showForm)
        <div class="bg-gray-50 rounded-lg p-6 border-2 border-dashed border-gray-300">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Crear Nueva Alineación PED ↔ PND</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Selector PED --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Objetivo Estratégico PED
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchPed"
                           placeholder="Buscar objetivo..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->ped_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->ped_resultados as $ped)
                                <button wire:click="selectPed({{ $ped->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedPedId === $ped->id ? 'bg-indigo-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $ped->clave_completa }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ Str::limit($ped->descripcion, 50) }}
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedPedId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-indigo-100 text-indigo-800 text-sm">
                            Seleccionado: {{ App\Models\PedObjetivoEstrategico::find($selectedPedId)->clave_completa }}
                        </div>
                    @endif
                </div>

                {{-- Selector PND --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Objetivo PND
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchPnd"
                           placeholder="Buscar objetivo..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->pnd_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->pnd_resultados as $pnd)
                                <button wire:click="selectPnd({{ $pnd->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedPndId === $pnd->id ? 'bg-green-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $pnd->clave }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ Str::limit($pnd->descripcion, 50) }}
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedPndId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-800 text-sm">
                            Seleccionado: {{ App\Models\PndObjetivo::find($selectedPndId)->clave }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex justify-end space-x-3">
                <x-secondary-button wire:click="toggleForm">
                    Cancelar
                </x-secondary-button>

                <x-primary-button wire:click="crearAlineacion" wire:disabled="{{ !$selectedPedId || !$selectedPndId }}">
                    Crear Alineación
                </x-primary-button>
            </div>
        </div>
    @endif

    {{-- Lista de alineaciones existentes --}}
    <div>
        <h4 class="text-sm font-medium text-gray-900 mb-4">
            Alineaciones Existentes ({{ $this->alineaciones->count() }})
        </h4>

        @if($this->alineaciones->isEmpty())
            <div class="text-center py-8 text-gray-500">
                No hay alineaciones registradas. Cree la primera.
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->alineaciones as $ped)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                        PED
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $ped->clave_completa }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">
                                    {{ Str::limit($ped->descripcion, 100) }}
                                </p>

                                <div class="space-y-2">
                                    @foreach($ped->pndObjetivos as $pnd)
                                        <div class="flex items-center justify-between bg-white rounded border p-2">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                                </svg>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $pnd->clave }}
                                                </span>
                                                <span class="text-sm text-gray-700">
                                                    {{ Str::limit($pnd->descripcion, 50) }}
                                                </span>
                                            </div>
                                            <button wire:click="eliminarAlineacion({{ $ped->id }}, {{ $pnd->id }})"
                                                    wire:confirm="¿Eliminar esta alineación?"
                                                    class="text-red-600 hover:text-red-900 text-xs font-medium">
                                                Eliminar
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
```

---

Crear `resources/views/livewire/cascade/alineacion-pnd-ods.blade.php`:

```blade
<div class="space-y-6">

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

    {{-- Botón para mostrar formulario --}}
    <div class="flex justify-end">
        <button wire:click="toggleForm"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Alineación
        </button>
    </div>

    {{-- Formulario de creación --}}
    @if($showForm)
        <div class="bg-gray-50 rounded-lg p-6 border-2 border-dashed border-gray-300">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Crear Nueva Alineación PND ↔ ODS</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Selector PND --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Objetivo PND
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchPnd"
                           placeholder="Buscar objetivo..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->pnd_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->pnd_resultados as $pnd)
                                <button wire:click="selectPnd({{ $pnd->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedPndId === $pnd->id ? 'bg-green-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $pnd->clave }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ Str::limit($pnd->descripcion, 50) }}
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedPndId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-800 text-sm">
                            Seleccionado: {{ App\Models\PndObjetivo::find($selectedPndId)->clave }}
                        </div>
                    @endif
                </div>

                {{-- Selector ODS --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Meta ODS
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchOds"
                           placeholder="Buscar meta ODS..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->ods_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->ods_resultados as $ods)
                                <button wire:click="selectOds({{ $ods->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedOdsId === $ods->id ? 'bg-rose-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $ods->clave }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        ODS {{ $ods->objetivo->numero }}: {{ Str::limit($ods->descripcion, 40) }}
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedOdsId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-rose-100 text-rose-800 text-sm">
                            Seleccionado: {{ App\Models\OdsMeta::find($selectedOdsId)->clave }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex justify-end space-x-3">
                <x-secondary-button wire:click="toggleForm">
                    Cancelar
                </x-secondary-button>

                <x-primary-button wire:click="crearAlineacion" wire:disabled="{{ !$selectedPndId || !$selectedOdsId }}">
                    Crear Alineación
                </x-primary-button>
            </div>
        </div>
    @endif

    {{-- Lista de alineaciones existentes --}}
    <div>
        <h4 class="text-sm font-medium text-gray-900 mb-4">
            Alineaciones Existentes ({{ $this->alineaciones->count() }})
        </h4>

        @if($this->alineaciones->isEmpty())
            <div class="text-center py-8 text-gray-500">
                No hay alineaciones registradas. Cree la primera.
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->alineaciones as $pnd)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        PND
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $pnd->clave }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">
                                    {{ Str::limit($pnd->descripcion, 100) }}
                                </p>

                                <div class="space-y-2">
                                    @foreach($pnd->odsMetas as $ods)
                                        <div class="flex items-center justify-between bg-white rounded border p-2">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                                </svg>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">
                                                    {{ $ods->clave }}
                                                </span>
                                                <span class="text-sm text-gray-700">
                                                    {{ Str::limit($ods->descripcion, 50) }}
                                                </span>
                                            </div>
                                            <button wire:click="eliminarAlineacion({{ $pnd->id }}, {{ $ods->id }})"
                                                    wire:confirm="¿Eliminar esta alineación?"
                                                    class="text-red-600 hover:text-red-900 text-xs font-medium">
                                                Eliminar
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
```

---

Crear `resources/views/livewire/cascade/alineacion-linea-programa.blade.php`:

```blade
<div class="space-y-6">

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

    {{-- Botón para mostrar formulario --}}
    <div class="flex justify-end">
        <button wire:click="toggleForm"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Alineación
        </button>
    </div>

    {{-- Formulario de creación --}}
    @if($showForm)
        <div class="bg-gray-50 rounded-lg p-6 border-2 border-dashed border-gray-300">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Crear Nueva Alineación Línea ↔ Programa Derivado</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Selector Línea de Acción --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Línea de Acción PED
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchLinea"
                           placeholder="Buscar línea de acción..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->linea_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->linea_resultados as $linea)
                                <button wire:click="selectLinea({{ $linea->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedLineaId === $linea->id ? 'bg-blue-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $linea->clave_completa }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ Str::limit($linea->descripcion, 50) }}
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedLineaId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-800 text-sm">
                            Seleccionado: {{ App\Models\PedLineaAccion::find($selectedLineaId)->clave_completa }}
                        </div>
                    @endif
                </div>

                {{-- Selector Programa Derivado --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Objetivo de Programa Derivado
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchPrograma"
                           placeholder="Buscar objetivo..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->programa_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->programa_resultados as $prog)
                                <button wire:click="selectPrograma({{ $prog->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedProgramaId === $prog->id ? 'bg-purple-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $prog->clave_completa }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $prog->programa->nombre }} ({{ $prog->programa->tipo->label() }})
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedProgramaId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-800 text-sm">
                            Seleccionado: {{ App\Models\ProgramaDerivadoObjetivo::find($selectedProgramaId)->clave_completa }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex justify-end space-x-3">
                <x-secondary-button wire:click="toggleForm">
                    Cancelar
                </x-secondary-button>

                <x-primary-button wire:click="crearAlineacion" wire:disabled="{{ !$selectedLineaId || !$selectedProgramaId }}">
                    Crear Alineación
                </x-primary-button>
            </div>
        </div>
    @endif

    {{-- Lista de alineaciones existentes --}}
    <div>
        <h4 class="text-sm font-medium text-gray-900 mb-4">
            Alineaciones Existentes ({{ $this->alineaciones->count() }})
        </h4>

        @if($this->alineaciones->isEmpty())
            <div class="text-center py-8 text-gray-500">
                No hay alineaciones registradas. Cree la primera.
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->alineaciones as $linea)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        Línea
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $linea->clave_completa }}
                                    </span>
                                    <button wire:click="$dispatch('verCadena', { lineaId: {{ $linea->id }} })"
                                            class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
                                        Ver cadena completa
                                    </button>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">
                                    {{ Str::limit($linea->descripcion, 100) }}
                                </p>

                                <div class="space-y-2">
                                    @foreach($linea->programasDerivadosObjetivos as $prog)
                                        <div class="flex items-center justify-between bg-white rounded border p-2">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                                </svg>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    {{ $prog->clave_completa }}
                                                </span>
                                                <span class="text-sm text-gray-700">
                                                    {{ $prog->programa->nombre }}
                                                </span>
                                            </div>
                                            <button wire:click="eliminarAlineacion({{ $linea->id }}, {{ $prog->id }})"
                                                    wire:confirm="¿Eliminar esta alineación?"
                                                    class="text-red-600 hover:text-red-900 text-xs font-medium">
                                                Eliminar
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
```

---

Crear `resources/views/livewire/cascade/cadena-alineacion.blade.php`:

```blade
<x-dialog-modal wire:model="showModal" max-width="4xl">
    <x-slot name="title">
        Cadena de Alineación Completa
    </x-slot>

    <x-slot name="content">
        @php($cadena = $this->cadena)

        @if($cadena)
            <div class="space-y-4">

                {{-- PED --}}
                <div class="border-l-4 border-blue-500 pl-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Plan Estatal de Desarrollo</div>
                    <div class="text-sm font-medium text-gray-900">{{ $cadena['plan']->nombre }}</div>
                </div>

                <div class="border-l-4 border-blue-400 pl-4 ml-4">
                    <div class="text-xs text-gray-500 mb-1">Eje</div>
                    <div class="text-sm text-gray-700">{{ $cadena['eje']->numero }}. {{ $cadena['eje']->nombre }}</div>
                </div>

                <div class="border-l-4 border-blue-300 pl-4 ml-8">
                    <div class="text-xs text-gray-500 mb-1">Tema</div>
                    <div class="text-sm text-gray-700">{{ $cadena['tema']->clave_completa }} - {{ $cadena['tema']->nombre }}</div>
                </div>

                <div class="border-l-4 border-amber-500 pl-4 ml-12">
                    <div class="text-xs text-gray-500 mb-1">Objetivo Estratégico</div>
                    <div class="text-sm font-medium text-gray-900">{{ $cadena['objetivo_estrategico']->clave_completa }}</div>
                    <div class="text-xs text-gray-600">{{ Str::limit($cadena['objetivo_estrategico']->descripcion, 100) }}</div>
                </div>

                <div class="border-l-4 border-emerald-500 pl-4 ml-12">
                    <div class="text-xs text-gray-500 mb-1">Estrategia</div>
                    <div class="text-sm text-gray-700">{{ $cadena['estrategia']->clave_completa }}</div>
                </div>

                <div class="border-l-4 border-indigo-500 pl-4 ml-16">
                    <div class="text-xs text-gray-500 mb-1">Línea de Acción</div>
                    <div class="text-sm font-medium text-gray-900">{{ $cadena['linea_accion']->clave_completa }}</div>
                    <div class="text-xs text-gray-600">{{ Str::limit($cadena['linea_accion']->descripcion, 100) }}</div>
                </div>

                {{-- PND Objetivos --}}
                @if($cadena['pnd_objetivos']->isNotEmpty())
                    <div class="border-l-4 border-green-600 pl-4 ml-12 mt-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Objetivos PND Alineados</div>
                        @foreach($cadena['pnd_objetivos'] as $pnd)
                            <div class="bg-green-50 rounded p-2 mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        {{ $pnd->clave }}
                                    </span>
                                    <span class="text-sm text-gray-700">{{ Str::limit($pnd->descripcion, 60) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ODS Metas --}}
                @if($cadena['ods_metas']->isNotEmpty())
                    <div class="border-l-4 border-rose-500 pl-4 ml-12 mt-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Metas ODS Contribuidas</div>
                        @foreach($cadena['ods_metas'] as $ods)
                            <div class="bg-rose-50 rounded p-2 mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">
                                        {{ $ods->clave }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        ODS {{ $ods->objetivo->numero }}
                                    </span>
                                    <span class="text-sm text-gray-700">{{ Str::limit($ods->descripcion, 50) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Programas Derivados --}}
                @if($cadena['programas_objetivos']->isNotEmpty())
                    <div class="border-l-4 border-purple-500 pl-4 ml-16 mt-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Programas Derivados Vinculados</div>
                        @foreach($cadena['programas_objetivos'] as $prog)
                            <div class="bg-purple-50 rounded p-2 mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ $prog->clave_completa }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ $prog->programa->tipo->label() }}
                                    </span>
                                    <span class="text-sm text-gray-700">{{ $prog->programa->nombre }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($cadena['pnd_objetivos']->isEmpty() && $cadena['programas_objetivos']->isEmpty())
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 ml-12 mt-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Esta Línea de Acción no tiene alineaciones registradas con PND ni Programas Derivados.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button wire:click="closeModal">
            Cerrar
        </x-secondary-button>
    </x-slot>
</x-dialog-modal>
```

---

### 5. Actualizar Navegación al Menú

Editar `resources/views/navigation-menu.blade.php`:

```blade
@can('gestionar_catalogos')
    <x-nav-link href="{{ route('cascade.ped.index') }}" :active="request()->routeIs('cascade.ped.*')">
        {{ __('Plan Estatal') }}
    </x-nav-link>

    <x-nav-link href="{{ route('programs.derivados.index') }}" :active="request()->routeIs('programs.derivados.*')">
        {{ __('Programas Derivados') }}
    </x-nav-link>

    <x-nav-link href="{{ route('cascade.alineacion.index') }}" :active="request()->routeIs('cascade.alineacion.*')">
        {{ __('Matriz de Alineación') }}
    </x-nav-link>
@endcan
```

---

### 6. Actualizar Tests

Editar `tests/Feature/Cascade/MatrizAlineacionTest.php`:

```php
<?php

namespace Tests\Feature\Cascade;

use App\Models\OdsMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use App\Models\User;
use Database\Seeders\{OdsSeeder, PedSeeder, PndSeeder, ProgramasDerivadosSeeder, RolesAndPermissionsSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MatrizAlineacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed([OdsSeeder::class, PndSeeder::class, PedSeeder::class, ProgramasDerivadosSeeder::class]);
    }

    // ============================================
    // Tests de Autorización
    // ============================================

    public function test_usuario_sin_permiso_no_puede_acceder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cascade.alineacion.index'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('cascade.alineacion.index'));

        $response->assertOk();
        $response->assertSee('Matriz de Alineación');
    }

    // ============================================
    // Tests de Alineación PED ↔ PND
    // ============================================

    public function test_crear_alineacion_ped_pnd(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        Livewire::actingAs($user)
            ->test('cascade.alineacion-ped-pnd')
            ->call('toggleForm')
            ->set('searchPed', substr($pedObj->descripcion, 0, 10))
            ->call('selectPed', $pedObj->id)
            ->set('searchPnd', $pndObj->clave)
            ->call('selectPnd', $pndObj->id)
            ->call('crearAlineacion');

        $this->assertDatabaseHas('alineacion_ped_pnd', [
            'ped_objetivo_estrategico_id' => $pedObj->id,
            'pnd_objetivo_id' => $pndObj->id,
        ]);
    }

    public function test_eliminar_alineacion_ped_pnd(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();

        $pedObj->pndObjetivos()->attach($pndObj->id);

        Livewire::actingAs($user)
            ->test('cascade.alineacion-ped-pnd')
            ->call('eliminarAlineacion', $pedObj->id, $pndObj->id);

        $this->assertDatabaseMissing('alineacion_ped_pnd', [
            'ped_objetivo_estrategico_id' => $pedObj->id,
            'pnd_objetivo_id' => $pndObj->id,
        ]);
    }

    // ============================================
    // Tests de Alineación PND ↔ ODS
    // ============================================

    public function test_crear_alineacion_pnd_ods(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::first();

        Livewire::actingAs($user)
            ->test('cascade.alineacion-pnd-ods')
            ->call('toggleForm')
            ->set('searchPnd', $pndObj->clave)
            ->call('selectPnd', $pndObj->id)
            ->set('searchOds', $odsMeta->clave)
            ->call('selectOds', $odsMeta->id)
            ->call('crearAlineacion');

        $this->assertDatabaseHas('alineacion_pnd_ods', [
            'pnd_objetivo_id' => $pndObj->id,
            'ods_meta_id' => $odsMeta->id,
        ]);
    }

    // ============================================
    // Tests de Alineación Línea ↔ Programa
    // ============================================

    public function test_crear_alineacion_linea_programa(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $linea = PedLineaAccion::first();
        $progObj = ProgramaDerivadoObjetivo::first();

        Livewire::actingAs($user)
            ->test('cascade.alineacion-linea-programa')
            ->call('toggleForm')
            ->set('searchLinea', substr($linea->descripcion, 0, 10))
            ->call('selectLinea', $linea->id)
            ->set('searchPrograma', substr($progObj->descripcion, 0, 10))
            ->call('selectPrograma', $progObj->id)
            ->call('crearAlineacion');

        $this->assertDatabaseHas('alineacion_linea_programa', [
            'ped_linea_accion_id' => $linea->id,
            'programa_derivado_objetivo_id' => $progObj->id,
        ]);
    }

    // ============================================
    // Tests de Cadena Completa
    // ============================================

    public function test_herencia_de_cadena_completa(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        // Crear alineaciones
        $pedObj = PedObjetivoEstrategico::first();
        $pndObj = PndObjetivo::first();
        $odsMeta = OdsMeta::where('clave', '1.1')->first();

        $pedObj->pndObjetivos()->attach($pndObj->id);
        $pndObj->odsMetas()->attach($odsMeta->id);

        // Obtener línea de acción del objetivo
        $linea = PedLineaAccion::whereHas('estrategia.objetivoEstrategico', fn($q) => $q->where('id', $pedObj->id))->first();

        if (!$linea) {
            // Crear estructura si no existe
            $estrategia = $pedObj->estrategias()->create(['clave' => '1', 'descripcion' => 'Test']);
            $linea = $estrategia->lineasAccion()->create(['clave' => '1', 'descripcion' => 'Test']);
        }

        // Verificar cadena
        $lineaConCadena = PedLineaAccion::with([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas'
        ])->find($linea->id);

        $this->assertTrue($lineaConCadena->estrategia->objetivoEstrategico->pndObjetivos->contains($pndObj));
        $this->assertTrue(
            $lineaConCadena->estrategia->objetivoEstrategico->pndObjetivos->first()->odsMetas->contains($odsMeta)
        );
    }

    public function test_ver_cadena_completa_modal(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $linea = PedLineaAccion::first();

        Livewire::actingAs($user)
            ->test('cascade.cadena-alineacion')
            ->call('loadCadena', $linea->id)
            ->assertSet('showModal', true)
            ->assertSet('lineaAccionId', $linea->id);
    }

    // ============================================
    // Tests de Eager Loading (N+1)
    // ============================================

    public function test_eager_loading_evita_n1(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        // Crear múltiples alineaciones
        $pedObjetivos = PedObjetivoEstrategico::take(3)->get();
        $pndObjetivos = PndObjetivo::take(3)->get();

        foreach ($pedObjetivos as $i => $ped) {
            $ped->pndObjetivos()->attach($pndObjetivos[$i]->id);
        }

        // Contar queries
        $queries = [];
        \DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        Livewire::actingAs($user)
            ->test('cascade.alineacion-ped-pnd');

        // Debe ser menor a 10 queries (con eager loading)
        $this->assertLessThan(10, count($queries), 'Se detectaron demasiadas queries. Posible problema N+1.');
    }
}
```

---

### 7. Ejecutar y Verificar

```bash
# Compilar assets
sail npm run build

# Ejecutar tests
sail artisan test --filter MatrizAlineacionTest

# Acceder a la aplicación
# http://localhost/alineacion
```

---

## Criterios de Aceptación

- [ ] Ruta `/alineacion` protegida con middleware `permission:gestionar_catalogos`
- [ ] Vista usa `<x-app-layout>` de Jetstream
- [ ] Vista usa `<x-page.container>` con breadcrumbs
- [ ] Vista usa `<x-page.header>` para título y acciones
- [ ] Vista organizada en `resources/views/cascade/alineacion/`
- [ ] Ruta organizada en `routes/web/cascade.php`
- [ ] Selectores dinámicos con búsqueda funcional
- [ ] Modal de cadena completa con `<x-dialog-modal>`
- [ ] Botones usan `<x-primary-button>`, `<x-secondary-button>`
- [ ] Tests pasan (9 assertions)
- [ ] Sin problemas N+1 (verificado con test)
- [ ] Responsive en pantallas >= 768px

---

## Resumen de Correcciones Aplicadas