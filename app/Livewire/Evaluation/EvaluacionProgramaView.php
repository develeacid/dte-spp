<?php

namespace App\Livewire\Evaluation;

use App\Enums\EstadoAvance;
use App\Enums\TipoNivelMir;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Mml\Indicador;
use App\Models\Tracking\Avance;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EvaluacionProgramaView extends Component
{
    public int $evaluacionId;

    public EvaluacionPrograma $evaluacionModel;

    public function mount($evaluacion): void
    {
        abort_unless(auth()->user()->can('exportar_reportes'), 403);

        $id = $evaluacion instanceof EvaluacionPrograma ? $evaluacion->id : (int) $evaluacion;
        $this->evaluacionId = $id;

        $this->evaluacionModel = EvaluacionPrograma::with([
            'programa.mirNiveles.indicadores.avances.metaPeriodo',
            'programa.mirNiveles.pedObjetivoEstrategico',
            'programa.mirNiveles.pedLineaAccion',
        ])->findOrFail($id);
    }

    public function resumenEjecutivo(): array
    {
        $programa = $this->evaluacionModel->programa;
        $finNivel = $programa->mirNiveles
            ->firstWhere('tipo_nivel', TipoNivelMir::FIN);

        return [
            'nombre' => $programa->nombre,
            'clave' => $programa->clave,
            'ejercicio' => $this->evaluacionModel->ejercicio_fiscal,
            'indice' => $this->evaluacionModel->indice_eficacia,
            'indicadores_evaluados' => $this->evaluacionModel->indicadores_evaluados,
            'indicadores_no_evaluados' => $this->evaluacionModel->indicadores_no_evaluados,
            'ped_objetivo' => $finNivel?->pedObjetivoEstrategico?->descripcion,
            'ped_linea' => $finNivel?->pedLineaAccion?->descripcion,
        ];
    }

    public function tableroSemaforos(): array
    {
        $conteo = $this->evaluacionModel->conteo_semaforos ?? [
            'verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'sin_dato' => 0,
        ];

        $desglose = $this->evaluacionModel->desglose_niveles ?? [];

        return [
            'conteo' => $conteo,
            'desglose' => $desglose,
        ];
    }

    public function comparativa(): array
    {
        $ejercicioActual = $this->evaluacionModel->ejercicio_fiscal;
        $ejercicioAnterior = $ejercicioActual - 1;

        $evaluacionAnterior = EvaluacionPrograma::where('programa_presupuestario_id', $this->evaluacionModel->programa_presupuestario_id)
            ->where('ejercicio_fiscal', $ejercicioAnterior)
            ->first();

        if (! $evaluacionAnterior) {
            return ['disponible' => false, 'filas' => [], 'indice_anterior' => null, 'indice_actual' => $this->evaluacionModel->indice_eficacia];
        }

        $programa = $this->evaluacionModel->programa;
        $indicadores = $programa->mirNiveles
            ->flatMap(fn ($nivel) => $nivel->indicadores);

        $filas = [];

        foreach ($indicadores as $indicador) {
            $avanceActual = $this->ultimoAvanceAprobado($indicador, $ejercicioActual);
            $avanceAnterior = $this->ultimoAvanceAprobado($indicador, $ejercicioAnterior);

            $resultadoActual = $avanceActual?->resultado;
            $resultadoAnterior = $avanceAnterior?->resultado;

            $tendencia = '=';
            if ($resultadoActual !== null && $resultadoAnterior !== null) {
                if ((float) $resultadoActual > (float) $resultadoAnterior) {
                    $tendencia = "\u{2191}";
                } elseif ((float) $resultadoActual < (float) $resultadoAnterior) {
                    $tendencia = "\u{2193}";
                }
            }

            $filas[] = [
                'indicador' => $indicador->nombre,
                'nivel' => $indicador->mirNivel->tipo_nivel,
                'resultado_anterior' => $resultadoAnterior,
                'semaforo_anterior' => $avanceAnterior?->semaforo_calculado,
                'resultado_actual' => $resultadoActual,
                'semaforo_actual' => $avanceActual?->semaforo_calculado,
                'tendencia' => $tendencia,
            ];
        }

        return [
            'disponible' => true,
            'filas' => $filas,
            'indice_anterior' => $evaluacionAnterior->indice_eficacia,
            'indice_actual' => $this->evaluacionModel->indice_eficacia,
        ];
    }

    public function desviaciones(): array
    {
        $ejercicio = $this->evaluacionModel->ejercicio_fiscal;
        $programa = $this->evaluacionModel->programa;

        $indicadores = $programa->mirNiveles
            ->flatMap(fn ($nivel) => $nivel->indicadores);

        $desviaciones = [];

        foreach ($indicadores as $indicador) {
            $avance = $this->ultimoAvanceAprobado($indicador, $ejercicio);

            if (! $avance || ! in_array($avance->semaforo_calculado, ['amarillo', 'rojo'])) {
                continue;
            }

            $desviaciones[] = [
                'indicador' => $indicador->nombre,
                'nivel' => $indicador->mirNivel->tipo_nivel,
                'semaforo' => $avance->semaforo_calculado,
                'resultado' => $avance->resultado,
                'meta' => $indicador->meta,
                'justificacion' => $avance->justificacion_final,
                'supuestos' => $indicador->mirNivel->supuestos,
            ];
        }

        return $desviaciones;
    }

    public function indicadoresCronicos(): array
    {
        $ejercicioActual = $this->evaluacionModel->ejercicio_fiscal;
        $ejercicios = [$ejercicioActual, $ejercicioActual - 1, $ejercicioActual - 2];

        $programa = $this->evaluacionModel->programa;
        $indicadores = $programa->mirNiveles
            ->flatMap(fn ($nivel) => $nivel->indicadores);

        $cronicos = [];

        foreach ($indicadores as $indicador) {
            $conteoRojo = 0;

            foreach ($ejercicios as $ej) {
                $avance = $this->ultimoAvanceAprobado($indicador, $ej);
                if ($avance && $avance->semaforo_calculado === 'rojo') {
                    $conteoRojo++;
                }
            }

            if ($conteoRojo >= 2) {
                $cronicos[] = [
                    'indicador' => $indicador->nombre,
                    'nivel' => $indicador->mirNivel->tipo_nivel,
                    'ejercicios_rojo' => $conteoRojo,
                ];
            }
        }

        return $cronicos;
    }

    public function render()
    {
        return view('livewire.evaluation.evaluacion-programa', [
            'resumen' => $this->resumenEjecutivo(),
            'tablero' => $this->tableroSemaforos(),
            'comparativa' => $this->comparativa(),
            'desviaciones' => $this->desviaciones(),
            'cronicos' => $this->indicadoresCronicos(),
        ]);
    }

    private function ultimoAvanceAprobado(Indicador $indicador, int $ejercicio): ?Avance
    {
        return $indicador->avances
            ->filter(fn ($a) => $a->estado === EstadoAvance::APROBADO && $a->metaPeriodo?->ejercicio_fiscal === $ejercicio)
            ->sortByDesc('id')
            ->first();
    }
}
