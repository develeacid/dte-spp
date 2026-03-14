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
    public ?int $parentIdSugerencia = null;
    public array $arbolEjemploPreview = [];
    public bool $mostrarPreviewArbol = false;
    public bool $generandoArbol = false;

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

    public function sugerirCausasIndirectas(int $causaDirectaId): void
    {
        try {
            $this->sugiriendoConIa = true;
            $this->sugerenciasIa = [];
            $this->tipoSugerencia = 'causa_indirecta';
            $this->parentIdSugerencia = $causaDirectaId;

            $llm = app(LlmServiceInterface::class);
            $arbol = Arbol::findOrFail($this->arbolId);

            $problemaCentral = $arbol->nodos()->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)->firstOrFail();
            $causaDirecta = ArbolNodo::findOrFail($causaDirectaId);

            $existentes = $causaDirecta->children()->pluck('descripcion')->implode('; ');

            $prompt = "Dado el problema central: \"{$problemaCentral->descripcion}\" "
                . "y la causa directa: \"{$causaDirecta->descripcion}\", "
                . "sugiere 3 causas indirectas (causas raíz que originan esta causa directa). "
                . ($existentes ? "Ya existen estas causas indirectas: {$existentes}. No las repitas. " : '')
                . "Responde solo con la lista numerada, sin explicaciones.";

            $result = $llm->suggest($prompt);
            $this->sugerenciasIa = collect(explode("\n", $result))
                ->map(fn ($l) => trim(preg_replace('/^\d+[\.\)\-]\s*/', '', trim($l))))
                ->filter(fn ($l) => strlen($l) > 5)
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'No se pudieron generar sugerencias.');
        } finally {
            $this->sugiriendoConIa = false;
        }
    }

    public function agregarSugerencia(string $descripcion, int $parentId, string $tipoNodo): void
    {
        $descripcion = trim(preg_replace('/^\d+[\.\)\-]\s*/', '', $descripcion));

        $actualParentId = ($tipoNodo === 'causa_indirecta' && $this->parentIdSugerencia)
            ? $this->parentIdSugerencia
            : $parentId;

        $maxOrden = ArbolNodo::where('arbol_id', $this->arbolId)
            ->where('parent_id', $actualParentId)
            ->where('tipo_nodo', $tipoNodo)
            ->max('orden') ?? 0;

        ArbolNodo::create([
            'arbol_id' => $this->arbolId,
            'parent_id' => $actualParentId,
            'tipo_nodo' => $tipoNodo,
            'descripcion' => $descripcion,
            'orden' => $maxOrden + 1,
        ]);

        $this->sugerenciasIa = array_values(array_filter(
            $this->sugerenciasIa,
            fn ($s) => trim(preg_replace('/^\d+[\.\)\-]\s*/', '', $s)) !== $descripcion
        ));
    }

    public function generarArbolEjemplo(): void
    {
        try {
            $this->generandoArbol = true;
            $arbol = Arbol::findOrFail($this->arbolId);
            $problemaCentral = $arbol->nodos()
                ->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)
                ->firstOrFail();

            $llm = app(LlmServiceInterface::class);
            $prompt = "Dado el problema central: \"{$problemaCentral->descripcion}\", "
                . "genera un árbol de problemas completo en formato JSON con exactamente esta estructura:\n"
                . "{\n"
                . "  \"causas_directas\": [\n"
                . "    {\"descripcion\": \"...\", \"indirectas\": [\"...\", \"...\"]},\n"
                . "    {\"descripcion\": \"...\", \"indirectas\": [\"...\", \"...\"]}\n"
                . "  ],\n"
                . "  \"efectos_directos\": [\"...\", \"...\"]\n"
                . "}\n"
                . "Exactamente 2 causas directas, 2 causas indirectas por cada directa, y 2 efectos directos. "
                . "Las causas y efectos deben ser específicos, relevantes y no genéricos. "
                . "Responde SOLO con el JSON, sin texto adicional.";

            $result = $llm->suggest($prompt);
            $this->arbolEjemploPreview = json_decode($result, true) ?? [];
            $this->mostrarPreviewArbol = !empty($this->arbolEjemploPreview);
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'No se pudo generar el árbol de ejemplo.');
        } finally {
            $this->generandoArbol = false;
        }
    }

    public function confirmarArbolEjemplo(): void
    {
        if (empty($this->arbolEjemploPreview)) return;

        $arbol = Arbol::findOrFail($this->arbolId);
        $problemaCentral = $arbol->nodos()
            ->where('tipo_nodo', TipoNodo::PROBLEMA_CENTRAL->value)
            ->firstOrFail();

        $orden = 1;
        foreach ($this->arbolEjemploPreview['causas_directas'] ?? [] as $causa) {
            $nodo = ArbolNodo::create([
                'arbol_id' => $this->arbolId,
                'parent_id' => $problemaCentral->id,
                'tipo_nodo' => 'causa_directa',
                'descripcion' => $causa['descripcion'],
                'orden' => $orden++,
            ]);
            $subOrden = 1;
            foreach ($causa['indirectas'] ?? [] as $indirecta) {
                ArbolNodo::create([
                    'arbol_id' => $this->arbolId,
                    'parent_id' => $nodo->id,
                    'tipo_nodo' => 'causa_indirecta',
                    'descripcion' => $indirecta,
                    'orden' => $subOrden++,
                ]);
            }
        }

        $orden = 1;
        foreach ($this->arbolEjemploPreview['efectos_directos'] ?? [] as $efecto) {
            ArbolNodo::create([
                'arbol_id' => $this->arbolId,
                'parent_id' => $problemaCentral->id,
                'tipo_nodo' => 'efecto_directo',
                'descripcion' => $efecto,
                'orden' => $orden++,
            ]);
        }

        $this->arbolEjemploPreview = [];
        $this->mostrarPreviewArbol = false;
        session()->flash('message', 'Árbol de ejemplo generado exitosamente.');
    }

    public function cancelarArbolEjemplo(): void
    {
        $this->arbolEjemploPreview = [];
        $this->mostrarPreviewArbol = false;
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
