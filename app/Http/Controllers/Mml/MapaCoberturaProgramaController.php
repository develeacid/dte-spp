<?php

namespace App\Http\Controllers\Mml;

use App\Http\Controllers\Controller;
use App\Models\ProgramaPresupuestario;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MapaCoberturaProgramaController extends Controller
{
    public function __invoke(Request $request, ProgramaPresupuestario $programa): Response
    {
        // Happy path implementation comes in Task 3.
        return response('', 503);
    }
}
