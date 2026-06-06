<?php

namespace Tests\Feature\Components\Filters;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SemaforoFilterTest extends TestCase
{
    #[Test]
    public function renderiza_placeholder_y_modelo(): void
    {
        $html = Blade::render('<x-filters.semaforo model="semaforoFiltro" :options="[]" />');

        $this->assertStringContainsString('Todos los semáforos', $html);
        $this->assertStringContainsString('wire:model.live="semaforoFiltro"', $html);
    }
}
