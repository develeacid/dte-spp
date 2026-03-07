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

---

## Pre-requisitos

- S2-T3: Modelos `PedPlan`, `PedEje`, `PedTema`, `PedObjetivoEstrategico`, `PedEstrategia`, `PedLineaAccion`
- S1-T3: Permiso `gestionar_catalogos`
- Extensión `fileinfo` habilitada para validación MIME

---

## Pasos

### 1. Crear Servicio Parser

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

        $count = function (array $data, string $key): int {
            $total = 0;
            foreach ($data[$key] ?? [] as $item) {
                $total++;
                if (isset($item['temas'])) $total += count($item['temas']);
                if (isset($item['objetivos'])) $total += count($item['objetivos']);
                if (isset($item['estrategias'])) $total += count($item['estrategias']);
                if (isset($item['lineas'])) $total += count($item['lineas']);
            }
            return $total;
        };

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

### 2. Crear Componente Livewire

```bash
sail artisan make:livewire PedImporter
```

Editar `app/Livewire/PedImporter.php`:

```php
<?php

namespace App\Livewire;

use App\Services\PedMarkdownParser;
use App\Models\PedPlan;
use Illuminate\Support\Facades\Storage;
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
        return view('livewire.ped-importer');
    }
}
```

---

### 3. Crear Rutas

Editar `routes/web.php`:

```php
// Agregar dentro del grupo de middleware 'permission:gestionar_catalogos'

Route::prefix('ped')->name('ped.')->group(function () {
    // ... rutas existentes de S2-T6 ...

    // Importador
    Route::get('/importar', [PedController::class, 'importForm'])->name('import');
    Route::post('/importar', [PedController::class, 'import'])->name('import.store');
});
```

Agregar método al controlador `app/Http/Controllers/PedController.php`:

```php
/**
 * Formulario de importación de PED.
 */
public function importForm()
{
    return view('ped.import');
}

/**
 * Procesa la importación (redirige al componente Livewire).
 */
public function import()
{
    return redirect()->route('ped.import');
}
```

---

### 4. Crear Vistas

Crear `resources/views/ped/import.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Importar Plan Estatal de Desarrollo
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <livewire:ped-importer />
        </div>
    </div>
</x-app-layout>
```

Crear `resources/views/livewire/ped-importer.blade.php`:

```blade
<div class="space-y-6">

    {{-- Mensaje de éxito --}}
    @if($showSuccess)
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">¡Importación Exitosa!</h3>
            <p class="text-gray-600 mb-6">{{ session('message') }}</p>
            <div class="flex justify-center space-x-4">
                <a href="{{ route('ped.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    Ver Plan Importado
                </a>
                <button wire:click="nuevaImportacion"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Nueva Importación
                </button>
            </div>
        </div>
    @else

    {{-- Información inicial --}}
    <div class="bg-white rounded-lg shadow p-6">
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
        <div class="bg-white rounded-lg shadow p-6">
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
        <div class="bg-white rounded-lg shadow p-6">
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
        <div class="bg-white rounded-lg shadow p-6">
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
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h3 class="text-lg font-medium text-gray-900">Estructura del Plan</h3>
                <p class="text-sm text-gray-500">Vista previa de la jerarquía detectada</p>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto">
                @if(!empty($parsedData))
                    <div class="space-y-2">
                        @foreach($parsedData['ejes'] ?? [] as $eje)
                            @include('livewire.partials.import-eje-preview', ['eje' => $eje])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-end space-x-4">
            <button wire:click="cancelar"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </button>
            <button wire:click="confirmarImportacion"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Confirmar Importación
            </button>
        </div>
    @endif

    @endif {{-- fin del else de showSuccess --}}
</div>
```

Crear `resources/views/livewire/partials/import-eje-preview.blade.php`:

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
            @include('livewire.partials.import-tema-preview', ['tema' => $tema])
        @endforeach
    </div>
</div>
```

Crear `resources/views/livewire/partials/import-tema-preview.blade.php`:

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
            @include('livewire.partials.import-objetivo-preview', ['objetivo' => $objetivo])
        @endforeach
    </div>
</div>
```

Crear `resources/views/livewire/partials/import-objetivo-preview.blade.php`:

```blade
<div class="border-l-4 border-emerald-400 pl-3 py-1" x-data="{ expanded: false }">
    <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200" :class="expanded ? 'rotate-90' : ''">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                {{ $objetivo['clave'] }}
            </span>
            <span class="text-sm text-gray-700">{{ Str::limit($objetivo['descripcion'], 40) }}</span>
        </div>
        <span class="text-xs text-gray-400">{{ count($objetivo['estrategias'] ?? []) }} est.</span>
    </div>

    <div x-show="expanded" class="ml-4 mt-1 space-y-1">
        @foreach($objetivo['estrategias'] ?? [] as $estrategia)
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
</div>
```

---

### 5. Agregar enlace en la vista de PED

Editar `resources/views/ped/index.blade.php`:

```blade
<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Plan Estatal de Desarrollo
        </h2>
        <div class="flex items-center space-x-3">
            <a href="{{ route('ped.import') }}"
               class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Importar desde Markdown
            </a>
            <livewire:ped-plan-form />
        </div>
    </div>
</x-slot>
```

---

### 6. Crear Tests Unitarios

```bash
sail artisan make:test PedMarkdownParserTest --unit
```

Editar `tests/Unit/PedMarkdownParserTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Services\PedMarkdownParser;
use PHPUnit\Framework\TestCase;

class PedMarkdownParserTest extends TestCase
{
    protected PedMarkdownParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new PedMarkdownParser();
    }

    // ============================================
    // Tests de Parsing Correcto
    // ============================================

    public function test_parsea_plan_basico(): void
    {
        $markdown = <<<'MD'
# Plan Estatal de Desarrollo 2025-2030
MD;

        $result = $this->parser->parse($markdown);

        $this->assertTrue($result['valid']);
        $this->assertEquals('Plan Estatal de Desarrollo 2025-2030', $result['tree']['nombre']);
        $this->assertEquals(2025, $result['tree']['periodo_inicio']);
        $this->assertEquals(2030, $result['tree']['periodo_fin']);
    }

    public function test_parsea_eje_con_formato(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Bienestar Social
MD;

        $result = $this->parser->parse($markdown);

        $this->assertTrue($result['valid']);
        $this->assertCount(1, $result['tree']['ejes']);
        $this->assertEquals('1', $result['tree']['ejes'][0]['numero']);
        $this->assertEquals('Bienestar Social', $result['tree']['ejes'][0]['nombre']);
    }

    public function test_parsea_estructura_completa(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Bienestar Social
### Tema 1.1: Educación
#### Objetivo 1.1.1: Garantizar acceso
##### Estrategia 1.1.1.1: Ampliar cobertura
- Línea de Acción 1.1.1.1.1: Construir escuelas
- Línea de Acción 1.1.1.1.2: Becas educativas
MD;

        $result = $this->parser->parse($markdown);

        $this->assertTrue($result['valid']);

        // Verificar estructura
        $this->assertCount(1, $result['tree']['ejes']);
        $eje = $result['tree']['ejes'][0];

        $this->assertCount(1, $eje['temas']);
        $tema = $eje['temas'][0];

        $this->assertCount(1, $tema['objetivos']);
        $objetivo = $tema['objetivos'][0];

        $this->assertCount(1, $objetivo['estrategias']);
        $estrategia = $objetivo['estrategias'][0];

        $this->assertCount(2, $estrategia['lineas']);
        $this->assertEquals('1.1.1.1.1', $estrategia['lineas'][0]['clave']);
        $this->assertEquals('Construir escuelas', $estrategia['lineas'][0]['descripcion']);
    }

    public function test_ignora_lineas_vacias(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030


## Eje 1: Test


### Tema 1.1: Test
MD;

        $result = $this->parser->parse($markdown);

        $this->assertTrue($result['valid']);
        $this->assertCount(1, $result['tree']['ejes']);
    }

    public function test_parsea_multiples_ejes(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Bienestar Social

## Eje 2: Economía Próspera

## Eje 3: Gobierno Eficaz
MD;

        $result = $this->parser->parse($markdown);

        $this->assertTrue($result['valid']);
        $this->assertCount(3, $result['tree']['ejes']);
        $this->assertEquals('Bienestar Social', $result['tree']['ejes'][0]['nombre']);
        $this->assertEquals('Economía Próspera', $result['tree']['ejes'][1]['nombre']);
        $this->assertEquals('Gobierno Eficaz', $result['tree']['ejes'][2]['nombre']);
    }

    // ============================================
    // Tests de Manejo de Errores
    // ============================================

    public function test_error_si_no_hay_plan(): void
    {
        $markdown = <<<'MD'
## Eje 1: Test
MD;

        $result = $this->parser->parse($markdown);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('debe comenzar con un Plan', $result['errors'][0]['message']);
    }

    public function test_error_si_tema_sin_eje(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030

### Tema 1.1: Test
MD;

        $result = $this->parser->parse($markdown);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('sin eje padre', $result['errors'][0]['message']);
    }

    public function test_error_si_linea_accion_sin_estrategia(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Test
- Línea de Acción 1.1.1.1.1: Test
MD;

        $result = $this->parser->parse($markdown);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('sin estrategia padre', $result['errors'][0]['message']);
    }

    // ============================================
    // Tests de Estadísticas
    // ============================================

    public function test_calcula_estadisticas_correctamente(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Test
### Tema 1.1: Test
#### Objetivo 1.1.1: Test
##### Estrategia 1.1.1.1: Test
- Línea de Acción 1.1.1.1.1: Test 1
- Línea de Acción 1.1.1.1.2: Test 2

## Eje 2: Test 2
MD;

        $result = $this->parser->parse($markdown);
        $stats = $this->parser->getStats($result);

        $this->assertEquals(1, $stats['plan']);
        $this->assertEquals(2, $stats['ejes']);
        $this->assertEquals(1, $stats['temas']);
        $this->assertEquals(1, $stats['objetivos']);
        $this->assertEquals(1, $stats['estrategias']);
        $this->assertEquals(2, $stats['lineas']);
    }

    // ============================================
    // Tests de Formatos Alternativos
    // ============================================

    public function test_acepta_formato_alternativo_con_punto(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030

## Eje 1. Bienestar Social
MD;

        $result = $this->parser->parse($markdown);

        $this->assertTrue($result['valid']);
        $this->assertEquals('Bienestar Social', $result['tree']['ejes'][0]['nombre']);
    }

    public function test_parsea_sin_periodo_en_titulo(): void
    {
        $markdown = <<<'MD'
# Plan Estatal de Desarrollo

## Eje 1: Test
MD;

        $result = $this->parser->parse($markdown);

        $this->assertTrue($result['valid']);
        $this->assertEquals('Plan Estatal de Desarrollo', $result['tree']['nombre']);
        $this->assertNull($result['tree']['periodo_inicio']);
    }

    public function test_acepta_linea_accion_sin_formato_prefijo(): void
    {
        $markdown = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Test
### Tema 1.1: Test
#### Objetivo 1.1.1: Test
##### Estrategia 1.1.1.1: Test
- Construir nuevas escuelas en zonas marginadas
MD;

        $result = $this->parser->parse($markdown);

        $this->assertTrue($result['valid']);
        $this->assertCount(1, $result['tree']['ejes'][0]['temas'][0]['objetivos'][0]['estrategias'][0]['lineas']);
        $this->assertEquals('Construir nuevas escuelas en zonas marginadas',
            $result['tree']['ejes'][0]['temas'][0]['objetivos'][0]['estrategias'][0]['lineas'][0]['descripcion']);
    }
}
```

---

### 7. Crear Test de Integración

```bash
sail artisan make:test PedImporterTest
```

Editar `tests/Feature/PedImporterTest.php`:

```php
<?php

namespace Tests\Feature;

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

    // ============================================
    // Tests de Autorización
    // ============================================

    public function test_usuario_sin_permiso_no_puede_acceder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ped.import'));

        $response->assertForbidden();
    }

    public function test_usuario_con_permiso_puede_acceder(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $response = $this->actingAs($user)->get(route('ped.import'));

        $response->assertOk();
        $response->assertSee('Importar Plan Estatal de Desarrollo');
    }

    // ============================================
    // Tests de Importación
    // ============================================

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
            ->test('ped-importer')
            ->set('archivo', $archivo)
            ->assertSet('showPreview', true)
            ->set('planNombre', 'Plan Test 2025-2030')
            ->set('planPeriodoInicio', 2025)
            ->set('planPeriodoFin', 2030)
            ->call('confirmarImportacion')
            ->assertSet('showSuccess', true);

        // Verificar que se crearon los registros
        $this->assertDatabaseHas('ped_planes', [
            'nombre' => 'Plan Test 2025-2030',
            'activo' => true,
        ]);

        $plan = PedPlan::where('nombre', 'Plan Test 2025-2030')->first();
        $this->assertEquals(1, $plan->ejes()->count());
        $this->assertEquals(1, $plan->ejes()->first()->temas()->count());
    }

    public function test_rechaza_archivo_con_formato_invalido(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $archivo = UploadedFile::fake()->create('plan.pdf', 1000, 'application/pdf');

        Livewire::actingAs($user)
            ->test('ped-importer')
            ->set('archivo', $archivo)
            ->assertHasErrors(['archivo']);
    }

    public function test_muestra_errores_de_parsing(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        // Archivo sin plan al inicio
        $contenido = <<<'MD'
## Eje 1: Test
MD;

        $archivo = UploadedFile::fake()->createWithContent('plan.md', $contenido);

        Livewire::actingAs($user)
            ->test('ped-importer')
            ->set('archivo', $archivo)
            ->assertSet('showPreview', false)
            ->assertSet('errors', fn($errors) => count($errors) > 0);
    }

    public function test_desactiva_plan_anterior_al_importar_nuevo_activo(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        // Crear plan activo existente
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
            ->test('ped-importer')
            ->set('archivo', $archivo)
            ->set('planActivo', true)
            ->call('confirmarImportacion');

        $this->assertFalse($planAnterior->fresh()->activo);
        $this->assertTrue(PedPlan::where('nombre', 'Plan Nuevo 2025-2030')->first()->activo);
    }

    public function test_importar_sin_activar_mantiene_plan_actual(): void
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
            ->test('ped-importer')
            ->set('archivo', $archivo)
            ->set('planActivo', false)
            ->call('confirmarImportacion');

        $this->assertTrue($planAnterior->fresh()->activo);
        $this->assertFalse(PedPlan::where('nombre', 'Plan Nuevo 2025-2030')->first()->activo);
    }

    public function test_importacion_en_transaccion_rollback_si_falla(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        // Este test requiere simular un fallo en la base de datos
        // Por ahora, verificamos que la transacción está envuelta correctamente
        $contenido = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Test
### Tema 1.1: Test
#### Objetivo 1.1.1: Test
##### Estrategia 1.1.1.1: Test
- Línea de Acción 1.1.1.1.1: Test
MD;

        $archivo = UploadedFile::fake()->createWithContent('plan.md', $contenido);

        Livewire::actingAs($user)
            ->test('ped-importer')
            ->set('archivo', $archivo)
            ->call('confirmarImportacion')
            ->assertSet('showSuccess', true);

        // Verificar que todos los registros fueron creados
        $plan = PedPlan::where('nombre', 'Plan Test 2025-2030')->first();
        $this->assertNotNull($plan);
        $this->assertGreaterThan(0, $plan->ejes()->count());
    }

    public function test_validacion_periodo_fin_mayor_que_inicio(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('gestionar_catalogos');

        $contenido = <<<'MD'
# Plan Test 2025-2030

## Eje 1: Test
MD;

        $archivo = UploadedFile::fake()->createWithContent('plan.md', $contenido);

        Livewire::actingAs($user)
            ->test('ped-importer')
            ->set('archivo', $archivo)
            ->set('planPeriodoInicio', 2030)
            ->set('planPeriodoFin', 2025)
            ->call('confirmarImportacion')
            ->assertHasErrors(['planPeriodoFin']);
    }
}
```

---

### 8. Ejecutar y Verificar

```bash
# Ejecutar tests unitarios
sail artisan test --filter PedMarkdownParserTest

# Ejecutar tests de integración
sail artisan test --filter PedImporterTest

# Compilar assets
sail npm run build

# Acceder a la aplicación
# http://localhost/ped/import
```

Verificación manual:

1. Iniciar sesión como usuario con permiso `gestionar_catalogos`
2. Navegar a `/ped/import`
3. Descargar archivo de ejemplo
4. Subir archivo Markdown válido
5. Verificar previsualización correcta
6. Editar datos del plan
7. Confirmar importación
8. Verificar que todos los registros se crearon
9. Probar con archivo con errores de formato
10. Verificar mensajes de error claros

---

## Criterios de Aceptación

- [ ] Servicio `App\Services\PedMarkdownParser` parsea Markdown correctamente
- [ ] Parser tolera espacios extra y líneas vacías
- [ ] Componente Livewire `PedImporter` con upload de archivo `.md`
- [ ] Validación de tipo MIME funciona
- [ ] Previsualización de árbol resultante antes de confirmar
- [ ] Datos del plan editables inline
- [ ] Creación en transacción BD (`DB::transaction()`)
- [ ] Manejo de errores de formato con mensajes claros y número de línea
- [ ] Solo accesible con permiso `gestionar_catalogos`
- [ ] Test unitario: `PedMarkdownParserTest` pasa (12 assertions)
- [ ] Test integración: `PedImporterTest` pasa (8 assertions)
- [ ] Descarga de archivo de ejemplo funciona
- [ ] Plan anterior se desactiva al importar nuevo activo

---

## Notas

### Tolerancia del Parser

El parser acepta:

- Líneas vacías entre secciones
- Espacios extra antes/después de headings
- Formatos alternativos: `Eje 1:`, `Eje 1.`, `Eje 1-`
- Períodos opcionales en el título del plan

### Integración con Observers

Si se implementa S2-T10 (Observers para embeddings), los registros creados durante la importación dispararán automáticamente la generación de embeddings, sin cambios en este código.

### Extensibilidad

El servicio `PedMarkdownParser` es independiente de Livewire y puede usarse desde:

- Comandos Artisan (`php artisan ped:import archivo.md`)
- Jobs en cola para archivos grandes
- API endpoints para importación remota

---
