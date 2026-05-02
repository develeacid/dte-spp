<?php

namespace App\Livewire\Transparencia\Datasets;

use App\Models\Transparencia\DatasetAbierto;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $filtroStatus = '';

    #[Url(as: 'sistema', except: '')]
    public string $filtroSistema = '';

    #[Url(as: 'tipo', except: '')]
    public string $filtroTipo = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroSistema(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroTipo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = DatasetAbierto::query()->with('creadoPor');

        if ($this->search !== '') {
            // Escapa wildcards LIKE/ILIKE para que `%` y `_` se interpreten literales.
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('dataset_clave', 'ilike', $term)
                    ->orWhere('nombre', 'ilike', $term);
            });
        }

        if ($this->filtroStatus !== '') {
            $query->where('status', $this->filtroStatus);
        }

        if ($this->filtroSistema !== '') {
            $query->where('sistema_origen', $this->filtroSistema);
        }

        if ($this->filtroTipo === 'plantilla') {
            $query->whereNull('periodo');
        } elseif ($this->filtroTipo === 'entrega') {
            $query->whereNotNull('periodo');
        }

        return view('livewire.transparencia.datasets.index', [
            'datasets' => $query->orderByDesc('updated_at')->paginate(20),
        ]);
    }
}
