<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    #[Test]
    public function renderiza_columnas_definidas(): void
    {
        $rows = collect([['nombre' => 'X', 'meta' => 10]]);
        $columns = [
            ['key' => 'nombre', 'label' => 'Nombre'],
            ['key' => 'meta', 'label' => 'Meta'],
        ];
        $html = Blade::render(
            '<x-data.table :rows="$rows" :columns="$columns" :traceable="false" />',
            compact('rows', 'columns')
        );

        $this->assertStringContainsString('Nombre', $html);
        $this->assertStringContainsString('Meta', $html);
        $this->assertStringContainsString('X', $html);
        $this->assertStringContainsString('10', $html);
    }

    #[Test]
    public function renderiza_empty_state_si_rows_vacio(): void
    {
        $html = Blade::render(
            '<x-data.table :rows="collect()" :columns="[]" empty-message="Sin datos" :traceable="false" />'
        );

        $this->assertStringContainsString('Sin datos', $html);
    }

    #[Test]
    public function renderiza_search_input_cuando_search_bound(): void
    {
        $rows = collect();
        $html = Blade::render(
            '<x-data.table :rows="$rows" :columns="[]" search="foo" search-placeholder="Buscar..." :traceable="false" />',
            compact('rows')
        );

        $this->assertStringContainsString('Buscar...', $html);
        $this->assertStringContainsString('foo', $html);
    }

    #[Test]
    public function renderiza_selector_per_page_con_opciones(): void
    {
        $rows = collect();
        $html = Blade::render(
            '<x-data.table :rows="$rows" :columns="[]" :per-page="25" :traceable="false" />',
            compact('rows')
        );

        $this->assertStringContainsString('10', $html);
        $this->assertStringContainsString('25', $html);
        $this->assertStringContainsString('50', $html);
        $this->assertStringContainsString('Todas', $html);
    }
}
