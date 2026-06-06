<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PageToolbarTest extends TestCase
{
    #[Test]
    public function renderiza_slot_dentro_del_wrapper(): void
    {
        $html = Blade::render('<x-page.toolbar><span>contenido-slot</span></x-page.toolbar>');

        $this->assertStringContainsString('contenido-slot', $html);
        $this->assertStringContainsString('sticky', $html);
    }

    #[Test]
    public function acepta_clase_extra_via_attributes(): void
    {
        $html = Blade::render('<x-page.toolbar class="extra-class"><span>x</span></x-page.toolbar>');

        $this->assertStringContainsString('extra-class', $html);
    }
}
