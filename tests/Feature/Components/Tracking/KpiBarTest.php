<?php

namespace Tests\Feature\Components\Tracking;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KpiBarTest extends TestCase
{
    #[Test]
    public function renderiza_4_kpis_con_labels_y_valores(): void
    {
        $stats = [
            ['label' => 'Total', 'value' => 25, 'color' => 'slate'],
            ['label' => 'Aprobados', 'value' => 10, 'color' => 'green'],
            ['label' => 'En proceso', 'value' => 8, 'color' => 'blue'],
            ['label' => 'Vencidos', 'value' => 7, 'color' => 'red'],
        ];

        $html = Blade::render('<x-tracking.kpi-bar :stats="$stats" />', ['stats' => $stats]);

        $this->assertStringContainsString('Total', $html);
        $this->assertStringContainsString('Aprobados', $html);
        $this->assertStringContainsString('En proceso', $html);
        $this->assertStringContainsString('Vencidos', $html);
        $this->assertStringContainsString('>25<', $html);
        $this->assertStringContainsString('>10<', $html);
        $this->assertStringContainsString('>8<', $html);
        $this->assertStringContainsString('>7<', $html);
        $this->assertStringContainsString('md:grid-cols-4', $html);
    }

    #[Test]
    public function aplica_clase_color_por_kpi(): void
    {
        $stats = [
            ['label' => 'A', 'value' => 1, 'color' => 'slate'],
            ['label' => 'B', 'value' => 2, 'color' => 'green'],
            ['label' => 'C', 'value' => 3, 'color' => 'blue'],
            ['label' => 'D', 'value' => 4, 'color' => 'red'],
            ['label' => 'E', 'value' => 5, 'color' => 'yellow'],
        ];

        $html = Blade::render('<x-tracking.kpi-bar :stats="$stats" />', ['stats' => $stats]);

        $this->assertStringContainsString('text-slate-900', $html);
        $this->assertStringContainsString('text-green-600', $html);
        $this->assertStringContainsString('text-blue-600', $html);
        $this->assertStringContainsString('text-red-600', $html);
        $this->assertStringContainsString('text-yellow-600', $html);
    }
}
