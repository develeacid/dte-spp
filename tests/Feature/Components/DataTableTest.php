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
    public function render_closure_recibe_row_y_emite_html_sin_escapar(): void
    {
        $rows = collect([['nombre' => 'Alfa', 'estado' => 'ok']]);
        $columns = [
            ['key' => 'nombre', 'label' => 'Nombre'],
            [
                'key' => 'estado',
                'label' => 'Estado',
                'render' => fn ($row) => '<span class="badge-ok">'.$row['estado'].'</span>',
            ],
        ];

        $html = Blade::render(
            '<x-data.table :rows="$rows" :columns="$columns" :traceable="false" />',
            compact('rows', 'columns')
        );

        $this->assertStringContainsString('Alfa', $html);
        $this->assertStringContainsString('<span class="badge-ok">ok</span>', $html);
    }

    #[Test]
    public function columna_sin_render_usa_data_get_normal(): void
    {
        $rows = collect([['meta' => 42]]);
        $columns = [
            ['key' => 'meta', 'label' => 'Meta'],
        ];

        $html = Blade::render(
            '<x-data.table :rows="$rows" :columns="$columns" :traceable="false" />',
            compact('rows', 'columns')
        );

        $this->assertStringContainsString('42', $html);
    }

    #[Test]
    public function aplica_row_class_closure_a_cada_fila(): void
    {
        $rows = collect([
            (object) ['nombre' => 'X', 'estado' => 'vencido'],
            (object) ['nombre' => 'Y', 'estado' => 'ok'],
        ]);
        $columns = [['key' => 'nombre', 'label' => 'Nombre']];
        $rowClass = fn ($row) => $row->estado === 'vencido' ? 'bg-red-50' : '';
        $html = Blade::render(
            '<x-data.table :rows="$rows" :columns="$columns" :row-class="$rowClass" :traceable="false" />',
            compact('rows', 'columns', 'rowClass')
        );
        $this->assertStringContainsString('bg-red-50', $html);
    }
}
