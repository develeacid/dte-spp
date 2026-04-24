<?php

namespace App\Livewire\Evaluation;

use App\Models\Evaluation\Asm;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AsmForm extends Component
{
    public ?Asm $asm = null;
    public AsmFormData $form;

    public function mount(?Asm $asm = null): void
    {
        if ($asm && $asm->exists) {
            $this->asm = $asm;
            $this->form->setFromModel($asm);
        }
    }

    public function save()
    {
        $data = $this->form->validate();

        if ($this->asm && $this->asm->exists) {
            $this->asm->update($data);
        } else {
            Asm::create($data);
        }

        session()->flash('status', $this->asm && $this->asm->exists ? 'ASM actualizado.' : 'ASM creado.');

        return redirect()->route('evaluation.asms.index');
    }

    public function render()
    {
        return view('livewire.evaluation.asm.form');
    }
}
