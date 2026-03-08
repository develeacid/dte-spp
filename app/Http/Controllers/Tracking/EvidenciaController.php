<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\Tracking\AvanceEvidencia;
use Illuminate\Support\Facades\Storage;

class EvidenciaController extends Controller
{
    public function download(AvanceEvidencia $evidencia)
    {
        $user = auth()->user();

        $esCapturdor = $evidencia->avance->capturado_por === $user->id;
        $tienePermiso = $user->hasPermissionTo('revisar_avance');

        if (! $esCapturdor && ! $tienePermiso) {
            abort(403, 'No tiene permiso para descargar esta evidencia.');
        }

        return Storage::disk('local')->download(
            $evidencia->ruta_archivo,
            $evidencia->nombre_archivo,
        );
    }
}
