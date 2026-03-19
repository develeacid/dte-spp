<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Tracking\CapturaAvance;
use Tests\TestCase;

class CapturaAvanceSyncTest extends TestCase
{
    public function test_sincronizar_variable_method_exists(): void
    {
        $this->assertTrue(method_exists(CapturaAvance::class, 'sincronizarVariable'));
    }
}
