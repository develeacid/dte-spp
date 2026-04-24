<?php

namespace App\Http\Controllers\Evaluation;

use App\Http\Controllers\Controller;
use App\Models\Evaluation\Asm;
use Illuminate\Http\RedirectResponse;

class AsmController extends Controller
{
    public function destroy(Asm $asm): RedirectResponse
    {
        $asm->delete();

        return redirect()
            ->route('evaluation.asms.index')
            ->with('status', 'ASM eliminado.');
    }
}
