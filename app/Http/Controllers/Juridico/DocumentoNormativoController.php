<?php

namespace App\Http\Controllers\Juridico;

use App\Http\Controllers\Controller;
use App\Models\Juridico\DocumentoNormativo;
use App\Services\Juridico\DocumentoNormativoService;

class DocumentoNormativoController extends Controller
{
    public function download(DocumentoNormativo $documento)
    {
        $this->authorize('ver_sustento_legal');

        abort_unless(
            $documento->team_id === auth()->user()->currentTeam->id,
            403
        );

        return app(DocumentoNormativoService::class)->descargar($documento->id);
    }
}
