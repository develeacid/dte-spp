<?php

namespace App\Livewire;

use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedTema;
use Livewire\Component;

class PedNodoForm extends Component
{
    public string $tipo = 'eje';
    public ?int $parentId = null;
    public ?int $nodoId = null;
    public bool $showModal = false;
    public string $mode = 'create';

    public string $numero = '';
    public string $clave = '';
    public string $nombre = '';
    public string $descripcion = '';

    protected $listeners = [
        'edit-nodo' => 'editByParams',
        'create-nodo' => 'createByParams',
    ];

    protected function rules(): array
    {
        $rules = [
            'descripcion' => ['required', 'string', 'max:500'],
        ];

        if (in_array($this->tipo, ['eje', 'tema'])) {
            $rules['numero'] = ['required', 'string', 'max:10'];
            $rules['nombre'] = ['required', 'string', 'max:255'];
        } else {
            $rules['clave'] = ['required', 'string', 'max:40'];
        }

        return $rules;
    }

    public function createByParams(string $tipo, int $parentId): void
    {
        $this->create($tipo, $parentId);
    }

    public function create(string $tipo, int $parentId): void
    {
        $this->reset(['numero', 'clave', 'nombre', 'descripcion']);
        $this->tipo = $tipo;
        $this->parentId = $parentId;
        $this->nodoId = null;
        $this->mode = 'create';
        $this->showModal = true;
    }

    public function editByParams(string $tipo, int $nodoId): void
    {
        $this->edit($tipo, $nodoId);
    }

    public function edit(string $tipo, int $nodoId): void
    {
        $this->tipo = $tipo;
        $this->nodoId = $nodoId;
        $this->mode = 'edit';

        $nodo = $this->getNodo();

        if ($nodo) {
            $this->parentId = $this->getParentId($nodo);
            $this->numero = $nodo->numero ?? '';
            $this->clave = $nodo->clave ?? '';
            $this->nombre = $nodo->nombre ?? '';
            $this->descripcion = $nodo->descripcion ?? '';
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->buildDataArray();

        if ($this->mode === 'create') {
            $this->createNodo($data);
            $this->dispatch('nodeCreated');
            session()->flash('message', "{$this->getTipoLabel()} creado exitosamente.");
        } else {
            $this->updateNodo($data);
            $this->dispatch('nodeUpdated');
            session()->flash('message', "{$this->getTipoLabel()} actualizado exitosamente.");
        }

        $this->showModal = false;
    }

    public function delete(): void
    {
        $nodo = $this->getNodo();

        if ($nodo) {
            $dependientes = $this->getDependientesCount($nodo);
            $nodo->delete();
            $this->dispatch('nodeDeleted');

            $mensaje = "{$this->getTipoLabel()} eliminado.";
            if ($dependientes > 0) {
                $mensaje .= " Se eliminaron {$dependientes} elementos dependientes.";
            }
            session()->flash('message', $mensaje);
        }

        $this->showModal = false;
    }

    public function getDependientes(): array
    {
        if ($this->mode !== 'edit' || !$this->nodoId) {
            return [];
        }

        $nodo = $this->getNodo();
        return $this->getDependientesData($nodo);
    }

    private function getNodo()
    {
        return match($this->tipo) {
            'eje' => PedEje::find($this->nodoId),
            'tema' => PedTema::find($this->nodoId),
            'objetivo' => PedObjetivoEstrategico::find($this->nodoId),
            'estrategia' => PedEstrategia::find($this->nodoId),
            'linea' => PedLineaAccion::find($this->nodoId),
            default => null,
        };
    }

    private function getParentId($nodo): ?int
    {
        return match($this->tipo) {
            'eje' => $nodo->ped_plan_id,
            'tema' => $nodo->ped_eje_id,
            'objetivo' => $nodo->ped_tema_id,
            'estrategia' => $nodo->ped_objetivo_estrategico_id,
            'linea' => $nodo->ped_estrategia_id,
            default => null,
        };
    }

    private function buildDataArray(): array
    {
        $data = ['descripcion' => $this->descripcion];

        if (in_array($this->tipo, ['eje', 'tema'])) {
            $data['numero'] = $this->numero;
            $data['nombre'] = $this->nombre;
        } else {
            $data['clave'] = $this->clave;
        }

        $data = array_merge($data, match($this->tipo) {
            'eje' => ['ped_plan_id' => $this->parentId],
            'tema' => ['ped_eje_id' => $this->parentId],
            'objetivo' => ['ped_tema_id' => $this->parentId],
            'estrategia' => ['ped_objetivo_estrategico_id' => $this->parentId],
            'linea' => ['ped_estrategia_id' => $this->parentId],
            default => [],
        });

        return $data;
    }

    private function createNodo(array $data)
    {
        return match($this->tipo) {
            'eje' => PedEje::create($data),
            'tema' => PedTema::create($data),
            'objetivo' => PedObjetivoEstrategico::create($data),
            'estrategia' => PedEstrategia::create($data),
            'linea' => PedLineaAccion::create($data),
            default => null,
        };
    }

    private function updateNodo(array $data)
    {
        $nodo = $this->getNodo();
        $nodo?->update($data);
        return $nodo;
    }

    private function getDependientesCount($nodo): int
    {
        return match($this->tipo) {
            'eje' => $nodo->temas()->count(),
            'tema' => $nodo->objetivosEstrategicos()->count(),
            'objetivo' => $nodo->estrategias()->count(),
            'estrategia' => $nodo->lineasAccion()->count(),
            'linea' => 0,
            default => 0,
        };
    }

    private function getDependientesData($nodo): array
    {
        if (!$nodo) {
            return [];
        }

        return match($this->tipo) {
            'eje' => ['temas' => $nodo->temas()->count()],
            'tema' => ['objetivos' => $nodo->objetivosEstrategicos()->count()],
            'objetivo' => ['estrategias' => $nodo->estrategias()->count()],
            'estrategia' => ['lineas' => $nodo->lineasAccion()->count()],
            'linea' => [],
            default => [],
        };
    }

    public function getTipoLabel(): string
    {
        return match($this->tipo) {
            'eje' => 'Eje',
            'tema' => 'Tema',
            'objetivo' => 'Objetivo Estratégico',
            'estrategia' => 'Estrategia',
            'linea' => 'Línea de Acción',
            default => 'Elemento',
        };
    }

    public function render()
    {
        return view('livewire.ped-nodo-form');
    }
}
