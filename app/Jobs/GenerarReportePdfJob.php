<?php

namespace App\Jobs;

use App\Exports\Pdf\AvanceTrimestralPdfExport;
use App\Exports\Pdf\EvaluacionAnualPdfExport;
use App\Exports\Pdf\MirPdfExport;
use App\Exports\Pdf\TransversalPdfExport;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Notifications\ReporteListoNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerarReportePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tipo,
        public array $parametros,
        public int $userId,
    ) {}

    public function handle(): void
    {
        $contenido = match ($this->tipo) {
            'mir' => $this->generarMir(),
            'avance-trimestral' => $this->generarAvanceTrimestral(),
            'evaluacion-anual' => $this->generarEvaluacionAnual(),
            'transversal' => $this->generarTransversal(),
            default => throw new \InvalidArgumentException("Tipo de reporte no soportado: {$this->tipo}"),
        };

        $disk = config('evaluation.exports.storage_disk');
        $path = config('evaluation.exports.storage_path');
        $filename = "{$this->tipo}-" . now()->format('Ymd-His') . '-' . uniqid() . '.pdf';
        $fullPath = "{$path}/{$filename}";

        Storage::disk($disk)->put($fullPath, $contenido);

        $user = User::find($this->userId);
        $user?->notify(new ReporteListoNotification($this->tipo, $filename));
    }

    private function generarMir(): string
    {
        $programa = ProgramaPresupuestario::findOrFail($this->parametros['programa_id']);

        return (new MirPdfExport($programa, $this->parametros['ejercicio_fiscal'] ?? (int) date('Y')))->generate();
    }

    private function generarAvanceTrimestral(): string
    {
        $programa = ProgramaPresupuestario::findOrFail($this->parametros['programa_id']);

        return (new AvanceTrimestralPdfExport(
            $programa,
            $this->parametros['ejercicio_fiscal'] ?? (int) date('Y'),
            $this->parametros['trimestre'] ?? 1,
            User::find($this->userId),
        ))->generate();
    }

    private function generarEvaluacionAnual(): string
    {
        $evaluacion = EvaluacionPrograma::findOrFail($this->parametros['evaluacion_id']);

        return (new EvaluacionAnualPdfExport($evaluacion))->generate();
    }

    private function generarTransversal(): string
    {
        return (new TransversalPdfExport(
            $this->parametros['subtipo'] ?? 'anexo',
            $this->parametros['ejercicio_fiscal'] ?? (int) date('Y'),
        ))->generate();
    }
}
