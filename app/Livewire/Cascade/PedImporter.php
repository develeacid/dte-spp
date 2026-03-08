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
    public array $parseErrors = [];

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

            $this->parseErrors = $result['errors'];
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
            $this->parseErrors = [[
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
            $this->parseErrors = [[
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
            'parseErrors',
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
