<?php

namespace App\Exports\Pdf;

use App\Enums\EstadoAvance;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\PedObjetivoEstrategico;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use Barryvdh\DomPDF\Facade\Pdf;

class FmyePdfExport
{
    public function __construct(
        private ProgramaPresupuestario $programa,
        private int $ejercicioFiscal,
    ) {}

    public function generate(): string
    {
        $team = $this->programa->team;

        $niveles = $this->programa->mirNiveles()
            ->with([
                'indicadores' => fn ($q) => $q->where('activo_seguimiento', true),
                'indicadores.avances' => fn ($q) => $q->whereHas('metaPeriodo', fn ($mp) => $mp->where('ejercicio_fiscal', $this->ejercicioFiscal))
                    ->where('estado', EstadoAvance::APROBADO),
            ])
            ->orderByRaw("CASE tipo_nivel WHEN 'fin' THEN 1 WHEN 'proposito' THEN 2 WHEN 'componente' THEN 3 WHEN 'actividad' THEN 4 END")
            ->orderBy('orden')
            ->get();

        $alineacion = $this->obtenerAlineacion();

        $evaluacion = EvaluacionPrograma::where('programa_presupuestario_id', $this->programa->id)
            ->where('ejercicio_fiscal', $this->ejercicioFiscal)
            ->first();

        $semaforoHistorico = $this->obtenerSemaforoHistorico();

        $encabezado = config('evaluation.exports.encabezado');

        $pdf = Pdf::loadView('exports.pdf.fmye', [
            'programa' => $this->programa,
            'team' => $team,
            'ejercicioFiscal' => $this->ejercicioFiscal,
            'encabezado' => $encabezado,
            'generadoEn' => now()->format('d/m/Y H:i'),
            'niveles' => $niveles,
            'alineacion' => $alineacion,
            'evaluacion' => $evaluacion,
            'semaforoHistorico' => $semaforoHistorico,
            'titular' => $team->titular,
            'dependencia' => $team->name,
            'fecha' => now()->format('d/m/Y'),
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->output();
    }

    private function obtenerAlineacion(): array
    {
        $alineacion = [];

        $pedObjetivos = PedObjetivoEstrategico::whereHas('estrategias.lineasAccion.programasDerivadosObjetivos')
            ->with([
                'pndObjetivos.odsMetas',
                'tema.eje',
            ])
            ->get();

        foreach ($pedObjetivos as $pedObj) {
            $entry = [
                'ped' => "Eje {$pedObj->tema->eje->numero}: {$pedObj->tema->eje->nombre} → Obj. {$pedObj->clave}: {$pedObj->descripcion}",
                'pnd' => [],
                'ods' => [],
            ];

            foreach ($pedObj->pndObjetivos as $pndObj) {
                $entry['pnd'][] = "Obj. {$pndObj->clave}: {$pndObj->descripcion}";
                foreach ($pndObj->odsMetas as $odsMeta) {
                    $entry['ods'][] = "Meta {$odsMeta->clave}: {$odsMeta->descripcion}";
                }
            }

            $alineacion[] = $entry;
        }

        return $alineacion;
    }

    private function obtenerSemaforoHistorico(): array
    {
        $historico = [];

        for ($t = 1; $t <= 4; $t++) {
            $avances = Avance::whereHas('indicador.mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $this->programa->id))
                ->whereHas('metaPeriodo', fn ($q) => $q->where('ejercicio_fiscal', $this->ejercicioFiscal)->where('periodo', $t))
                ->where('estado', EstadoAvance::APROBADO)
                ->get();

            if ($avances->isEmpty()) {
                continue;
            }

            $historico["T{$t}"] = [
                'verde' => $avances->where('semaforo_calculado', 'verde')->count(),
                'amarillo' => $avances->where('semaforo_calculado', 'amarillo')->count(),
                'rojo' => $avances->where('semaforo_calculado', 'rojo')->count(),
                'rojo_alto' => $avances->where('semaforo_calculado', 'rojo_alto')->count(),
            ];
        }

        return $historico;
    }
}
