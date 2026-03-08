<?php

namespace App\Livewire\Mml;

use App\DTOs\ImportedMirData;
use App\Models\Mml\ImportacionReporte;
use App\Services\Mml\MirDiagnosticoService;
use App\Services\Mml\MirParserService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Importar Programa — MIR')]
class ImportarPrograma extends Component
{
    use WithFileUploads;

    public $archivo;

    public ?array $preview = null;
    public ?array $diagnostico = null;
    public ?array $conteo = null;
    public ?int $importacionId = null;
    public ?string $errorMensaje = null;

    public function rules(): array
    {
        return [
            'archivo' => 'required|file|max:5120',
        ];
    }

    public function updatedArchivo(): void
    {
        $this->validate();

        $this->reset(['preview', 'diagnostico', 'conteo', 'importacionId', 'errorMensaje']);

        $originalName = $this->archivo->getClientOriginalName();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $allowedExtensions = ['md', 'csv', 'xlsx'];
        if (!in_array($extension, $allowedExtensions)) {
            $this->errorMensaje = 'Formato no soportado. Use archivos .md, .csv o .xlsx.';
            return;
        }

        try {
            $parser = new MirParserService();
            $data = match ($extension) {
                'md' => $parser->fromMarkdown($this->archivo->get()),
                'csv' => $parser->fromCsv($this->archivo->getRealPath()),
                'xlsx' => $parser->fromExcel($this->archivo->getRealPath()),
            };
        } catch (\Throwable $e) {
            $this->errorMensaje = 'Error al procesar el archivo: ' . $e->getMessage();
            return;
        }

        if (empty($data->niveles)) {
            $this->errorMensaje = 'El archivo no contiene datos válidos de MIR.';
            return;
        }

        $diagnosticoService = new MirDiagnosticoService();
        $this->diagnostico = $diagnosticoService->diagnosticar($data);
        $this->conteo = $diagnosticoService->conteo($this->diagnostico);

        $user = auth()->user();

        $reporte = ImportacionReporte::create([
            'team_id' => $user->currentTeam->id,
            'archivo_original' => $originalName,
            'formato' => $extension,
            'datos_parseados' => $data->toArray(),
            'diagnostico' => $this->diagnostico,
            'estado' => 'pendiente',
            'created_by' => $user->id,
        ]);

        $this->importacionId = $reporte->id;

        $this->preview = [
            'nombre' => $data->nombre,
            'clave' => $data->clave,
            'ejercicio_fiscal' => $data->ejercicioFiscal,
            'niveles' => collect($data->niveles)->map(fn (array $n) => [
                'tipo_nivel' => $n['tipo_nivel'],
                'resumen_narrativo' => $n['resumen_narrativo'] ?? '-',
                'indicadores_count' => count($n['indicadores'] ?? []),
            ])->toArray(),
        ];
    }

    public function continuar(): \Illuminate\Http\RedirectResponse
    {
        // Redirect to the completar route (to be defined in S5-T2)
        return redirect()->route('mml.importar.completar', ['importacion' => $this->importacionId]);
    }

    public function render()
    {
        return view('livewire.mml.importar-programa');
    }
}
