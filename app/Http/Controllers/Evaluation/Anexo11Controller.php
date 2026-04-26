<?php

namespace App\Http\Controllers\Evaluation;

use App\Exports\Excel\Anexo11ExcelExport;
use App\Http\Controllers\Controller;
use App\Models\ProgramaPresupuestario;
use App\Services\Evaluation\Anexo11ExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Anexo11Controller extends Controller
{
    public function __invoke(
        ProgramaPresupuestario $programa,
        Anexo11ExportService $service,
    ): BinaryFileResponse {
        abort_unless($programa->hasGeoBaseLink(), 404);

        $data = $service->build($programa->id, $programa->nombre);
        $filename = sprintf(
            'anexo-11-%s-%s.xlsx',
            $programa->clave ?: $programa->id,
            now()->format('Ymd'),
        );

        return (new Anexo11ExcelExport($data))->download($filename);
    }
}
