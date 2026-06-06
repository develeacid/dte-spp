<?php

namespace Tests\Feature\Livewire\Concerns;

use App\Livewire\Concerns\HasSearchablePagedTable;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

class FakeListComponent extends Component
{
    use HasSearchablePagedTable;

    public function render()
    {
        return '<div></div>';
    }
}

class HasSearchablePagedTableTest extends TestCase
{
    public function test_search_default_vacio(): void
    {
        Livewire::test(FakeListComponent::class)
            ->assertSet('search', '')
            ->assertSet('sortDir', 'asc')
            ->assertSet('perPage', 25);
    }

    public function test_toggle_sort_alterna_direccion_si_misma_columna(): void
    {
        Livewire::test(FakeListComponent::class)
            ->set('sortBy', 'name')
            ->call('toggleSort', 'name')
            ->assertSet('sortDir', 'desc')
            ->call('toggleSort', 'name')
            ->assertSet('sortDir', 'asc');
    }

    public function test_toggle_sort_cambia_columna_y_resetea_a_asc(): void
    {
        Livewire::test(FakeListComponent::class)
            ->set('sortBy', 'name')
            ->set('sortDir', 'desc')
            ->call('toggleSort', 'email')
            ->assertSet('sortBy', 'email')
            ->assertSet('sortDir', 'asc');
    }

    public function test_updating_search_resetea_pagina(): void
    {
        Livewire::test(FakeListComponent::class)
            ->set('paginators.page', 5)
            ->set('search', 'foo')
            ->assertSet('paginators.page', 1);
    }

    public function test_effective_per_page_devuelve_1000_cuando_per_page_es_null(): void
    {
        $c = new FakeListComponent;
        $c->perPage = null;

        $reflection = new ReflectionClass($c);
        $method = $reflection->getMethod('effectivePerPage');
        $method->setAccessible(true);

        $this->assertSame(1000, $method->invoke($c));
    }

    public function test_url_state_usa_keys_cortas(): void
    {
        $reflection = new ReflectionClass(HasSearchablePagedTable::class);
        $code = file_get_contents($reflection->getFileName());

        $this->assertStringContainsString("Url(as: 'q'", $code);
        $this->assertStringContainsString("Url(as: 'sort'", $code);
        $this->assertStringContainsString("Url(as: 'dir'", $code);
        $this->assertStringContainsString("Url(as: 'per'", $code);
    }
}
