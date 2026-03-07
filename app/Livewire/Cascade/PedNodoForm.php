<?php

namespace App\Livewire\Cascade;

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

    // Campos comunes
    public string $clave = '';
    public string $descripcion = '';
    // Campos para eje/tema
    public string $numero = '';
    public string $nombre = '';

    public function mount(string $tipo, ?int $parentId = null, ?int $nodoId = null): void
    {
        $this->tipo = $tipo;
        $this->parentId = $parentId;
        $this->nodoId = $nodoId;

        if ($nodoId) {
            $this->loadNodo($nodoId);
        }
    }

    protected function loadNodo(int $id): void
    {
        $model = $this->getModel()->find($id);
        if (!$model) return;

        if (in_array($this->tipo, ['eje', 'tema'])) {
            $this->numero = $model->numero;
            $this->nombre = $model->nombre;
            $this->descripcion = $model->descripcion ?? '';
        } else {
            $this->clave = $model->clave;
            $this->descripcion = $model->descripcion;
        }
    }

    public function save(): void
    {
        $this->validate($this->getValidationRules());

        $data = in_array($this->tipo, ['eje', 'tema'])
            ? ['numero' => $this->numero, 'nombre' => $this->nombre, 'descripcion' => $this->descripcion]
            : ['clave' => $this->clave, 'descripcion' => $this->descripcion];

        $data = array_merge($data, $this->getParentData());

        if ($this->nodoId) {
            $this->getModel()->find($this->nodoId)?->update($data);
            session()->flash('message', ucfirst($this->getTipoLabel()) . ' actualizado.');
        } else {
            $this->getModel()->create($data);
            session()->flash('message', ucfirst($this->getTipoLabel()) . ' creado.');
        }

        $this->redirect(route('cascade.ped.index'));
    }

    public function delete(): void
    {
        $this->getModel()->find($this->nodoId)?->delete();
        session()->flash('message', ucfirst($this->getTipoLabel()) . ' eliminado.');
        $this->redirect(route('cascade.ped.index'));
    }

    protected function getModel(): \Illuminate\Database\Eloquent\Builder
    {
        return match($this->tipo) {
            'eje'        => PedEje::query(),
            'tema'       => PedTema::query(),
            'objetivo'   => PedObjetivoEstrategico::query(),
            'estrategia' => PedEstrategia::query(),
            'linea'      => PedLineaAccion::query(),
        };
    }

    protected function getParentData(): array
    {
        return match($this->tipo) {
            'eje'        => ['ped_plan_id' => $this->parentId],
            'tema'       => ['ped_eje_id' => $this->parentId],
            'objetivo'   => ['ped_tema_id' => $this->parentId],
            'estrategia' => ['ped_objetivo_estrategico_id' => $this->parentId],
            'linea'      => ['ped_estrategia_id' => $this->parentId],
            default      => [],
        };
    }

    protected function getValidationRules(): array
    {
        if (in_array($this->tipo, ['eje', 'tema'])) {
            return [
                'numero'      => ['required', 'string', 'max:10'],
                'nombre'      => ['required', 'string', 'max:255'],
                'descripcion' => ['nullable', 'string', 'max:500'],
            ];
        }
        return [
            'clave'       => ['required', 'string', 'max:40'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }

    public function getTipoLabel(): string
    {
        return match($this->tipo) {
            'eje'        => 'Eje',
            'tema'       => 'Tema',
            'objetivo'   => 'Objetivo estratégico',
            'estrategia' => 'Estrategia',
            'linea'      => 'Línea de acción',
            default      => $this->tipo,
        };
    }

    public function getDependientes(): array
    {
        if (!$this->nodoId) return [];

        $model = $this->getModel()->find($this->nodoId);
        if (!$model) return [];

        $map = [
            'eje'        => fn($m) => ['temas' => $m->temas()->count()],
            'tema'       => fn($m) => ['objetivos' => $m->objetivosEstrategicos()->count()],
            'objetivo'   => fn($m) => ['estrategias' => $m->estrategias()->count()],
            'estrategia' => fn($m) => ['lineas' => $m->lineasAccion()->count()],
            'linea'      => fn($m) => [],
        ];

        $counts = ($map[$this->tipo] ?? fn($m) => [])($model);
        return array_filter($counts, fn($c) => $c > 0);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.cascade.ped-nodo-form');
    }
}
