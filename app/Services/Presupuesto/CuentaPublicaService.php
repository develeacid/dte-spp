<?php

namespace App\Services\Presupuesto;

use App\Models\Juridico\ValidacionJuridicaPrograma;
use App\Models\ProgramaPresupuestario;
use Illuminate\Support\Collection;

class CuentaPublicaService
{
    public function __construct(
        private PresupuestoResumenService $resumenService,
        private SemaforoFinancieroService $semaforoService,
    ) {}

    /**
     * Datos para el reporte cruzado: físico + financiero + alineación.
     */
    public function generarDatos(int $ejercicio, ?int $teamId = null): array
    {
        $query = ProgramaPresupuestario::ejercicio($ejercicio)
            ->with([
                'partidasPresupuestales' => fn ($q) => $q->with(['avancesFinancieros', 'metasGasto']),
                'mirNiveles' => fn ($q) => $q->whereIn('tipo_nivel', ['fin', 'proposito'])
                    ->with(['pedObjetivoEstrategico.tema.eje', 'indicadores']),
            ]);

        if ($teamId) {
            $query->paraTeam($teamId);
        }

        $programas = $query->orderBy('clave')->get();

        $datos = [];

        foreach ($programas as $programa) {
            $resumen = $this->resumenService->resumenPrograma($programa->id, $ejercicio);

            // Obtener último trimestre con datos
            $ultimoTrimestre = $programa->partidasPresupuestales
                ->flatMap(fn ($p) => $p->avancesFinancieros->pluck('trimestre'))
                ->max() ?? 1;

            $semaforo = $this->semaforoService->combinado($programa->id, $ejercicio, $ultimoTrimestre);
            $eficiencia = $this->resumenService->indiceEficiencia($programa->id, $ejercicio, $ultimoTrimestre);
            $alineacion = $this->obtenerAlineacion($programa);

            $datos[] = [
                'programa' => $programa,
                'financiero' => $resumen,
                'semaforo' => $semaforo,
                'eficiencia' => $eficiencia,
                'alineacion' => $alineacion,
                'sustento_legal' => $this->estadoJuridicoPrograma($programa->id),
            ];
        }

        return $datos;
    }

    /**
     * Resumen por eje PED: agrupa programas bajo cada eje estratégico.
     */
    public function resumenPorEjePed(int $ejercicio): Collection
    {
        $datos = $this->generarDatos($ejercicio);
        $porEje = [];

        foreach ($datos as $item) {
            $ejeNombre = $item['alineacion']['ped_eje'] ?? 'Sin alineación';

            if (! isset($porEje[$ejeNombre])) {
                $porEje[$ejeNombre] = [
                    'eje' => $ejeNombre,
                    'programas' => [],
                    'total_aprobado' => 0,
                    'total_ejercido' => 0,
                ];
            }

            $porEje[$ejeNombre]['programas'][] = $item;
            $porEje[$ejeNombre]['total_aprobado'] += $item['financiero']->efectivo;
            $porEje[$ejeNombre]['total_ejercido'] += $item['financiero']->pagado;
        }

        return collect($porEje)->map(function ($eje) {
            $eje['pct_ejercido'] = $eje['total_aprobado'] > 0
                ? round(($eje['total_ejercido'] / $eje['total_aprobado']) * 100, 2)
                : 0;

            return (object) $eje;
        })->values();
    }

    /**
     * Estado jurídico de un programa para el reporte de Cuenta Pública.
     */
    public function estadoJuridicoPrograma(int $programaId): array
    {
        $programa = ProgramaPresupuestario::with(['validacionJuridica', 'sustentosLegales'])->find($programaId);

        if (! $programa) {
            return ['estado' => 'Sin registro', 'fundamentos' => []];
        }

        return [
            'estado' => $programa->validacionJuridica?->estado->label() ?? 'Sin registro',
            'fundamentos' => $programa->sustentosLegales->map(fn ($s) => [
                'tipo' => $s->tipo->label(),
                'cita' => $s->cita_completa,
                'nivel' => $s->nivel_jerarquia->label(),
            ])->toArray(),
        ];
    }

    /**
     * Obtener cadena de alineación estratégica de un programa.
     */
    private function obtenerAlineacion(ProgramaPresupuestario $programa): array
    {
        $alineacion = [
            'ped_eje' => null,
            'ped_tema' => null,
            'ped_objetivo' => null,
            'ped_linea_accion' => null,
            'pnd_objetivos' => [],
            'ods_metas' => [],
        ];

        // Buscar en niveles MIR (fin o propósito) la alineación PED
        $nivelConPed = $programa->mirNiveles
            ->first(fn ($n) => $n->pedObjetivoEstrategico !== null);

        if (! $nivelConPed || ! $nivelConPed->pedObjetivoEstrategico) {
            return $alineacion;
        }

        $objetivo = $nivelConPed->pedObjetivoEstrategico;
        $tema = $objetivo->tema;
        $eje = $tema?->eje;

        $alineacion['ped_eje'] = $eje?->nombre;
        $alineacion['ped_tema'] = $tema?->nombre;
        $alineacion['ped_objetivo'] = $objetivo->nombre;

        // PND via pivot alineacion_ped_pnd
        $pndObjetivos = $objetivo->pndObjetivos ?? collect();
        $alineacion['pnd_objetivos'] = $pndObjetivos->map(fn ($o) => $o->nombre)->toArray();

        // ODS via pivot alineacion_pnd_ods
        $odsMetas = $pndObjetivos->flatMap(fn ($o) => $o->odsMetas ?? collect());
        $alineacion['ods_metas'] = $odsMetas->map(fn ($m) => $m->nombre ?? $m->descripcion)->unique()->values()->toArray();

        return $alineacion;
    }
}
