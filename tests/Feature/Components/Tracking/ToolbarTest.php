<?php

namespace Tests\Feature\Components\Tracking;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ToolbarTest extends TestCase
{
    #[Test]
    public function renderiza_3_familias_con_labels(): void
    {
        $html = Blade::render('<x-tracking.toolbar :programasOpciones="[]" :nivelesOpciones="[]" :estadosOpciones="[]" />');

        $this->assertStringContainsString('Ámbito', $html);
        $this->assertStringContainsString('Tiempo', $html);
        $this->assertStringContainsString('Vista', $html);
        $this->assertStringContainsString('Todo', $html);
        $this->assertStringContainsString('Rango', $html);
    }

    #[Test]
    public function slot_ambito_extra_se_inyecta_en_familia_ambito(): void
    {
        $html = Blade::render(<<<'BLADE'
<x-tracking.toolbar :programasOpciones="[]" :nivelesOpciones="[]" :estadosOpciones="[]">
    <x-slot:ambitoExtra>
        <span id="semaforo-extra-test">SEMAFORO_INYECTADO</span>
    </x-slot>
</x-tracking.toolbar>
BLADE);

        $this->assertStringContainsString('SEMAFORO_INYECTADO', $html);
        $this->assertStringContainsString('semaforo-extra-test', $html);
    }

    #[Test]
    public function oculta_select_orden_si_show_orden_false(): void
    {
        $htmlConOrden = Blade::render('<x-tracking.toolbar :programasOpciones="[]" :nivelesOpciones="[]" :estadosOpciones="[]" :showOrden="true" />');
        $this->assertStringContainsString('Orden: Indicador', $htmlConOrden);

        $htmlSinOrden = Blade::render('<x-tracking.toolbar :programasOpciones="[]" :nivelesOpciones="[]" :estadosOpciones="[]" :showOrden="false" />');
        $this->assertStringNotContainsString('Orden: Indicador', $htmlSinOrden);
    }
}
