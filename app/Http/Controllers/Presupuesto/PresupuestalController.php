<?php

namespace App\Http\Controllers\Presupuesto;

use App\Exports\Excel\CuentaPublicaExcelExport;
use App\Http\Controllers\Controller;
use App\Services\Presupuesto\CuentaPublicaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class PresupuestalController extends Controller
{
    public function exportarPdf(int $ejercicio, CuentaPublicaService $service)
    {
        $teamId = auth()->user()->currentTeam->id;
        $datos = $service->generarDatos($ejercicio, $teamId);
        $resumenEjes = $service->resumenPorEjePed($ejercicio);

        $pdf = Pdf::loadView('exports.pdf.cuenta-publica', [
            'datos' => $datos,
            'resumenEjes' => $resumenEjes,
            'ejercicio' => $ejercicio,
            'team' => auth()->user()->currentTeam,
        ])->setPaper('legal', 'landscape');

        return $pdf->download("cuenta-publica-{$ejercicio}.pdf");
    }

    public function exportarExcel(int $ejercicio, CuentaPublicaService $service)
    {
        $teamId = auth()->user()->currentTeam->id;
        $datos = $service->generarDatos($ejercicio, $teamId);
        $resumenEjes = $service->resumenPorEjePed($ejercicio);

        return Excel::download(
            new CuentaPublicaExcelExport($datos, $resumenEjes, $ejercicio),
            "cuenta-publica-{$ejercicio}.xlsx"
        );
    }
}
