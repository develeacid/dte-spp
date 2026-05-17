<?php

namespace Tests\Feature\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpLabelComponentTest extends TestCase
{
    #[Test]
    public function help_label_renders_with_glossary_key(): void
    {
        $view = $this->blade(
            '<x-ui.help-label glossary="fin">Fin</x-ui.help-label>'
        );

        $view->assertSee('Fin');
        $view->assertSee(config('glosario.fin'));
    }

    #[Test]
    public function help_label_renders_with_custom_help_text(): void
    {
        $view = $this->blade(
            '<x-ui.help-label help="Custom help">Label</x-ui.help-label>'
        );

        $view->assertSee('Custom help');
    }

    #[Test]
    public function help_label_renders_without_tooltip_when_no_help(): void
    {
        $view = $this->blade(
            '<x-ui.help-label>Plain Label</x-ui.help-label>'
        );

        $view->assertSee('Plain Label');
        $view->assertDontSee('cursor-help');
    }

    #[Test]
    public function help_label_renders_for_attribute(): void
    {
        $view = $this->blade(
            '<x-ui.help-label for="campo1" glossary="fin">Fin</x-ui.help-label>'
        );

        $view->assertSee('for="campo1"', false);
    }
}
