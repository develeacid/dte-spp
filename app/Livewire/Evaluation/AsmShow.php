<?php

namespace App\Livewire\Evaluation;

use App\Models\Evaluation\Asm;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class AsmShow extends Component
{
    public Asm $asm;

    public function mount(Asm $asm): void
    {
        $this->asm = $asm->load(['programa', 'responsable', 'evaluacion']);
    }

    public function render()
    {
        $activities = Activity::where('log_name', 'asm')
            ->where('subject_id', $this->asm->id)
            ->latest()
            ->take(50)
            ->get();

        return view('livewire.evaluation.asm.show', [
            'activities' => $activities,
        ]);
    }
}
