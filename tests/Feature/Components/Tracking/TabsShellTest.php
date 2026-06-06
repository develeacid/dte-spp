<?php

namespace Tests\Feature\Components\Tracking;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TabsShellTest extends TestCase
{
    #[Test]
    public function renderiza_solo_el_slot_del_tab_activo(): void
    {
        $html = Blade::render(<<<'BLADE'
<x-tracking.tabs-shell active="dashboard">
    <x-slot:dashboard>
        <div id="dash-content">CONTENIDO_DASH</div>
    </x-slot>
    <x-slot:tabla>
        <div id="tabla-content">CONTENIDO_TABLA</div>
    </x-slot>
</x-tracking.tabs-shell>
BLADE);

        $this->assertStringContainsString('CONTENIDO_DASH', $html);
        $this->assertStringNotContainsString('CONTENIDO_TABLA', $html);
    }

    #[Test]
    public function soporta_tabs_custom_solo_uno(): void
    {
        $html = Blade::render(<<<'BLADE'
<x-tracking.tabs-shell active="lista" :tabs="['lista' => 'Lista']">
    <x-slot:lista>
        <div>CONTENIDO_LISTA</div>
    </x-slot>
</x-tracking.tabs-shell>
BLADE);

        $this->assertStringContainsString('CONTENIDO_LISTA', $html);
        $this->assertStringContainsString('Lista', $html);
    }
}
