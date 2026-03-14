<?php

namespace App\Livewire\Juridico;

use App\Models\Juridico\SustentoLegalPrograma as SustentoModel;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SustentoLegalPrograma extends Component
{
    public ProgramaPresupuestario $programa;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
    }

    public function eliminarFundamento(int $id): void
    {
        $this->authorize('gestionar_sustento_legal');

        $sustento = SustentoModel::where('programa_presupuestario_id', $this->programa->id)
            ->where('team_id', auth()->user()->currentTeam->id)
            ->findOrFail($id);

        $sustento->delete();

        session()->flash('message', 'Fundamento eliminado.');
    }

    public function render(): \Illuminate\View\View
    {
        $teamId = auth()->user()->currentTeam->id;

        $sustentos = SustentoModel::where('programa_presupuestario_id', $this->programa->id)
            ->paraTeam($teamId)
            ->with(['catalogoOrdenamiento', 'registrador'])
            ->orderBy('tipo')
            ->orderBy('nivel_jerarquia')
            ->get()
            ->groupBy(fn ($s) => $s->tipo->value);

        $documentos = $this->programa->documentosNormativos()
            ->where('team_id', $teamId)
            ->with('registrador')
            ->latest()
            ->get();

        $validacion = $this->programa->validacionJuridica;

        return view('livewire.juridico.sustento-legal-programa', [
            'sustentos' => $sustentos,
            'documentos' => $documentos,
            'validacion' => $validacion,
        ]);
    }
}
