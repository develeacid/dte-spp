<?php

namespace Tests\Feature\Components\Filters;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EstadoFilterTest extends TestCase
{
    #[Test]
    public function renderiza_placeholder_y_modelo(): void
    {
        $html = Blade::render('<x-filters.estado model="estadoFiltro" :options="[]" />');

        $this->assertStringContainsString('Todos los estados', $html);
        $this->assertStringContainsString('wire:model.live="estadoFiltro"', $html);
    }
}
