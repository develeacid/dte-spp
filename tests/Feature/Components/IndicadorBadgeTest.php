<?php

namespace Tests\Feature\Components;

use App\Enums\TipoNivelMir;
use App\Support\Mml\Trazabilidad;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndicadorBadgeTest extends TestCase
{
    #[Test]
    public function renderiza_clave_en_mono(): void
    {
        $t = new Trazabilidad('EDU-002', TipoNivelMir::ACTIVIDAD, 1, 2);
        $html = Blade::render('<x-data.indicador-badge :trazabilidad="$t" />', ['t' => $t]);

        $this->assertStringContainsString('EDU-002-c1-a2', $html);
        $this->assertStringContainsString('font-mono', $html);
    }

    #[Test]
    public function incluye_tooltip_con_nivel_legible(): void
    {
        $t = new Trazabilidad('ISM-001', TipoNivelMir::COMPONENTE, 3, null);
        $html = Blade::render('<x-data.indicador-badge :trazabilidad="$t" />', ['t' => $t]);

        $this->assertStringContainsString('Componente 3', $html);
        $this->assertStringContainsString('ISM-001', $html);
    }
}
