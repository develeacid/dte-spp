<?php

namespace App\Livewire\Mml;

use App\Models\Evaluation\AnexoTransversal;
use App\Models\Mml\MirNivel;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedTema;
use App\Models\ProgramaPresupuestario;
use App\Services\Embeddings\SemanticSearchService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Etapa 6 — Alineación Estratégica')]
class AlineacionEstrategica extends Component
{
    public ProgramaPresupuestario $programa;

    // PED selects dependientes
    public ?int $ejeId = null;
    public ?int $temaId = null;
    public ?int $objetivoEstrategicoId = null;

    // ODS
    public array $odsSeleccionados = [];

    // Anexos transversales
    public array $anexosSeleccionados = [];

    // IA suggestions
    public array $sugerenciasIa = [];
    public bool $buscandoConIa = false;

    public function mount(ProgramaPresupuestario $programa): void
    {
        $this->programa = $programa;

        // Cargar alineación existente desde el nivel FIN de la MIR
        $fin = $programa->mirNiveles()->where('tipo_nivel', 'fin')->first();

        if ($fin?->ped_objetivo_estrategico_id) {
            $objetivo = PedObjetivoEstrategico::with('tema.eje')->find($fin->ped_objetivo_estrategico_id);
            if ($objetivo) {
                $this->ejeId = $objetivo->tema->eje->id;
                $this->temaId = $objetivo->tema->id;
                $this->objetivoEstrategicoId = $objetivo->id;
            }
        }
    }

    public function updatedEjeId(): void
    {
        $this->temaId = null;
        $this->objetivoEstrategicoId = null;
    }

    public function updatedTemaId(): void
    {
        $this->objetivoEstrategicoId = null;
    }

    public function buscarConIa(): void
    {
        $this->buscandoConIa = true;
        $this->sugerenciasIa = [];

        try {
            // Obtener el problema central del programa
            $arbol = $this->programa->arboles()->where('tipo', 'problema')->first();
            $problema = $arbol?->nodos()->where('tipo_nodo', 'problema_central')->first();

            if (! $problema?->descripcion) {
                session()->flash('error', 'No se encontró el problema central para buscar alineación.');
                return;
            }

            $search = app(SemanticSearchService::class);
            $resultados = $search->findSimilar($problema->descripcion, PedObjetivoEstrategico::class, 5, 0.0);

            // Cargar relaciones para los modelos devueltos por la búsqueda vectorial
            $ids = $resultados->pluck('model.id')->all();
            $objetivos = PedObjetivoEstrategico::with('tema.eje')->whereIn('id', $ids)->get()->keyBy('id');

            $this->sugerenciasIa = $resultados->map(function ($r) use ($objetivos) {
                $obj = $objetivos->get($r->model->id);

                return [
                    'id' => $r->model->id,
                    'descripcion' => $obj?->descripcion ?? $r->model->descripcion,
                    'clave' => $obj?->clave_completa ?? '',
                    'tema' => $obj?->tema?->nombre ?? '',
                    'eje' => $obj?->tema?->eje?->nombre ?? '',
                    'score' => round($r->score * 100),
                ];
            })->toArray();
        } catch (\Throwable $e) {
            session()->flash('error', 'Error al buscar alineación con IA: ' . $e->getMessage());
        } finally {
            $this->buscandoConIa = false;
        }
    }

    public function seleccionarSugerencia(int $objetivoId): void
    {
        $objetivo = PedObjetivoEstrategico::with('tema.eje')->find($objetivoId);

        if ($objetivo) {
            $this->ejeId = $objetivo->tema->eje->id;
            $this->temaId = $objetivo->tema->id;
            $this->objetivoEstrategicoId = $objetivo->id;
        }
    }

    public function guardar(): void
    {
        if (! $this->objetivoEstrategicoId) {
            $this->addError('objetivoEstrategicoId', 'Selecciona al menos un Objetivo Estratégico del PED.');
            return;
        }

        // Guardar alineación en el nivel FIN de la MIR (crear si no existe)
        $fin = $this->programa->mirNiveles()->where('tipo_nivel', 'fin')->first();

        if (! $fin) {
            $fin = $this->programa->mirNiveles()->create([
                'tipo_nivel' => 'fin',
                'orden' => 0,
            ]);
        }

        $fin->update([
            'ped_objetivo_estrategico_id' => $this->objetivoEstrategicoId,
        ]);

        session()->flash('success', 'Alineación estratégica guardada correctamente.');
    }

    public function finalizarPlaneacion(): void
    {
        // Verificar que todos los pasos estén completos
        if (! $this->objetivoEstrategicoId) {
            session()->flash('error', 'Completa la alineación estratégica antes de finalizar.');
            return;
        }

        // Marcar planeación como completada
        $this->programa->update(['planeacion_completada_at' => now()]);

        // Pre-llenar MIR desde el EAP
        app(\App\Services\Mml\MirPrellenadoService::class)
            ->prellenarDesdeEAP($this->programa);

        // Redirigir a la MIR
        $this->redirect(route('mml.mir', $this->programa));
    }

    public function render()
    {
        $ejes = PedEje::orderBy('numero')->get();
        $temas = $this->ejeId ? PedTema::where('ped_eje_id', $this->ejeId)->orderBy('numero')->get() : collect();
        $objetivos = $this->temaId ? PedObjetivoEstrategico::where('ped_tema_id', $this->temaId)->orderBy('clave')->get() : collect();
        $odsObjetivos = OdsObjetivo::orderBy('numero')->get();
        $anexos = AnexoTransversal::activos()->get();

        return view('livewire.mml.alineacion-estrategica', compact(
            'ejes', 'temas', 'objetivos', 'odsObjetivos', 'anexos'
        ));
    }
}
