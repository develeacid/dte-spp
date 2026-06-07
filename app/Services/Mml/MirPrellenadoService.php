<?php

namespace App\Services\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNivelMir;
use App\Enums\TipoNodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;

class MirPrellenadoService
{
    public function prellenar(ProgramaPresupuestario $programa): void
    {
        if ($programa->mirNiveles()->exists()) {
            return;
        }

        $arbolObj = $programa->arboles()
            ->where('tipo', TipoArbol::OBJETIVOS->value)
            ->first();

        if (! $arbolObj) {
            return;
        }

        $objetivoCentral = $arbolObj->nodos()
            ->where('tipo_nodo', TipoNodo::OBJETIVO_CENTRAL->value)
            ->first();

        // Fin — desde fines directos del árbol de objetivos
        $finDirecto = $arbolObj->nodos()
            ->where('tipo_nodo', TipoNodo::FIN_DIRECTO->value)
            ->orderBy('orden')
            ->first();

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => $finDirecto?->descripcion ?? '',
            'arbol_nodo_id' => $finDirecto?->id,
            'orden' => 1,
        ]);

        // Propósito — desde objetivo central
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => $objetivoCentral?->descripcion ?? '',
            'arbol_nodo_id' => $objetivoCentral?->id,
            'orden' => 1,
        ]);

        // Componentes — desde medios de la alternativa seleccionada
        $alternativa = $programa->alternativas()
            ->where('seleccionada', true)
            ->with('nodos')
            ->first();

        if ($alternativa) {
            $mediosDirectos = $alternativa->nodos
                ->filter(fn ($n) => $n->tipo_nodo->value === TipoNodo::MEDIO_DIRECTO->value);

            foreach ($mediosDirectos->values() as $i => $medio) {
                MirNivel::create([
                    'programa_presupuestario_id' => $programa->id,
                    'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
                    'resumen_narrativo' => $medio->descripcion ?? '',
                    'arbol_nodo_id' => $medio->id,
                    'orden' => $i + 1,
                ]);
            }
        }
    }
}
