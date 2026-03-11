<?php

namespace App\Livewire\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 1 — Definición del Problema')]
class DefinicionProblema extends Component
{
    public ProgramaPresupuestario $programa;
    public string $descripcion = '';
    public string $sugerenciaIa = '';
    public bool $validandoConIa = false;
    public ?array $resultadoValidacion = null;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        $arbol = $programa->arboles()->where('tipo', TipoArbol::PROBLEMA->value)->first();
        if ($arbol) {
            $nodoCentral = $arbol->nodos()->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)->first();
            if ($nodoCentral) {
                $this->descripcion = $nodoCentral->descripcion;
            }
        }
    }

    public function validarConIa(): void
    {
        $this->validate([
            'descripcion' => 'required|min:20',
        ]);

        $this->validandoConIa = true;
        $this->resultadoValidacion = null;
        $this->sugerenciaIa = '';

        try {
            $llm = app(LlmServiceInterface::class);
            $result = $llm->validateProblema($this->descripcion);

            $this->resultadoValidacion = $result->toArray();
            $this->sugerenciaIa = $result->suggestion;
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo conectar con el servicio de IA. Puedes continuar sin validación.');
        } finally {
            $this->validandoConIa = false;
        }
    }

    public function aceptarSugerencia(): void
    {
        if (!empty($this->sugerenciaIa)) {
            $this->descripcion = $this->sugerenciaIa;
            $this->sugerenciaIa = '';
            $this->resultadoValidacion = null;
        }
    }

    public function guardar(): void
    {
        $this->validate([
            'descripcion' => 'required|min:20|max:1000',
        ]);

        $arbol = Arbol::firstOrCreate(
            [
                'programa_presupuestario_id' => $this->programa->id,
                'tipo' => TipoArbol::PROBLEMA->value,
            ]
        );

        ArbolNodo::updateOrCreate(
            [
                'arbol_id' => $arbol->id,
                'tipo_nodo' => TipoNodo::PROBLEMA_CENTRAL->value,
            ],
            [
                'descripcion' => $this->descripcion,
            ]
        );

        session()->flash('success', 'Problema central guardado correctamente.');
    }

    public function render()
    {
        return view('livewire.mml.definicion-problema');
    }
}
