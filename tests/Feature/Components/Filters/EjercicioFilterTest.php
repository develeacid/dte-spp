<?php

namespace Tests\Feature\Components\Filters;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EjercicioFilterTest extends TestCase
{
    #[Test]
    public function renderiza_rango_desde_from_hasta_to(): void
    {
        $html = Blade::render('<x-filters.ejercicio model="ejercicioFiltro" :from="2024" :to="2026" />');

        $this->assertStringContainsString('Todos los ejercicios', $html);
        $this->assertStringContainsString('>2024<', $html);
        $this->assertStringContainsString('>2025<', $html);
        $this->assertStringContainsString('>2026<', $html);
    }
}
