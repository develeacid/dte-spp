<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

trait HasTraceableTable
{
    use HasSearchablePagedTable;

    #[Url(as: 'group')]
    public bool $groupByPrograma = true;

    public function updatingGroupByPrograma(): void
    {
        $this->resetPage();
    }

    protected function applyTraceableFilters(
        Builder $query,
        string $searchColumn,
        string $programaColumn,
        string $fechaColumn,
        string $indicadorOrderColumn,
    ): Builder {
        $this->applySearch($query, $searchColumn);

        if ($this->groupByPrograma) {
            $query->orderBy($programaColumn);
        }

        $col = $this->sortBy === 'fecha' ? $fechaColumn : $indicadorOrderColumn;
        $query->orderBy($col, $this->sortDir);

        return $query;
    }
}
