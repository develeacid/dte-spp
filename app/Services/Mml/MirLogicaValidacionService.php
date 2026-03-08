<?php

namespace App\Services\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoNivelMir;
use App\Models\ProgramaPresupuestario;

class MirLogicaValidacionService
{
    public function __construct(
        private LlmServiceInterface $llm,
    ) {}

    public function validarCompleta(ProgramaPresupuestario $programa): array
    {
        $vertical = $this->validarVertical($programa);
        $horizontal = $this->validarHorizontal($programa);

        return [
            'vertical' => $vertical,
            'horizontal' => $horizontal,
            'hallazgos' => array_merge($vertical['hallazgos'] ?? [], $horizontal['hallazgos'] ?? []),
        ];
    }

    private function validarVertical(ProgramaPresupuestario $programa): array
    {
        $niveles = $programa->mirNiveles()
            ->with('actividades')
            ->orderBy('tipo_nivel')
            ->orderBy('orden')
            ->get();

        $fin = $niveles->firstWhere('tipo_nivel', TipoNivelMir::FIN);
        $proposito = $niveles->firstWhere('tipo_nivel', TipoNivelMir::PROPOSITO);
        $componentes = $niveles->where('tipo_nivel', TipoNivelMir::COMPONENTE);

        $actividades = [];
        foreach ($componentes as $comp) {
            foreach ($comp->actividades as $act) {
                $actividades[] = [
                    'componente' => $comp->resumen_narrativo ?? 'Sin resumen',
                    'descripcion' => $act->resumen_narrativo ?? 'Sin resumen',
                ];
            }
        }

        $promptText = view('prompts.mir.validar-logica-vertical', [
            'fin' => $fin?->resumen_narrativo ?? 'No definido',
            'proposito' => $proposito?->resumen_narrativo ?? 'No definido',
            'componentes' => $componentes->pluck('resumen_narrativo')->map(fn ($r) => $r ?? 'Sin resumen')->toArray(),
            'actividades' => $actividades,
        ])->render();

        try {
            $result = $this->llm->suggest($promptText);
            $data = json_decode($result, true);

            return is_array($data) ? $data : ['coherente' => false, 'hallazgos' => []];
        } catch (\Exception $e) {
            return ['coherente' => false, 'hallazgos' => [], 'error' => $e->getMessage()];
        }
    }

    private function validarHorizontal(ProgramaPresupuestario $programa): array
    {
        $niveles = $programa->mirNiveles()
            ->with(['indicadores.mediosVerificacion'])
            ->get();

        $allHallazgos = [];

        foreach ($niveles as $nivel) {
            if ($nivel->indicadores->isEmpty()) {
                continue;
            }

            $indicadoresData = $nivel->indicadores->map(fn ($ind) => [
                'nombre' => $ind->nombre,
                'formula' => $ind->formula_texto,
                'tipo' => $ind->tipo?->label() ?? '',
                'dimension' => $ind->dimension?->label() ?? '',
                'frecuencia' => $ind->frecuencia?->label() ?? '',
                'medios' => $ind->mediosVerificacion->map(fn ($m) => [
                    'nombre' => $m->nombre,
                    'fuente' => $m->fuente,
                    'frecuencia' => $m->frecuencia,
                ])->toArray(),
            ])->toArray();

            $promptText = view('prompts.mir.validar-logica-horizontal', [
                'tipoNivel' => $nivel->tipo_nivel->label(),
                'resumenNarrativo' => $nivel->resumen_narrativo ?? 'No definido',
                'indicadores' => $indicadoresData,
            ])->render();

            try {
                $result = $this->llm->suggest($promptText);
                $data = json_decode($result, true);

                if (is_array($data) && isset($data['hallazgos'])) {
                    $allHallazgos = array_merge($allHallazgos, $data['hallazgos']);
                }
            } catch (\Exception $e) {
                // Continue with other levels
            }
        }

        return [
            'coherente' => empty($allHallazgos),
            'hallazgos' => $allHallazgos,
        ];
    }
}
