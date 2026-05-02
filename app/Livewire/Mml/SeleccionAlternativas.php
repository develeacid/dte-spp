<?php

namespace App\Livewire\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 4 — Selección de Alternativas')]
class SeleccionAlternativas extends Component
{
    public ProgramaPresupuestario $programa;

    public ?int $arbolObjetivosId = null;

    public string $nuevaAlternativaNombre = '';

    public string $justificacionSeleccion = '';

    // IA
    public bool $evaluandoConIa = false;

    public array $evaluacionIa = [];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
        $arbol = $programa->arboles()
            ->where('tipo', TipoArbol::OBJETIVOS->value)
            ->first();
        $this->arbolObjetivosId = $arbol?->id;
    }

    public function crearAlternativa(): void
    {
        $this->validate([
            'nuevaAlternativaNombre' => 'required|min:3|max:100',
        ]);

        Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => $this->nuevaAlternativaNombre,
        ]);

        $this->nuevaAlternativaNombre = '';
    }

    public function toggleNodo(int $alternativaId, int $nodoId): void
    {
        $alternativa = Alternativa::findOrFail($alternativaId);

        if ($alternativa->nodos()->where('arbol_nodo_id', $nodoId)->exists()) {
            $alternativa->nodos()->detach($nodoId);
        } else {
            $alternativa->nodos()->attach($nodoId);
        }
    }

    public function seleccionarAlternativa(int $alternativaId): void
    {
        $this->validate([
            'justificacionSeleccion' => 'required|min:20|max:1000',
        ]);

        Alternativa::where('programa_presupuestario_id', $this->programa->id)
            ->update(['seleccionada' => false, 'justificacion_seleccion' => null]);

        $alternativa = Alternativa::findOrFail($alternativaId);
        $alternativa->update([
            'seleccionada' => true,
            'justificacion_seleccion' => $this->justificacionSeleccion,
        ]);

        $this->justificacionSeleccion = '';
        session()->flash('success', "Alternativa \"{$alternativa->nombre}\" seleccionada.");
    }

    public function eliminarAlternativa(int $alternativaId): void
    {
        Alternativa::findOrFail($alternativaId)->delete();
    }

    public function evaluarConIa(int $alternativaId): void
    {
        $this->evaluandoConIa = true;
        $this->evaluacionIa = [];

        try {
            $alternativa = Alternativa::with('nodos')->findOrFail($alternativaId);
            $llm = app(LlmServiceInterface::class);

            $arbol = Arbol::find($this->arbolObjetivosId);
            $objetivoCentral = $arbol->nodos()
                ->where('tipo_nodo', TipoNodo::OBJETIVO_CENTRAL->value)
                ->first();

            $promptText = view('prompts.mml.evaluar-alternativa', [
                'programa' => $this->programa->nombre,
                'nombreAlternativa' => $alternativa->nombre,
                'medios' => $alternativa->nodos->pluck('descripcion')->toArray(),
                'objetivoCentral' => $objetivoCentral?->descripcion ?? '',
            ])->render();

            $result = $llm->suggest($promptText);
            $this->evaluacionIa = json_decode($result, true) ?? [];
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo evaluar con IA.');
        } finally {
            $this->evaluandoConIa = false;
        }
    }

    public function render()
    {
        $medios = collect();
        $alternativaSeleccionada = null;

        if ($this->arbolObjetivosId) {
            $medios = ArbolNodo::where('arbol_id', $this->arbolObjetivosId)
                ->whereIn('tipo_nodo', [
                    TipoNodo::MEDIO_DIRECTO->value,
                    TipoNodo::MEDIO_INDIRECTO->value,
                ])
                ->orderBy('orden')
                ->get();

            $alternativaSeleccionada = $this->programa->alternativas()
                ->where('seleccionada', true)
                ->with('nodos')
                ->first();
        }

        $alternativas = $this->programa->alternativas()
            ->with('nodos')
            ->get();

        $tieneMir = $this->programa->mirNiveles()->exists();

        return view('livewire.mml.seleccion-alternativas', [
            'medios' => $medios,
            'alternativas' => $alternativas,
            'alternativaSeleccionada' => $alternativaSeleccionada,
            'tieneMir' => $tieneMir,
        ]);
    }
}
