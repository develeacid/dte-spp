<?php

namespace App\Jobs;

use App\Exports\Excel\AvanceTrimestralExcelExport;
use App\Exports\Excel\EvaluacionAnualExcelExport;
use App\Exports\Excel\MirExcelExport;
use App\Exports\Excel\TransversalExcelExport;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Notifications\ReporteListoNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class GenerarReporteExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $tipo,
        public array $parametros,
        public int $userId,
    ) {}

    public function handle(): void
    {
        $export = match ($this->tipo) {
            'mir' => $this->crearMirExport(),
            'avance-trimestral' => $this->crearAvanceTrimestralExport(),
            'evaluacion-anual' => $this->crearEvaluacionAnualExport(),
            'transversal' => $this->crearTransversalExport(),
            default => throw new \InvalidArgumentException("Tipo de reporte no soportado: {$this->tipo}"),
        };

        $path = config('evaluation.exports.storage_path');
        $filename = "{$this->tipo}-" . now()->format('Ymd-His') . '-' . uniqid() . '.xlsx';
        $fullPath = "{$path}/{$filename}";

        $disk = config('evaluation.exports.storage_disk');
        Excel::store($export, $fullPath, $disk);

        $user = User::find($this->userId);
        $user?->notify(new ReporteListoNotification($this->tipo, $filename));
    }

    private function crearMirExport(): MirExcelExport
    {
        $programa = ProgramaPresupuestario::findOrFail($this->parametros['programa_id']);

        return new MirExcelExport($programa, $this->parametros['ejercicio_fiscal'] ?? (int) date('Y'));
    }

    private function crearAvanceTrimestralExport(): AvanceTrimestralExcelExport
    {
        $programa = ProgramaPresupuestario::findOrFail($this->parametros['programa_id']);

        return new AvanceTrimestralExcelExport(
            $programa,
            $this->parametros['ejercicio_fiscal'] ?? (int) date('Y'),
            $this->parametros['trimestre'] ?? 1,
        );
    }

    private function crearEvaluacionAnualExport(): EvaluacionAnualExcelExport
    {
        $evaluacion = EvaluacionPrograma::findOrFail($this->parametros['evaluacion_id']);

        return new EvaluacionAnualExcelExport($evaluacion);
    }

    private function crearTransversalExport(): TransversalExcelExport
    {
        return new TransversalExcelExport(
            $this->parametros['subtipo'] ?? 'anexo',
            $this->parametros['ejercicio_fiscal'] ?? (int) date('Y'),
        );
    }
}
