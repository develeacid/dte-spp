<?php

namespace App\Livewire\Evaluation;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AsmIndex extends Component
{
    public ?int $programaId = null;

    public function mount(?int $programaId = null): void
    {
        $this->programaId = $programaId;
    }

    public function render()
    {
        return view('livewire.evaluation.asm.index');
    }
}
