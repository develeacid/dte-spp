<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\PubPrograma;

class PortalDownloadController extends Controller
{
    public function csv(string $codigo)
    {
        abort_unless($codigo === 'DS-01', 404);

        $filename = 'DS-01-programas-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ejercicio_fiscal', 'programa_clave', 'programa_nombre',
                'unidad_responsable', 'modalidad', 'activo',
            ]);
            PubPrograma::orderBy('ejercicio_fiscal')->orderBy('programa_clave')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [
                            $r->ejercicio_fiscal,
                            $r->programa_clave,
                            $r->programa_nombre,
                            $r->unidad_responsable,
                            $r->modalidad,
                            $r->activo ? '1' : '0',
                        ]);
                    }
                });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
