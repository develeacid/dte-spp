<?php

namespace App\Http\Controllers\Evaluation;

use App\Exports\Excel\PresupuestoCapituloExport;
use App\Http\Controllers\Controller;
use App\Models\ProgramaPresupuestario;
use App\Services\Presupuesto\PresupuestoCapituloReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PresupuestoCapituloXlsxController extends Controller
{
    public function download(Request $request, ProgramaPresupuestario $programa)
    {
        $ejercicio = $request->integer('ejercicio') ?: (int) now()->format('Y');
        $trimestre = $request->integer('trimestre') ?: (int) ceil((int) now()->format('n') / 3);

        $service = new PresupuestoCapituloReportService($programa, $ejercicio, $trimestre);

        $filename = sprintf(
            'presupuesto_ejercido_%s_%d_T%d.xlsx',
            $programa->clave,
            $ejercicio,
            $trimestre,
        );

        return Excel::download(new PresupuestoCapituloExport($service), $filename);
    }
}
