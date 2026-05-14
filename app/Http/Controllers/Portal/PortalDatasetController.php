<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\PubDatasetCatalogo;
use App\Models\Portal\PubPrograma;

class PortalDatasetController extends Controller
{
    public function show(string $codigo)
    {
        abort_unless($codigo === 'DS-01', 404);

        $dataset = PubDatasetCatalogo::where('codigo', 'DS-01')->firstOrFail();
        $programas = PubPrograma::orderBy('ejercicio_fiscal', 'desc')
            ->orderBy('programa_clave')
            ->paginate(25);

        return view('portal.dataset.programas', compact('dataset', 'programas'));
    }
}
