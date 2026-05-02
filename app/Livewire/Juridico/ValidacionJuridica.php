<?php

namespace App\Livewire\Juridico;

use App\Models\ProgramaPresupuestario;
use App\Services\Juridico\ValidacionJuridicaService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ValidacionJuridica extends Component
{
    public ProgramaPresupuestario $programa;

    public string $observaciones = '';

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
    }

    public function validar(): void
    {
        $this->authorize('validar_sustento_legal');

        try {
            app(ValidacionJuridicaService::class)->validar(
                $this->programa->id,
                config('presupuesto.ejercicio_default'),
                auth()->id(),
                $this->observaciones ?: null
            );

            session()->flash('message', 'Programa validado jurídicamente.');
            $this->redirect(route('juridico.programa', $this->programa));
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function rechazar(): void
    {
        $this->authorize('validar_sustento_legal');

        $this->validate([
            'observaciones' => ['required', 'string', 'min:10'],
        ]);

        app(ValidacionJuridicaService::class)->rechazar(
            $this->programa->id,
            config('presupuesto.ejercicio_default'),
            auth()->id(),
            $this->observaciones
        );

        session()->flash('message', 'Validación rechazada.');
        $this->redirect(route('juridico.programa', $this->programa));
    }

    public function marcarEnRevision(): void
    {
        $this->authorize('validar_sustento_legal');

        $validacion = $this->programa->validacionJuridica;
        if ($validacion) {
            $validacion->update(['estado' => 'en_revision']);
        }

        session()->flash('message', 'Programa marcado en revisión.');
    }

    public function render(): View
    {
        // Recalculate checklist before rendering
        $validacion = app(ValidacionJuridicaService::class)->recalcularChecklist(
            $this->programa->id,
            config('presupuesto.ejercicio_default')
        );

        $sustentos = $this->programa->sustentosLegales()
            ->where('team_id', auth()->user()->currentTeam->id)
            ->with('catalogoOrdenamiento')
            ->get();

        $documentos = $this->programa->documentosNormativos()
            ->where('team_id', auth()->user()->currentTeam->id)
            ->get();

        return view('livewire.juridico.validacion-juridica', [
            'validacion' => $validacion,
            'sustentos' => $sustentos,
            'documentos' => $documentos,
        ]);
    }
}
