<?php

namespace App\Http\Controllers\Evaluation;

use App\Http\Controllers\Controller;
use App\Services\Evaluation\DatosAbiertosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatosAbiertosController extends Controller
{
    public function __construct(
        private readonly DatosAbiertosService $service,
    ) {}

    public function csv(int $ejercicio): Response
    {
        $contenido = $this->service->exportarCsv($ejercicio);

        return new Response($contenido, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"datos_{$ejercicio}.csv\"",
        ]);
    }

    public function json(int $ejercicio): JsonResponse
    {
        $data = $this->service->exportarJson($ejercicio);

        return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function diccionario(): Response
    {
        $contenido = $this->service->generarDiccionario();

        return new Response($contenido, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="diccionario_datos.csv"',
        ]);
    }

    public function zip(int $ejercicio): BinaryFileResponse
    {
        $path = $this->service->generarZip($ejercicio);

        return response()->download($path, "datos_abiertos_{$ejercicio}.zip")->deleteFileAfterSend();
    }
}
