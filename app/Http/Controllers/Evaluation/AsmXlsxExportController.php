<?php

namespace App\Http\Controllers\Evaluation;

use App\Exports\Excel\AsmExport;
use App\Http\Controllers\Controller;
use App\Services\Evaluation\AsmReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AsmXlsxExportController extends Controller
{
    public function download(Request $request)
    {
        $filters = [
            'programa_id' => $request->integer('programa') ?: null,
        ];

        $ejercicio = $request->integer('ejercicio') ?: (int) now()->format('Y');

        $service = new AsmReportService($filters);

        return Excel::download(
            new AsmExport($service),
            "asm_seguimiento_{$ejercicio}.xlsx",
        );
    }
}
