# Plan: S2-T9 — Importador de PED desde Markdown

**Ticket:** S2-T9
**Tipo:** feat
**Rama:** `feat/S2-T9-importador-markdown-ped`
**Sprint:** 2 — Cascada de Planes
**Depende de:** S2-T3 (Modelos PED), S1-T3 (Permisos)

---

## Contexto

El PED estatal se publica típicamente como documento de texto estructurado. Este importador permite al planeador subir un archivo Markdown con la jerarquía completa y convertirla en registros de base de datos, evitando la captura manual de cientos de nodos.

**Mapeo de Headings a Niveles PED:**

| Nivel Markdown | Nivel PED            | Prefijo en texto          |
| -------------- | -------------------- | ------------------------- |
| `#` (H1)       | Plan                 | Plan Estatal...           |
| `##` (H2)      | Eje                  | Eje N: Nombre             |
| `###` (H3)     | Tema                 | Tema N.N: Nombre          |
| `####` (H4)    | Objetivo Estratégico | Objetivo N.N.N            |
| `#####` (H5)   | Estrategia           | Estrategia N.N.N.N        |
| `-` (Lista)    | Línea de Acción      | Línea de Acción N.N.N.N.N |

**Esta versión implementa:**
- Uso de `<x-app-layout>` de Jetstream (no se reemplaza)
- Componentes `<x-page.container>`, `<x-page.header>`
- Vistas organizadas por dominio: `resources/views/cascade/ped/`
- Rutas organizadas en `routes/web/cascade.php`
- Componentes Livewire en `App\Livewire\Cascade\`
- Sintaxis moderna de componentes Jetstream (sin prefijo `x-jet-`)

---

## Pre-requisitos

- S2-T3: Modelos `PedPlan`, `PedEje`, `PedTema`, `PedObjetivoEstrategico`, `PedEstrategia`, `PedLineaAccion`
- S1-T3: Permiso `gestionar_catalogos`
- Componentes base de página creados (`x-page.container`, `x-page.header`)
- Extensión `fileinfo` habilitada para validación MIME

---

## Pasos

### 1. Crear Servicio Parser (Sin cambios)

```bash
mkdir -p app/Services
```

Crear `app/Services/PedMarkdownParser.php`:

```php
<?php

namespace App\Services;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PedMarkdownParser
{
    protected array $errors = [];
    protected array $tree = [];

    /**
     * Parsea un archivo Markdown y retorna la estructura en árbol.
     */
    public function parse(string $content): array
    {
        $this->errors = [];
        $this->tree = [];

        $lines = explode("\n", $content);
        $lineNumber = 0;

        // Estado actual del parser
        $currentPlan = null;
        $currentEje = null;
        $currentTema = null;
        $currentObjetivo = null;
        $currentEstrategia = null;

        foreach ($lines as $line) {
            $lineNumber++;
            $line = trim($line);

            // Ignorar líneas vacías
            if (empty($line)) {
                continue;
            }

            // Detectar nivel de heading
            $level = $this->detectHeadingLevel($line);

            if ($level === null) {
                // No es heading, podría ser línea de acción (lista)
                if (Str::startsWith($line, '-')) {
                    if ($currentEstrategia === null) {
                        $this->addError($lineNumber, "Línea de acción sin estrategia padre");
                        continue;
                    }

                    $lineaAccion = $this->parseLineaAccion($line);
                    if ($lineaAccion) {
                        $currentEstrategia['lineas'][] = $lineaAccion;
                    }
                }
                continue;
            }

            // Validar jerarquía correcta
            if (!$this->validateHierarchy($level, $lineNumber)) {
                continue;
            }

            // Parsear según el nivel
            switch ($level) {
                case 1: // Plan
                    $currentPlan = $this->parsePlan($line);
                    $this->tree = $currentPlan;
                    $currentEje = null;
                    $currentTema = null;
                    $currentObjetivo = null;
                    $currentEstrategia = null;
                    break;

                case 2: // Eje
                    if ($currentPlan === null) {
                        $this->addError($lineNumber, "Eje sin plan padre");
                        continue 2;
                    }
                    $currentEje = $this->parseEje($line);
                    $currentPlan['ejes'][] = $currentEje;
                    $currentTema = null;
                    $currentObjetivo = null;
                    $currentEstrategia = null;
                    break;

                case 3: // Tema
                    if ($currentEje === null) {
                        $this->addError($lineNumber, "Tema sin eje padre");
                        continue 2;
                    }
                    $currentTema = $this->parseTema($line);
                    // Actualizar referencia en el árbol
                    $lastEjeIndex = count($this->tree['ejes']) - 1;
                    $this->tree['ejes'][$lastEjeIndex]['temas'][] = $currentTema;
                    $currentObjetivo = null;
                    $currentEstrategia = null;
                    break;

                case 4: // Objetivo Estratégico
                    if ($currentTema === null) {
                        $this->addError($lineNumber, "Objetivo estratégico sin tema padre");
                        continue 2;
                    }
                    $currentObjetivo = $this->parseObjetivo($line);
                    $lastEjeIndex = count($this->tree['ejes']) - 1;
                    $lastTemaIndex = count($this->tree['ejes'][$lastEjeIndex]['temas']) - 1;
                    $this->tree['ejes'][$lastEjeIndex]['temas'][$lastTemaIndex]['objetivos'][] = $currentObjetivo;
                    $currentEstrategia = null;
                    break;

                case 5: // Estrategia
                    if ($currentObjetivo === null) {
                        $this->addError($lineNumber, "Estrategia sin objetivo padre");
                        continue 2;
                    }
                    $currentEstrategia = $this->parseEstrategia($line);
                    $lastEjeIndex = count($this->tree['ejes']) - 1;
                    $lastTemaIndex = count($this->tree['ejes'][$lastEjeIndex]['temas']) - 1;
                    $lastObjIndex = count($this->tree['ejes'][$lastEjeIndex]['temas'][$lastTemaIndex]['objetivos']) - 1;
                    $this->tree['ejes'][$lastEjeIndex]['temas'][$lastTemaIndex]['objetivos'][$lastObjIndex]['estrategias'][] = $currentEstrategia;
                    break;
            }
        }

        // Validar que se encontró al menos un plan
        if (empty($this->tree)) {
            $this->addError(0, "No se encontró ningún plan en el archivo");
        }

        return [
            'tree' => $this->tree,
            'errors' => $this->errors,
            'valid' => empty($this->errors),
        ];
    }

    /**
     * Detecta el nivel de heading (1-5).
     */
    protected function detectHeadingLevel(string $line): ?int
    {
        if (Str::startsWith($line, '#####')) return 5;
        if (Str::startsWith($line, '####')) return 4;
        if (Str::startsWith($line, '###')) return 3;
        if (Str::startsWith($line, '##')) return 2;
        if (Str::startsWith($line, '#')) return 1;
        
        return null;
    }

    /**
     * Valida que la jerarquía sea correcta.
     */
    protected function validateHierarchy(int $level, int $lineNumber): bool
    {
        // El primer elemento debe ser un plan (nivel 1)
        if (empty($this->tree) && $level !== 1) {
            $this->addError($lineNumber, "El archivo debe comenzar con un Plan (heading nivel 1)");
            return false;
        }

        return true;
    }

    /**
     * Parsea un Plan (H1).
     */
    protected function parsePlan(string $line): array
    {
        $text = ltrim($line, '# ');
        
        // Intentar extraer periodo
        $periodoMatch = preg_match('/(\d{4})\s*[-–]\s*(\d{4})/', $text, $matches);
        
        return [
            'tipo' => 'plan',
            'nombre' => $text,
            'periodo_inicio' => $periodoMatch ? (int) $matches[1] : null,
            'periodo_fin' => $periodoMatch ? (int) $matches[2] : null,
            'ejes' => [],
        ];
    }

    /**
     * Parsea un Eje (H2).
     */
    protected function parseEje(string $line): array
    {
        $text = ltrim($line, '# ');
        
        // Formato esperado: "Eje 1: Nombre" o "Eje 1. Nombre"
        if (preg_match('/^Eje\s+(\d+)\s*[:.-]\s*(.+)$/i', $text, $matches)) {
            return [
                'tipo' => 'eje',
                'numero' => $matches[1],
                'nombre' => trim($matches[2]),
                'temas' => [],
            ];
        }

        // Si no coincide el formato, usar el texto completo
        return [
            'tipo' => 'eje',
            'numero' => '1',
            'nombre' => $text,
            'temas' => [],
        ];
    }

    /**
     * Parsea un Tema (H3).
     */
    protected function parseTema(string $line): array
    {
        $text = ltrim($line, '# ');
        
        // Formato esperado: "Tema 1.1: Nombre" o "Tema 1.1 Nombre"
        if (preg_match('/^Tema\s+([\d.]+)\s*[:.-]?\s*(.*)$/i', $text, $matches)) {
            return [
                'tipo' => 'tema',
                'numero' => $matches[1],
                'nombre' => trim($matches[2]) ?: $text,
            ];
        }

        return [
            'tipo' => 'tema',
            'numero' => '1',
            'nombre' => $text,
        ];
    }

    /**
     * Parsea un Objetivo Estratégico (H4).
     */
    protected function parseObjetivo(string $line): array
    {
        $text = ltrim($line, '# ');
        
        // Formato esperado: "Objetivo 1.1.1: Descripción" o solo la descripción
        if (preg_match('/^Objetivo\s+([\d.]+)\s*[:.-]?\s*(.*)$/i', $text, $matches)) {
            return [
                'tipo' => 'objetivo',
                'clave' => $matches[1],
                'descripcion' => trim($matches[2]) ?: $text,
                'estrategias' => [],
            ];
        }

        return [
            'tipo' => 'objetivo',
            'clave' => '1',
            'descripcion' => $text,
            'estrategias' => [],
        ];
    }

    /**
     * Parsea una Estrategia (H5).
     */
    protected function parseEstrategia(string $line): array
    {
        $text = ltrim($line, '# ');
        
        // Formato esperado: "Estrategia 1.1.1.1: Descripción"
        if (preg_match('/^Estrategia\s+([\d.]+)\s*[:.-]?\s*(.*)$/i', $text, $matches)) {
            return [
                'tipo' => 'estrategia',
                'clave' => $matches[1],
                'descripcion' => trim($matches[2]) ?: $text,
                'lineas' => [],
            ];
        }

        return [
            'tipo' => 'estrategia',
            'clave' => '1',
            'descripcion' => $text,
            'lineas' => [],
        ];
    }

    /**
     * Parsea una Línea de Acción (lista).
     */
    protected function parseLineaAccion(string $line): ?array
    {
        $text = ltrim($line, '- ');
        
        // Formato esperado: "Línea de Acción 1.1.1.1.1: Descripción"
        if (preg_match('/^L[ií]nea\s+de\s+Acci[oó]n\s+([\d.]+)\s*[:.-]?\s*(.*)$/i', $text, $matches)) {
            return [
                'tipo' => 'linea',
                'clave' => $matches[1],
                'descripcion' => trim($matches[2]) ?: $text,
            ];
        }

        // Si no tiene formato específico, asumir que es solo descripción
        return [
            'tipo' => 'linea',
            'clave' => '1',
            'descripcion' => $text,
        ];
    }

    /**
     * Agrega un error a la lista.
     */
    protected function addError(int $line, string $message): void
    {
        $this->errors[] = [
            'line' => $line,
            'message' => $message,
        ];
    }

    /**
     * Importa el árbol parseado a la base de datos.
     */
    public function import(array $tree, bool $activatePlan = true): PedPlan
    {
        return DB::transaction(function () use ($tree, $activatePlan) {
            // Desactivar otros planes si este será activo
            if ($activatePlan) {
                PedPlan::query()->update(['activo' => false]);
            }

            // Crear Plan
            $plan = PedPlan::create([
                'nombre' => $tree['nombre'],
                'nivel_gobierno' => 'estatal',
                'periodo_inicio' => $tree['periodo_inicio'] ?? now()->year,
                'periodo_fin' => $tree['periodo_fin'] ?? now()->year + 5,
                'activo' => $activatePlan,
            ]);

            // Crear Ejes
            foreach ($tree['ejes'] ?? [] as $ejeData) {
                $eje = $plan->ejes()->create([
                    'numero' => $ejeData['numero'],
                    'nombre' => $ejeData['nombre'],
                ]);

                // Crear Temas
                foreach ($ejeData['temas'] ?? [] as $temaData) {
                    $tema = $eje->temas()->create([
                        'numero' => $temaData['numero'],
                        'nombre' => $temaData['nombre'],
                    ]);

                    // Crear Objetivos
                    foreach ($temaData['objetivos'] ?? [] as $objData) {
                        $objetivo = $tema->objetivosEstrategicos()->create([
                            'clave' => $objData['clave'],
                            'descripcion' => $objData['descripcion'],
                        ]);

                        // Crear Estrategias
                        foreach ($objData['estrategias'] ?? [] as $estData) {
                            $estrategia = $objetivo->estrategias()->create([
                                'clave' => $estData['clave'],
                                'descripcion' => $estData['descripcion'],
                            ]);

                            // Crear Líneas de Acción
                            foreach ($estData['lineas'] ?? [] as $lineaData) {
                                $estrategia->lineasAccion()->create([
                                    'clave' => $lineaData['clave'],
                                    'descripcion' => $lineaData['descripcion'],
                                ]);
                            }
                        }
                    }
                }
            }

            return $plan;
        });
    }

    /**
     * Parsea archivo y retorna estadísticas.
     */
    public function getStats(array $parsed): array
    {
        $tree = $parsed['tree'];

        return [
            'plan' => !empty($tree) ? 1 : 0,
            'ejes' => count($tree['ejes'] ?? []),
            'temas' => $this->countRecursive($tree['ejes'] ?? [], 'temas'),
            'objetivos' => $this->countRecursive($tree['ejes'] ?? [], 'temas', 'objetivos'),
            'estrategias' => $this->countRecursive($tree['ejes'] ?? [], 'temas', 'objetivos', 'estrategias'),
            'lineas' => $this->countRecursive($tree['ejes'] ?? [], 'temas', 'objetivos', 'estrategias', 'lineas'),
        ];
    }

    /**
     * Cuenta elementos recursivamente en el árbol.
     */
    protected function countRecursive(array $data, string ...$keys): int
    {
        if (empty($keys)) {
            return count($data);
        }

        $key = array_shift($keys);
        $total = 0;

        foreach ($data as $item) {
            if (isset($item[$key])) {
                if (empty($keys)) {
                    $total += count($item[$key]);
                } else {
                    $total += $this->countRecursive($item[$key], ...$keys);
                }
            }
        }

        return $total;
    }
}
```

---

### 2. Agregar Rutas al Archivo de Dominio

Agregar a `routes/web/cascade.php`:

```php
// Dentro del grupo Route::prefix('ped')->name('ped.')->group(function () {

    // ... rutas existentes ...

    // Importador
    Route::get('/import', [PedController::class, 'importForm'])->name('import');
    Route::post('/import', [PedController::class, 'import'])->name('import.store');
// });
```

---

### 3. Actualizar Controlador

Agregar métodos al controlador `app/Http/Controllers/Cascade/PedController.php`:

```php
/**
 * Formulario de importación de PED.
 */
public function importForm()
{
    return view('cascade.ped.import');
}

/**
 * Procesa la importación (delegado al componente Livewire).
 */
public function import()
{
    return redirect()->route('cascade.ped.import');
}
```

---

### 4. Crear Componente Livewire (Organizado por Dominio)

```bash
sail artisan make:livewire Cascade/PedImporter
```

Editar `app/Livewire/Cascade/PedImporter.php`:

```php
<?php

namespace App\Livewire\Cascade;

use App\Services\PedMarkdownParser;
use App\Models\PedPlan;
use Livewire\Component;
use Livewire\WithFileUploads;

class PedImporter extends Component
{
    use WithFileUploads;

    public $archivo;
    public bool $showPreview = false;
    public bool $showSuccess = false;
    public array $parsedData = [];
    public array $stats = [];
    public array $errors = [];
    
    // Datos del plan (editables)
    public string $planNombre = '';
    public int $planPeriodoInicio = 2025;
    public int $planPeriodoFin = 2030;
    public bool $planActivo = true;

    protected $listeners = ['refresh' => '$refresh'];

    protected function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:md,txt,markdown', 'max:10240'], // Max 10MB
            'planNombre' => ['required', 'string', 'max:255'],
            'planPeriodoInicio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'planPeriodoFin' => ['required', 'integer', 'min:2000', 'max:2100', 'gt:planPeriodoInicio'],
        ];
    }

    protected $validationAttributes = [
        'archivo' => 'archivo Markdown',
        'planNombre' => 'nombre del plan',
        'planPeriodoInicio' => 'año de inicio',
        'planPeriodoFin' => 'año de fin',
    ];

    /**
     * Procesa el archivo subido.
     */
    public function updatedArchivo(): void
    {
        $this->validateOnly('archivo');

        try {
            $content = $this->archivo->get();
            
            $parser = new PedMarkdownParser();
            $result = $parser->parse($content);

            $this->errors = $result['errors'];
            $this->parsedData = $result['tree'];
            $this->stats = $parser->getStats($result);

            if ($result['valid']) {
                // Pre-llenar datos editables
                $this->planNombre = $this->parsedData['nombre'] ?? '';
                $this->planPeriodoInicio = $this->parsedData['periodo_inicio'] ?? now()->year;
                $this->planPeriodoFin = $this->parsedData['periodo_fin'] ?? now()->year + 5;
                $this->showPreview = true;
            }

        } catch (\Exception $e) {
            $this->errors = [[
                'line' => 0,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ]];
            $this->showPreview = false;
        }
    }

    /**
     * Confirma la importación.
     */
    public function confirmarImportacion(): void
    {
        $this->validate([
            'planNombre' => ['required', 'string', 'max:255'],
            'planPeriodoInicio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'planPeriodoFin' => ['required', 'integer', 'min:2000', 'max:2100', 'gt:planPeriodoInicio'],
        ]);

        try {
            // Actualizar datos del plan
            $this->parsedData['nombre'] = $this->planNombre;
            $this->parsedData['periodo_inicio'] = $this->planPeriodoInicio;
            $this->parsedData['periodo_fin'] = $this->planPeriodoFin;

            $parser = new PedMarkdownParser();
            $plan = $parser->import($this->parsedData, $this->planActivo);

            $this->showPreview = false;
            $this->showSuccess = true;

            session()->flash('message', "Plan '{$plan->nombre}' importado exitosamente con {$this->stats['ejes']} ejes y {$this->stats['lineas']} líneas de acción.");

        } catch (\Exception $e) {
            $this->errors = [[
                'line' => 0,
                'message' => 'Error al importar: ' . $e->getMessage(),
            ]];
        }
    }

    /**
     * Cancela la importación y reinicia el formulario.
     */
    public function cancelar(): void
    {
        $this->reset([
            'archivo',
            'showPreview',
            'showSuccess',
            'parsedData',
            'stats',
            'errors',
            'planNombre',
            'planPeriodoInicio',
            'planPeriodoFin',
        ]);
    }

    /**
     * Reinicia para una nueva importación.
     */
    public function nuevaImportacion(): void
    {
        $this->cancelar();
    }

    /**
     * Descarga un archivo de ejemplo.
     */
    public function descargarEjemplo()
    {
        $contenido = <<<'MD'
# Plan Estatal de Desarrollo 2025-2030

## Eje 1: Bienestar Social
### Tema 1.1: Educación de Calidad
#### Objetivo 1.1.1: Garantizar el acceso a la educación
##### Estrategia 1.1.1.1: Ampliar la cobertura educativa
- Línea de Acción 1.1.1.1.1: Construir nuevas escuelas en zonas marginadas
- Línea de Acción 1.1.1.1.2: Ampliar los programas de becas educativas

##### Estrategia 1.1.1.2: Fortalecer la calidad educativa
- Línea de Acción 1.1.1.2.1: Implementar programas de capacitación docente

### Tema 1.2: Salud Integral
#### Objetivo 1.2.1: Garantizar el acceso a servicios de salud
##### Estrategia 1.2.1.1: Fortalecer la infraestructura de salud
- Línea de Acción 1.2.1.1.1: Construir nuevos centros de salud
- Línea de Acción 1.2.1.1.2: Equipar hospitales regionales

## Eje 2: Economía Próspera
### Tema 2.1: Empleo y Emprendimiento
#### Objetivo 2.1.1: Fomentar la creación de empleos
##### Estrategia 2.1.1.1: Apoyar a pequeños empresarios
- Línea de Acción 2.1.1.1.1: Crear programa de financiamiento para PyMEs
- Línea de Acción 2.1.1.1.2: Simplificar trámites para nuevos negocios
MD;

        return response()->streamDownload(
            fn() => print($contenido),
            'ejemplo-ped.md',
            ['Content-Type' => 'text/markdown']
        );
    }

    public function render()
    {
        return view('livewire.cascade.ped-importer');
    }
}
```

---

### 5. Crear Vistas con Nueva Arquitectura

Crear `resources/views/cascade/ped/import.blade.php`:

```blade
<x-app-layout>
    
    {{-- Slot Header de Jetstream --}}
    <x-slot name="header">
        <x-page.header 
            title="Importar Plan Estatal de Desarrollo" 
            subtitle="Cargue un archivo Markdown con la estructura completa del PED"
        >
            <x-secondary-button href="{{ route('cascade.ped.index') }}">
                Volver
            </x-secondary-button>
        </x-page.header>
    </x-slot>

    {{-- Contenedor de Página --}}
    <x-page.container 
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Cascada de Planes', 'url' => route('cascade.ped.index')],
            ['label' => 'Importar PED']
        ]"
    >
        
        <livewire:cascade.ped-importer />

    </x-page.container>

</x-app-layout>
```

---

Crear `resources/views/livewire/cascade/ped-importer.blade.php`:

```blade
<div class="space-y-6">

    {{-- Mensaje de éxito --}}
    @if($showSuccess)
        <div class="bg-white shadow sm:rounded-lg p-8 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">¡Importación Exitosa!</h3>
            <p class="text-gray-600 mb-6">{{ session('message') }}</p>
            <div class="flex justify-center space-x-4">
                <a href="{{ route('cascade.ped.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    Ver Plan Importado
                </a>
                <button wire:click="nuevaImportacion"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                    Nueva Importación
                </button>
            </div>
        </div>
    @else

    {{-- Información inicial --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-900">Formato del Archivo</h3>
                <div class="mt-2 text-sm text-gray-600">
                    <p>El archivo Markdown debe seguir la siguiente estructura de headings:</p>
                    <ul class="mt-2 space-y-1 list-disc list-inside">
                        <li><code class="bg-gray-100 px-1 rounded">#</code> Plan (H1)</li>
                        <li><code class="bg-gray-100 px-1 rounded">##</code> Eje (H2)</li>
                        <li><code class="bg-gray-100 px-1 rounded">###</code> Tema (H3)</li>
                        <li><code class="bg-gray-100 px-1 rounded">####</code> Objetivo Estratégico (H4)</li>
                        <li><code class="bg-gray-100 px-1 rounded">#####</code> Estrategia (H5)</li>
                        <li><code class="bg-gray-100 px-1 rounded">-</code> Línea de Acción (lista)</li>
                    </ul>
                </div>
                <button wire:click="descargarEjemplo"
                        class="mt-3 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Descargar archivo de ejemplo
                </button>
            </div>
        </div>
    </div>

    {{-- Formulario de carga --}}
    @if(!$showPreview)
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex items-center justify-center w-full">
                <label for="dropzone-file" 
                       class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 @error('archivo') border-red-300 @enderror">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        @if($archivo)
                            <svg class="w-10 h-10 mb-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mb-2 text-sm text-gray-700 font-medium">{{ $archivo->getClientOriginalName() }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($archivo->getSize() / 1024, 1) }} KB</p>
                        @else
                            <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="mb-2 text-sm text-gray-500">
                                <span class="font-semibold">Click para seleccionar</span> o arrastra y suelta
                            </p>
                            <p class="text-xs text-gray-500">Archivos Markdown (.md, .markdown, .txt) - Máx. 10MB</p>
                        @endif
                    </div>
                    <input id="dropzone-file" 
                           type="file" 
                           class="hidden" 
                           wire:model="archivo"
                           accept=".md,.markdown,.txt" />
                </label>
            </div>
            @error('archivo')
                <p class="mt-2 text-sm text-red-600 text-center">{{ $message }}</p>
            @enderror
        </div>
    @endif

    {{-- Errores de parsing --}}
    @if(!empty($errors))
        <div class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Se encontraron errores en el archivo</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors as $error)
                                <li>
                                    @if($error['line'] > 0)
                                        <span class="font-medium">Línea {{ $error['line'] }}:</span>
                                    @endif
                                    {{ $error['message'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <button wire:click="cancelar"
                            class="mt-3 text-sm font-medium text-red-600 hover:text-red-800">
                        Cargar otro archivo
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Previsualización --}}
    @if($showPreview && empty($errors))
        
        {{-- Estadísticas --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Resumen de Importación</h3>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['plan'] }}</div>
                    <div class="text-xs text-gray-500">Plan</div>
                </div>
                <div class="bg-amber-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-amber-600">{{ $stats['ejes'] }}</div>
                    <div class="text-xs text-gray-500">Ejes</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['temas'] }}</div>
                    <div class="text-xs text-gray-500">Temas</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-emerald-600">{{ $stats['objetivos'] }}</div>
                    <div class="text-xs text-gray-500">Objetivos</div>
                </div>
                <div class="bg-cyan-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-cyan-600">{{ $stats['estrategias'] }}</div>
                    <div class="text-xs text-gray-500">Estrategias</div>
                </div>
                <div class="bg-rose-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-rose-600">{{ $stats['lineas'] }}</div>
                    <div class="text-xs text-gray-500">Líneas</div>
                </div>
            </div>
        </div>

        {{-- Formulario de edición --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Datos del Plan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <x-label for="planNombre" value="Nombre del Plan" />
                    <x-input id="planNombre"
                             type="text"
                             class="mt-1 block w-full"
                             wire:model="planNombre" />
                    @error('planNombre')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="planPeriodoInicio" value="Año de Inicio" />
                    <x-input id="planPeriodoInicio"
                             type="number"
                             class="mt-1 block w-full"
                             wire:model="planPeriodoInicio" />
                    @error('planPeriodoInicio')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="planPeriodoFin" value="Año de Fin" />
                    <x-input id="planPeriodoFin"
                             type="number"
                             class="mt-1 block w-full"
                             wire:model="planPeriodoFin" />
                    @error('planPeriodoFin')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <x-checkbox wire:model="planActivo" />
                        <span class="ml-2 text-sm text-gray-700">
                            Establecer como plan activo (desactivará otros planes)
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Previsualización del árbol --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="p-4 border-b">
                <h3 class="text-lg font-medium text-gray-900">Estructura del Plan</h3>
                <p class="text-sm text-gray-500">Vista previa de la jerarquía detectada</p>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto">
                @if(!empty($parsedData))
                    <div class="space-y-2">
                        @foreach($parsedData['ejes'] ?? [] as $eje)
                            @include('livewire.cascade.partials.import-eje-preview', ['eje' => $eje])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-end space-x-4">
            <x-secondary-button wire:click="cancelar">
                Cancelar
            </x-secondary-button>
            <x-primary-button wire:click="confirmarImportacion">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Confirmar Importación
            </x-primary-button>
        </div>
    @endif

    @endif {{-- fin del else de showSuccess --}}
</div>
```

---

### 6. Crear Partials para Previsualización

Crear `resources/views/livewire/cascade/partials/import-eje-preview.blade.php`:

```blade
<div class="border-l-4 border-amber-400 pl-3 py-1" x-data="{ expanded: true }">
    <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200" :class="expanded ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                {{ $eje['numero'] }}
            </span>
            <span class="text-sm font-medium text-gray-800">{{ $eje['nombre'] }}</span>
        </div>
        <span class="text-xs text-gray-400">{{ count($eje['temas'] ?? []) }} temas</span>
    </div>
    
    <div x-show="expanded" class="ml-4 mt-1 space-y-1">
        @foreach($eje['temas'] ?? [] as $tema)
            @include('livewire.cascade.partials.import-tema-preview', ['tema' => $tema])
        @endforeach
    </div>
</div>
```

Crear `resources/views/livewire/cascade/partials/import-tema-preview.blade.php`:

```blade
<div class="border-l-4 border-purple-400 pl-3 py-1" x-data="{ expanded: false }">
    <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200" :class="expanded ? 'rotate-90' : ''">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                {{ $tema['numero'] }}
            </span>
            <span class="text-sm text-gray-700">{{ $tema['nombre'] }}</span>
        </div>
        <span class="text-xs text-gray-400">{{ count($tema['objetivos'] ?? []) }} obj.</span>
    </div>
    
    <div x-show="expanded" class="ml-4 mt-1 space-y-1">
        @foreach($tema['objetivos'] ?? [] as $objetivo)
            <div class="border-l-4 border-emerald-400 pl-3 py-1">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                        {{ $objetivo['clave'] }}
                    </span>
                    <span class="text-sm text-gray-700">{{ Str::limit($objetivo['descripcion'], 40) }}</span>
                </div>
                @if(!empty($objetivo['estrategias']))
                    <div class="ml-4 mt-1 space-y-1">
                        @foreach($objetivo['estrategias'] as $estrategia)
                            <div class="border-l-4 border-cyan-400 pl-3 py-1">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800">
                                        {{ $estrategia['clave'] }}
                                    </span>
                                    <span class="text-sm text-gray-700">{{ Str::limit($estrategia['descripcion'], 40) }}</span>
                                </div>
                                @if(!empty($estrategia['lineas']))
                                    <div class="ml-4 mt-1 space-y-1">
                                        @foreach($estrategia['lineas'] as $linea)
                                            <div class="flex items-center space-x-2 text-xs text-gray-600">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-rose-100 text-rose-800">
                                                    {{ $linea['clave'] }}
                                                </span>
                                                {{ Str::limit($linea['descripcion'], 35) }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
```

---

### 7. Actualizar Tests

Actualizar `tests/Unit/PedMarkdownParserTest.php` (sin cambios en el contenido, solo verificar namespace correcto).

Actualizar `tests/Feature/Cascade/PedImporterTest.php`:

```php
<?php

namespace Tests\Feature\Cascade;

use App\Models\PedPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class PedImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_usuario_sin_permiso_no_puede_acceder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cascade.ped.import'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('cascade.ped.import'));

        $response->assertOk();
        $response->assertSee('Importar Plan Estatal de Desarrollo');
    }

    public function test_importa_archivo_valido(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $contenido = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Bienestar Social
### Tema 1.1: Educación
#### Objetivo 1.1.1: Garantizar acceso
##### Estrategia 1.1.1.1: Ampliar cobertura
- Línea de Acción 1.1.1.1.1: Construir escuelas
MD;

        $archivo = UploadedFile::fake()->createWithContent('plan.md', $contenido);

        Livewire::actingAs($user)
            ->test('cascade.ped-importer')
            ->set('archivo', $archivo)
            ->assertSet('showPreview', true)
            ->set('planNombre', 'Plan Test 2025-2030')
            ->set('planPeriodoInicio', 2025)
            ->set('planPeriodoFin', 2030)
            ->call('confirmarImportacion')
            ->assertSet('showSuccess', true);

        $this->assertDatabaseHas('ped_planes', [
            'nombre' => 'Plan Test 2025-2030',
            'activo' => true,
        ]);

        $plan = PedPlan::where('nombre', 'Plan Test 2025-2030')->first();
        $this->assertEquals(1, $plan->ejes()->count());
    }

    public function test_desactiva_plan_anterior_al_importar_nuevo_activo(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $planAnterior = PedPlan::create([
            'nombre' => 'Plan Anterior',
            'periodo_inicio' => 2020,
            'periodo_fin' => 2025,
            'activo' => true,
        ]);

        $contenido = <<<'MD'
# Plan Nuevo 2025-2030

## Eje 1: Test
MD;

        $archivo = UploadedFile::fake()->createWithContent('plan.md', $contenido);

        Livewire::actingAs($user)
            ->test('cascade.ped-importer')
            ->set('archivo', $archivo)
            ->set('planActivo', true)
            ->call('confirmarImportacion');

        $this->assertFalse($planAnterior->fresh()->activo);
        $this->assertTrue(PedPlan::where('nombre', 'Plan Nuevo 2025-2030')->first()->activo);
    }
}
```

---

### 8. Ejecutar y Verificar

```bash
# Ejecutar tests
sail artisan test --filter PedMarkdownParserTest
sail artisan test --filter PedImporterTest

# Compilar assets
sail npm run build
```

---

## Criterios de Aceptación

- [ ] Servicio `App\Services\PedMarkdownParser` parsea Markdown correctamente
- [ ] Vista usa `<x-app-layout>` de Jetstream
- [ ] Vista usa `<x-page.container>` con breadcrumbs
- [ ] Vista usa `<x-page.header>` para título y acciones
- [ ] Vista organizada en `resources/views/cascade/ped/import.blade.php`
- [ ] Componente Livewire en `App\Livewire\Cascade\PedImporter`
- [ ] Ruta en `routes/web/cascade.php`
- [ ] Botones usan `<x-primary-button>`, `<x-secondary-button>`
- [ ] Tests pasan

---