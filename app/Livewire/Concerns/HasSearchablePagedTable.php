<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

trait HasSearchablePagedTable
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortBy = '';

    #[Url(as: 'dir')]
    public string $sortDir = 'asc';

    #[Url(as: 'per')]
    public ?int $perPage = 25;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function updatingSortDir(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleSort(string $by): void
    {
        if ($this->sortBy === $by) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $by;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    protected function effectivePerPage(): int
    {
        return $this->perPage ?? 1000;
    }

    protected function applySearch(Builder $query, string $column): Builder
    {
        if ($this->search !== '') {
            $query->where($column, 'ilike', '%'.$this->search.'%');
        }

        return $query;
    }
}
