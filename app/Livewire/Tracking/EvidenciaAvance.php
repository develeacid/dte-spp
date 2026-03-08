<?php

namespace App\Livewire\Tracking;

use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class EvidenciaAvance extends Component
{
    use WithFileUploads;

    public Avance $avance;

    public $archivo;

    public string $nombre_documento = '';

    public string $area_generadora = '';

    public ?string $fecha_documento = null;

    public function mount(Avance $avance): void
    {
        $this->avance = $avance;
    }

    public function guardar(): void
    {
        $this->validate([
            'archivo' => 'required|file|mimes:pdf,xlsx,xls,jpg,jpeg,png,doc,docx|max:10240',
            'nombre_documento' => 'required|string|max:255',
            'area_generadora' => 'nullable|string|max:255',
            'fecha_documento' => 'nullable|date',
        ]);

        if ($this->avance->estaCongelado()) {
            abort(403, 'El avance está congelado.');
        }

        if (! $this->avance->estado->esEditable()) {
            abort(403, 'El avance no es editable.');
        }

        $hash = hash_file('sha256', $this->archivo->getRealPath());

        $path = $this->archivo->store("evidencias/{$this->avance->id}", 'local');

        AvanceEvidencia::create([
            'avance_id' => $this->avance->id,
            'nombre_archivo' => $this->archivo->getClientOriginalName(),
            'ruta_archivo' => $path,
            'mime_type' => $this->archivo->getClientMimeType(),
            'tamano_bytes' => $this->archivo->getSize(),
            'hash_archivo' => $hash,
            'nombre_documento' => $this->nombre_documento,
            'area_generadora' => $this->area_generadora ?: null,
            'fecha_documento' => $this->fecha_documento,
            'subido_por' => auth()->id(),
        ]);

        $this->reset(['archivo', 'nombre_documento', 'area_generadora', 'fecha_documento']);
        $this->avance->refresh();
    }

    public function eliminar(int $evidenciaId): void
    {
        if ($this->avance->estaCongelado()) {
            abort(403, 'El avance está congelado.');
        }

        $evidencia = $this->avance->evidencias()->findOrFail($evidenciaId);

        Storage::disk('local')->delete($evidencia->ruta_archivo);
        $evidencia->delete();

        $this->avance->refresh();
    }

    public function render()
    {
        return view('livewire.tracking.evidencia-avance', [
            'evidencias' => $this->avance->evidencias()->with('subidoPor')->latest()->get(),
        ]);
    }
}
