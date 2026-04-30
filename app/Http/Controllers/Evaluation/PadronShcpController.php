<?php

namespace App\Http\Controllers\Evaluation;

use App\Exports\Excel\PadronShcpExcelExport;
use App\Http\Controllers\Controller;
use App\Models\ProgramaPresupuestario;
use App\Services\GeoBase\GeoBaseException;
use App\Services\Padron\PadronShcpExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PadronShcpController extends Controller
{
    public function __invoke(
        ProgramaPresupuestario $programa,
        Request $request,
        PadronShcpExportService $service,
    ): BinaryFileResponse|RedirectResponse {
        abort_unless($programa->hasGeoBaseLink(), 404);

        $periodo = $request->query('periodo');

        try {
            $rows = $service->build($programa, $periodo);
        } catch (GeoBaseException $e) {
            return back()->with('error', "Error al consultar GeoBase: {$e->getMessage()}");
        }

        return (new PadronShcpExcelExport($rows))->download($service->filename($programa, $periodo));
    }
}
