<?php

namespace App\Http\Controllers;

use App\Models\Juridico\CatalogoOrdenamiento;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AyudaController extends Controller
{
    public function __invoke(): View
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
