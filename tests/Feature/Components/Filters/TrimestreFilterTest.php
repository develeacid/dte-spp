<?php

namespace Tests\Feature\Components\Filters;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrimestreFilterTest extends TestCase
{
    #[Test]
    public function renderiza_t1_a_t4_fijos(): void
    {
        $html = Blade::render('<x-filters.trimestre model="trimestreFiltro" />');

        $this->assertStringContainsString('T1', $html);
        $this->assertStringContainsString('T2', $html);
        $this->assertStringContainsString('T3', $html);
        $this->assertStringContainsString('T4', $html);
        $this->assertStringContainsString('Todos los trimestres', $html);
    }
}
