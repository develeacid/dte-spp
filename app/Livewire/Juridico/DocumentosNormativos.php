<?php

namespace App\Livewire\Juridico;

use App\Enums\TipoDocumentoNormativo;
use App\Models\Juridico\DocumentoNormativo;
use App\Models\ProgramaPresupuestario;
use App\Services\Juridico\DocumentoNormativoService;
use App\Services\Juridico\ValidacionJuridicaService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class DocumentosNormativos extends Component
{
    use WithFileUploads;

    public ProgramaPresupuestario $programa;

    public $archivo;
    public string $tipo_documento = '';
    public string $nombre = '';
    public string $fecha_publicacion = '';
    public string $fecha_vigencia = '';

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
    }

    public function upload(): void
    {
        $validated = $this->validate([
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:'.(config('juridico.max_upload_size_mb', 10) * 1024)],
            'tipo_documento' => ['required', 'in:'.implode(',', array_column(TipoDocumentoNormativo::cases(), 'value'))],
            'nombre' => ['required', 'string', 'max:255'],
            'fecha_publicacion' => ['nullable', 'date'],
            'fecha_vigencia' => ['nullable', 'date', 'after:fecha_publicacion'],
        ]);

        app(DocumentoNormativoService::class)->almacenar(
            $this->archivo,
            $this->programa->id,
            [
                'tipo_documento' => $validated['tipo_documento'],
                'nombre' => $validated['nombre'],
                'fecha_publicacion' => $validated['fecha_publicacion'] ?: null,
                'fecha_vigencia' => $validated['fecha_vigencia'] ?: null,
                'registrado_por' => auth()->id(),
                'team_id' => auth()->user()->currentTeam->id,
            ]
        );

        // Recalcular checklist
        app(ValidacionJuridicaService::class)->recalcularChecklist(
            $this->programa->id,
            config('presupuesto.ejercicio_default')
        );

        $this->reset(['archivo', 'tipo_documento', 'nombre', 'fecha_publicacion', 'fecha_vigencia']);
        session()->flash('message', 'Documento subido correctamente.');
    }

    public function verificar(int $documentoId): void
    {
        $this->authorize('gestionar_reglas_operacion');

        app(DocumentoNormativoService::class)->verificar($documentoId, auth()->id());

        // Recalcular checklist
        app(ValidacionJuridicaService::class)->recalcularChecklist(
            $this->programa->id,
            config('presupuesto.ejercicio_default')
        );

        session()->flash('message', 'Documento verificado.');
    }

    public function eliminar(int $documentoId): void
    {
        $this->authorize('gestionar_reglas_operacion');

        $documento = DocumentoNormativo::where('programa_presupuestario_id', $this->programa->id)
            ->where('team_id', auth()->user()->currentTeam->id)
            ->findOrFail($documentoId);

        $documento->delete();

        session()->flash('message', 'Documento eliminado.');
    }

    public function render(): \Illuminate\View\View
    {
        $teamId = auth()->user()->currentTeam->id;

        $documentos = DocumentoNormativo::where('programa_presupuestario_id', $this->programa->id)
            ->paraTeam($teamId)
            ->with(['registrador', 'verificador'])
            ->latest()
            ->get();

        return view('livewire.juridico.documentos-normativos', [
            'documentos' => $documentos,
            'tiposDocumento' => TipoDocumentoNormativo::cases(),
        ]);
    }
}
