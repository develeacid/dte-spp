<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageTabsTest extends TestCase
{
    #[Test]
    public function renderiza_los_tabs_definidos(): void
    {
        $tabs = ['dashboard' => 'Dashboard', 'tabla' => 'Tabla'];
        $html = Blade::render(
            '<x-page.tabs :tabs="$tabs" active="dashboard" />',
            ['tabs' => $tabs]
        );

        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('Tabla', $html);
    }

    #[Test]
    public function marca_el_tab_activo_con_color_indigo(): void
    {
        $tabs = ['dashboard' => 'Dashboard', 'tabla' => 'Tabla'];
        $html = Blade::render(
            '<x-page.tabs :tabs="$tabs" active="dashboard" />',
            ['tabs' => $tabs]
        );

        $this->assertStringContainsString('border-indigo-500', $html);
        $this->assertStringContainsString('text-indigo-600', $html);
    }

    #[Test]
    public function wire_click_apunta_al_modelo_definido(): void
    {
        $tabs = ['uno' => 'Uno'];
        $html = Blade::render(
            '<x-page.tabs :tabs="$tabs" active="uno" model="miTab" />',
            ['tabs' => $tabs]
        );

        $this->assertStringContainsString("wire:click=\"\$set('miTab',", $html);
    }
}
