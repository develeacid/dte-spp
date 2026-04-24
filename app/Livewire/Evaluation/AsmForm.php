<?php

namespace App\Livewire\Evaluation;

use App\Models\Evaluation\Asm;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AsmForm extends Component
{
    public ?Asm $asm = null;

    public function mount(?Asm $asm = null): void
    {
        $this->asm = $asm;
    }

    public function render()
    {
        return view('livewire.evaluation.asm.form');
    }
}
