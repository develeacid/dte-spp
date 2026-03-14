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
#[Title('Etapa 2 — Árbol del Problema')]
class ArbolProblemaBuilder extends Component
{
    public ProgramaPresupuestario $programa;
    public ?int $arbolId = null;

    // Estado del formulario de nuevo nodo
    public bool $mostrarFormNuevoNodo = false;
    public ?int $parentIdNuevoNodo = null;
    public string $tipoNuevoNodo = '';
    public string $nuevoNodoDescripcion = '';

    // Estado del formulario de edición
    public ?int $editNodoId = null;
    public string $editNodoDescripcion = '';

    // Estado de IA
    public bool $sugiriendoConIa = false;
    public array $sugerenciasIa = [];
    public string $tipoSugerencia = 'causa_directa';

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;
        $arbol = $programa->arboles()
            ->where('tipo', TipoArbol::PROBLEMA->value)
            ->first();
        $this->arbolId = $arbol?->id;
    }

    public function agregarNodo(int $parentId, string $tipoNodo): void
    {
        $this->mostrarFormNuevoNodo = true;
        $this->parentIdNuevoNodo = $parentId;
        $this->tipoNuevoNodo = $tipoNodo;
        $this->nuevoNodoDescripcion = '';
    }

    public function cancelarNuevoNodo(): void
    {
        $this->mostrarFormNuevoNodo = false;
        $this->parentIdNuevoNodo = null;
        $this->tipoNuevoNodo = '';
        $this->nuevoNodoDescripcion = '';
    }

    public function guardarNuevoNodo(): void
    {
        $this->validate([
            'nuevoNodoDescripcion' => 'required|min:10|max:500',
        ]);

        $maxOrden = ArbolNodo::where('arbol_id', $this->arbolId)
            ->where('parent_id', $this->parentIdNuevoNodo)
            ->max('orden') ?? 0;

        ArbolNodo::create([
            'arbol_id' => $this->arbolId,
            'parent_id' => $this->parentIdNuevoNodo,
            'tipo_nodo' => $this->tipoNuevoNodo,
            'descripcion' => $this->nuevoNodoDescripcion,
            'orden' => $maxOrden + 1,
        ]);

        $this->cancelarNuevoNodo();
    }

    public function editarNodo(int $nodoId): void
    {
        $nodo = ArbolNodo::findOrFail($nodoId);
        $this->editNodoId = $nodoId;
        $this->editNodoDescripcion = $nodo->descripcion;
    }

    public function cancelarEdicion(): void
    {
        $this->editNodoId = null;
        $this->editNodoDescripcion = '';
    }

    public function actualizarNodo(): void
    {
        $this->validate([
            'editNodoDescripcion' => 'required|min:10|max:500',
        ]);

        $nodo = ArbolNodo::findOrFail($this->editNodoId);
        $nodo->update(['descripcion' => $this->editNodoDescripcion]);

        $this->cancelarEdicion();
    }

    public function eliminarNodo(int $nodoId): void
    {
        $nodo = ArbolNodo::findOrFail($nodoId);

        if ($nodo->tipo_nodo === TipoNodo::PROBLEMA_CENTRAL) {
            $this->dispatch('notify', message: 'El problema central no se puede eliminar.');
            return;
        }

        $nodo->delete();
    }

    public function sugerirConIa(string $tipoSugerencia): void
    {
        $this->sugiriendoConIa = true;
        $this->sugerenciasIa = [];
        $this->tipoSugerencia = match($tipoSugerencia) {
            'causa' => 'causa_directa',
            'efecto' => 'efecto_directo',
            default => $tipoSugerencia,
        };

        try {
            $llm = app(LlmServiceInterface::class);
            $arbol = Arbol::find($this->arbolId);

            $problemaCentral = $arbol->nodos()
                ->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)
                ->first();

            $nodosExistentes = $arbol->nodos()
                ->where('tipo_nodo', 'like', $tipoSugerencia . '%')
                ->pluck('descripcion')
                ->implode(', ');

            $prompt = "Problema central: \"{$problemaCentral->descripcion}\". "
                . "Nodos existentes de tipo {$tipoSugerencia}: [{$nodosExistentes}]. "
                . "Sugiere 3 {$tipoSugerencia}s adicionales que no estén ya listados. "
                . "Responde solo con una lista numerada, un elemento por línea.";

            $result = $llm->suggest($prompt);

            $this->sugerenciasIa = array_filter(
                array_map('trim', explode("\n", $result)),
                fn ($line) => !empty($line) && preg_match('/^\d/', $line)
            );
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudieron generar sugerencias de IA.');
        } finally {
            $this->sugiriendoConIa = false;
        }
    }

    public function agregarSugerencia(string $descripcion, int $parentId, string $tipoNodo): void
    {
        $descripcion = preg_replace('/^\d+\.\s*/', '', $descripcion);

        $maxOrden = ArbolNodo::where('arbol_id', $this->arbolId)
            ->where('parent_id', $parentId)
            ->max('orden') ?? 0;

        ArbolNodo::create([
            'arbol_id' => $this->arbolId,
            'parent_id' => $parentId,
            'tipo_nodo' => $tipoNodo,
            'descripcion' => $descripcion,
            'orden' => $maxOrden + 1,
        ]);

        $this->sugerenciasIa = array_values(array_filter(
            $this->sugerenciasIa,
            fn ($s) => $s !== $descripcion
        ));
    }

    public function render()
    {
        $arbol = $this->arbolId ? Arbol::with('nodos')->find($this->arbolId) : null;

        $tieneMir = $this->programa->mirNiveles()->exists();

        return view('livewire.mml.arbol-problema-builder', [
            'arbol' => $arbol,
            'tieneMir' => $tieneMir,
        ]);
    }
}
