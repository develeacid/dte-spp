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
#[Title('Etapa 3 — Árbol de Objetivos')]
class ArbolObjetivosBuilder extends Component
{
    public ProgramaPresupuestario $programa;
    public ?int $arbolProblemaId = null;
    public ?int $arbolObjetivosId = null;

    // Edición
    public ?int $editNodoId = null;
    public string $editDescripcion = '';

    // IA
    public bool $transformandoConIa = false;

    private const MAPEO_TIPOS = [
        'problema_central' => 'objetivo_central',
        'causa_directa' => 'medio_directo',
        'causa_indirecta' => 'medio_indirecto',
        'efecto_directo' => 'fin_directo',
        'efecto_indirecto' => 'fin_indirecto',
    ];

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        $arbolProblema = $programa->arboles()
            ->where('tipo', TipoArbol::PROBLEMA->value)->first();
        $this->arbolProblemaId = $arbolProblema?->id;

        if ($arbolProblema) {
            $arbolObjetivos = $programa->arboles()
                ->where('tipo', TipoArbol::OBJETIVOS->value)->first();

            if (!$arbolObjetivos) {
                $arbolObjetivos = $this->generarArbolObjetivos($arbolProblema);
            }

            $this->arbolObjetivosId = $arbolObjetivos->id;
        }
    }

    private function generarArbolObjetivos(Arbol $arbolProblema): Arbol
    {
        $arbolObjetivos = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);

        $nodosRaiz = $arbolProblema->nodos()
            ->whereNull('parent_id')
            ->orderBy('orden')
            ->get();

        foreach ($nodosRaiz as $nodoProblema) {
            $this->transformarNodo($arbolObjetivos, $nodoProblema, null);
        }

        return $arbolObjetivos;
    }

    private function transformarNodo(Arbol $arbolObjetivos, ArbolNodo $nodoProblema, ?int $parentObjetivoId): void
    {
        $tipoOriginal = $nodoProblema->tipo_nodo instanceof TipoNodo
            ? $nodoProblema->tipo_nodo->value
            : $nodoProblema->tipo_nodo;

        $tipoObjetivo = self::MAPEO_TIPOS[$tipoOriginal] ?? null;

        if (!$tipoObjetivo) {
            return;
        }

        $nodoObjetivo = ArbolNodo::create([
            'arbol_id' => $arbolObjetivos->id,
            'parent_id' => $parentObjetivoId,
            'tipo_nodo' => $tipoObjetivo,
            'descripcion' => '[Pendiente de transformación] ' . $nodoProblema->descripcion,
            'nodo_origen_id' => $nodoProblema->id,
            'orden' => $nodoProblema->orden,
        ]);

        foreach ($nodoProblema->children()->orderBy('orden')->get() as $hijo) {
            $this->transformarNodo($arbolObjetivos, $hijo, $nodoObjetivo->id);
        }
    }

    public function transformarConIa(int $nodoObjetivoId): void
    {
        $nodoObj = ArbolNodo::findOrFail($nodoObjetivoId);
        $nodoOrigen = $nodoObj->nodoOrigen;

        if (!$nodoOrigen) {
            return;
        }

        $this->transformandoConIa = true;

        try {
            $llm = app(LlmServiceInterface::class);

            $tipoOriginal = $nodoOrigen->tipo_nodo instanceof TipoNodo
                ? $nodoOrigen->tipo_nodo->value
                : $nodoOrigen->tipo_nodo;

            $promptText = view('prompts.mml.transformar-a-positivo', [
                'tipoOriginal' => $tipoOriginal,
                'textoNegativo' => $nodoOrigen->descripcion,
            ])->render();

            $result = $llm->transform($nodoOrigen->descripcion, $promptText);

            $nodoObj->update(['descripcion' => $result]);
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo transformar con IA. Edita manualmente.');
        } finally {
            $this->transformandoConIa = false;
        }
    }

    public function editarNodo(int $nodoId): void
    {
        $nodo = ArbolNodo::findOrFail($nodoId);
        $this->editNodoId = $nodoId;
        $this->editDescripcion = $nodo->descripcion;
    }

    public function cancelarEdicion(): void
    {
        $this->editNodoId = null;
        $this->editDescripcion = '';
    }

    public function guardarEdicion(): void
    {
        $this->validate([
            'editDescripcion' => 'required|min:10|max:500',
        ]);

        $nodo = ArbolNodo::findOrFail($this->editNodoId);
        $nodo->update(['descripcion' => $this->editDescripcion]);
        $this->cancelarEdicion();
    }

    public function render()
    {
        $paresNodos = [];

        if ($this->arbolObjetivosId) {
            $nodosObjetivo = ArbolNodo::where('arbol_id', $this->arbolObjetivosId)
                ->with('nodoOrigen')
                ->orderBy('orden')
                ->get();

            foreach ($nodosObjetivo as $nodoObj) {
                $paresNodos[] = [
                    'objetivo' => $nodoObj,
                    'problema' => $nodoObj->nodoOrigen,
                ];
            }
        }

        return view('livewire.mml.arbol-objetivos-builder', [
            'paresNodos' => $paresNodos,
        ]);
    }
}
