<?php

namespace App\Services\Juridico;

use App\Models\Juridico\DocumentoNormativo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoNormativoService
{
    /**
     * Almacena un documento PDF en storage privado.
     */
    public function almacenar(UploadedFile $archivo, int $programaId, array $metadata): DocumentoNormativo
    {
        $storagePath = config('juridico.storage_path', 'private/juridico');
        $path = $archivo->store("{$storagePath}/{$programaId}", 'local');

        $hash = hash_file('sha256', $archivo->getRealPath());

        return DocumentoNormativo::create(array_merge($metadata, [
            'programa_presupuestario_id' => $programaId,
            'archivo_path' => $path,
            'archivo_hash' => $hash,
            'archivo_size' => $archivo->getSize(),
        ]));
    }

    /**
     * Verifica un documento (marca como verificado por el Analista Jurídico).
     */
    public function verificar(int $documentoId, int $verificadorId): DocumentoNormativo
    {
        $documento = DocumentoNormativo::findOrFail($documentoId);

        $documento->update([
            'verificado' => true,
            'verificado_por' => $verificadorId,
            'verificado_at' => now(),
        ]);

        return $documento;
    }

    /**
     * Descarga controlada de documento (verifica permisos).
     */
    public function descargar(int $documentoId): StreamedResponse
    {
        $documento = DocumentoNormativo::findOrFail($documentoId);

        abort_unless(Storage::disk('local')->exists($documento->archivo_path), 404);

        return Storage::disk('local')->download(
            $documento->archivo_path,
            $documento->nombre.'.pdf'
        );
    }
}
