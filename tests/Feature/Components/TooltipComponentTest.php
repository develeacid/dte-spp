<?php

namespace Tests\Feature\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TooltipComponentTest extends TestCase
{
    #[Test]
    public function tooltip_renders_with_text(): void
    {
        $view = $this->blade(
            '<x-ui.tooltip text="Texto de ayuda"><span>Trigger</span></x-ui.tooltip>'
        );

        $view->assertSee('Texto de ayuda');
        $view->assertSee('Trigger');
    }

    #[Test]
    public function tooltip_renders_with_top_position(): void
    {
        $view = $this->blade(
            '<x-ui.tooltip text="Help" position="top"><span>T</span></x-ui.tooltip>'
        );

        $view->assertSee('bottom-full');
    }

    #[Test]
    public function tooltip_renders_with_right_position(): void
    {
        $view = $this->blade(
            '<x-ui.tooltip text="Help" position="right"><span>T</span></x-ui.tooltip>'
        );

        $view->assertSee('left-full');
    }

    #[Test]
    public function tooltip_renders_with_custom_max_width(): void
    {
        $view = $this->blade(
            '<x-ui.tooltip text="Help" maxWidth="max-w-md"><span>T</span></x-ui.tooltip>'
        );

        $view->assertSee('max-w-md');
    }
}
