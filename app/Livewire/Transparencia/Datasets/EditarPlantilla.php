<?php

namespace App\Livewire\Transparencia\Datasets;

use App\Models\Transparencia\DatasetAbierto;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EditarPlantilla extends Component
{
    public DatasetAbierto $dataset;
    public string $nombre = '';
    public string $descripcion = '';
    public string $dcat_metadata_json = '';

    public function mount(DatasetAbierto $dataset): void
    {
        abort_if($dataset->periodo !== null, 404);
        $this->authorize('editarPlantilla', $dataset);

        $this->dataset = $dataset;
        $this->nombre = $dataset->nombre;
        $this->descripcion = $dataset->descripcion ?? '';
        $this->dcat_metadata_json = json_encode($dataset->dcat_metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function save(): void
    {
        $this->authorize('editarPlantilla', $this->dataset);

        $this->validate([
            'nombre' => 'required|string|min:3|max:255',
            'descripcion' => 'nullable|string|max:5000',
            'dcat_metadata_json' => ['required', 'string', function ($attribute, $value, $fail) {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('El DCAT metadata debe ser JSON válido.');
                }
            }],
        ]);

        $this->dataset->update([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'dcat_metadata' => json_decode($this->dcat_metadata_json, true),
        ]);

        session()->flash('success', 'Plantilla actualizada.');
        $this->redirect(route('transparencia.datos-abiertos.show', $this->dataset), navigate: true);
    }

    public function render()
    {
        return view('livewire.transparencia.datasets.editar-plantilla');
    }
}
