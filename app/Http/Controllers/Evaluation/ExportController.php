<?php

namespace App\Http\Controllers\Evaluation;

use App\Exports\Excel\AvanceTrimestralExcelExport;
use App\Exports\Excel\EvaluacionAnualExcelExport;
use App\Exports\Excel\MirExcelExport;
use App\Exports\Excel\TransversalExcelExport;
use App\Exports\Pdf\AvanceTrimestralPdfExport;
use App\Exports\Pdf\EvaluacionAnualPdfExport;
use App\Exports\Pdf\FichaTecnicaPdfExport;
use App\Exports\Pdf\FmyePdfExport;
use App\Exports\Pdf\MirPdfExport;
use App\Exports\Pdf\TransversalPdfExport;
use App\Http\Controllers\Controller;
use App\Jobs\GenerarReporteExcelJob;
use App\Jobs\GenerarReportePdfJob;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Mml\Indicador;
use App\Models\ProgramaPresupuestario;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function pdf(Request $request, string $tipo, ?int $id = null): Response
    {
        $contenido = match ($tipo) {
            'mir' => $this->mirPdf($id),
            'ficha-tecnica' => $this->fichaTecnicaPdf($id),
            'avance-trimestral' => $this->avanceTrimestralPdf($request, $id),
            'evaluacion-anual' => $this->evaluacionAnualPdf($id),
            'transversal' => $this->transversalPdf($request),
            'fmye' => $this->fmyePdf($request, $id),
            default => abort(404, 'Tipo de reporte no encontrado'),
        };

        $filename = "{$tipo}-" . now()->format('Ymd-His') . '.pdf';

        return new Response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function excel(Request $request, string $tipo, ?int $id = null): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = "{$tipo}-" . now()->format('Ymd-His') . '.xlsx';

        $export = match ($tipo) {
            'mir' => new MirExcelExport(
                ProgramaPresupuestario::findOrFail($id),
                (int) $request->input('ejercicio_fiscal', date('Y')),
            ),
            'avance-trimestral' => new AvanceTrimestralExcelExport(
                ProgramaPresupuestario::findOrFail($id),
                (int) $request->input('ejercicio_fiscal', date('Y')),
                (int) $request->input('trimestre', 1),
            ),
            'evaluacion-anual' => new EvaluacionAnualExcelExport(
                EvaluacionPrograma::findOrFail($id),
            ),
            'transversal' => new TransversalExcelExport(
                $request->input('subtipo', 'anexo'),
                (int) $request->input('ejercicio_fiscal', date('Y')),
            ),
            default => abort(404, 'Tipo de reporte no encontrado'),
        };

        return Excel::download($export, $filename);
    }

    public function async(Request $request, string $formato, string $tipo): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'parametros' => 'required|array',
        ]);

        $parametros = $request->input('parametros');
        $userId = $request->user()->id;

        match ($formato) {
            'pdf' => GenerarReportePdfJob::dispatch($tipo, $parametros, $userId),
            'excel' => GenerarReporteExcelJob::dispatch($tipo, $parametros, $userId),
            default => abort(400, 'Formato no soportado'),
        };

        return response()->json([
            'mensaje' => 'El reporte se está generando. Recibirás una notificación cuando esté listo.',
        ]);
    }

    public function descargar(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path = config('evaluation.exports.storage_path') . '/' . $filename;
        $disk = config('evaluation.exports.storage_disk');

        abort_unless(Storage::disk($disk)->exists($path), 404, 'Archivo no encontrado');

        return Storage::disk($disk)->download($path, $filename);
    }

    private function mirPdf(?int $id): string
    {
        $programa = ProgramaPresupuestario::findOrFail($id);

        return (new MirPdfExport($programa, (int) date('Y')))->generate();
    }

    private function fichaTecnicaPdf(?int $id): string
    {
        $indicador = Indicador::findOrFail($id);

        return (new FichaTecnicaPdfExport($indicador))->generate();
    }

    private function avanceTrimestralPdf(Request $request, ?int $id): string
    {
        $programa = ProgramaPresupuestario::findOrFail($id);

        return (new AvanceTrimestralPdfExport(
            $programa,
            (int) $request->input('ejercicio_fiscal', date('Y')),
            (int) $request->input('trimestre', 1),
        ))->generate();
    }

    private function evaluacionAnualPdf(?int $id): string
    {
        $evaluacion = EvaluacionPrograma::findOrFail($id);

        return (new EvaluacionAnualPdfExport($evaluacion))->generate();
    }

    private function fmyePdf(Request $request, ?int $id): string
    {
        $programa = ProgramaPresupuestario::findOrFail($id);

        return (new FmyePdfExport(
            $programa,
            (int) $request->input('ejercicio_fiscal', date('Y')),
        ))->generate();
    }

    private function transversalPdf(Request $request): string
    {
        return (new TransversalPdfExport(
            $request->input('subtipo', 'anexo'),
            (int) $request->input('ejercicio_fiscal', date('Y')),
        ))->generate();
    }
}
