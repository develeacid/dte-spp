<?php

namespace App\Livewire;

use App\Models\Juridico\CatalogoOrdenamiento;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ayuda')]
class Ayuda extends Component
{
    public function render()
    {
        $ordenamientos = CatalogoOrdenamiento::where('activo', true)
            ->orderBy('orden')
            ->get()
            ->groupBy(fn (CatalogoOrdenamiento $o) => $o->nivel_jerarquia->label());

        $glosarioHtml = Str::markdown(
            file_get_contents(resource_path('markdown/glosario-mir.md'))
        );

        return view('ayuda.index', compact('ordenamientos', 'glosarioHtml'));
    }
}
