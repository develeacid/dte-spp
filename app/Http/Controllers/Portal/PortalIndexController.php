<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\PubDatasetCatalogo;

class PortalIndexController extends Controller
{
    public function index()
    {
        $catalogo = PubDatasetCatalogo::orderBy('codigo')->get();

        return view('portal.index', compact('catalogo'));
    }
}
