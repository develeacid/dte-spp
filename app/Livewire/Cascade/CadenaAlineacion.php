<?php

namespace App\Livewire\Cascade;

use App\Models\PedLineaAccion;
use Livewire\Component;

class CadenaAlineacion extends Component
{
    public bool $showModal = false;
    public ?int $lineaAccionId = null;

    protected $listeners = ['verCadena' => 'loadCadena'];

    public function loadCadena(int $lineaId): void
    {
        $this->lineaAccionId = $lineaId;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->lineaAccionId = null;
    }

    public function getCadenaProperty(): ?array
    {
        if (!$this->lineaAccionId) {
            return null;
        }

        $lineaAccion = PedLineaAccion::with([
            'estrategia.objetivoEstrategico.pndObjetivos.odsMetas.objetivo',
            'estrategia.objetivoEstrategico.tema.eje.plan',
            'programasDerivadosObjetivos.programa',
        ])->find($this->lineaAccionId);

        if (!$lineaAccion) {
            return null;
        }

        return [
            'linea_accion' => $lineaAccion,
            'estrategia' => $lineaAccion->estrategia,
            'objetivo_estrategico' => $lineaAccion->estrategia->objetivoEstrategico,
            'tema' => $lineaAccion->estrategia->objetivoEstrategico->tema,
            'eje' => $lineaAccion->estrategia->objetivoEstrategico->tema->eje,
            'plan' => $lineaAccion->estrategia->objetivoEstrategico->tema->eje->plan,
            'pnd_objetivos' => $lineaAccion->estrategia->objetivoEstrategico->pndObjetivos,
            'ods_metas' => $lineaAccion->estrategia->objetivoEstrategico->pndObjetivos->flatMap->odsMetas->unique('id'),
            'programas_objetivos' => $lineaAccion->programasDerivadosObjetivos,
        ];
    }

    public function render()
    {
        return view('livewire.cascade.cadena-alineacion');
    }
}
