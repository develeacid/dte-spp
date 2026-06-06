<?php

namespace Tests\Feature\Components\Filters;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProgramaFilterTest extends TestCase
{
    #[Test]
    public function renderiza_placeholder_y_opciones(): void
    {
        $options = ['EDU-002' => 'Programa Educación', 'ISM-001' => 'Programa ISM'];
        $html = Blade::render(
            '<x-filters.programa model="programaFiltro" :options="$options" />',
            ['options' => $options]
        );

        $this->assertStringContainsString('Todos los programas', $html);
        $this->assertStringContainsString('EDU-002', $html);
        $this->assertStringContainsString('Programa Educación', $html);
        $this->assertStringContainsString('wire:model.live="programaFiltro"', $html);
    }
}
