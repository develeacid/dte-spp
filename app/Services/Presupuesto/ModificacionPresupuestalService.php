<?php

namespace App\Services\Presupuesto;

use App\Models\Presupuesto\ModificacionPresupuestal;
use App\Models\Presupuesto\PartidaPresupuestal;
use Illuminate\Support\Facades\DB;

/**
 * Registra adecuaciones presupuestarias (V2-E5) y mantiene `partidas.monto_modificado`
 * derivado del log: aprobado + Σampliaciones − Σreducciones.
 */
class ModificacionPresupuestalService
{
    public function registrar(PartidaPresupuestal $partida, array $datos): ModificacionPresupuestal
    {
        return DB::transaction(function () use ($partida, $datos) {
            $modificacion = $partida->modificaciones()->create([
                'tipo' => $datos['tipo'],
                'monto' => $datos['monto'],
                'fecha' => $datos['fecha'],
                'oficio' => $datos['oficio'] ?? null,
                'justificacion' => $datos['justificacion'] ?? null,
                'registrado_por' => $datos['registrado_por'] ?? auth()->id(),
            ]);

            $this->recalcularMontoModificado($partida);

            return $modificacion;
        });
    }

    /** Recalcula y persiste el monto modificado de la partida desde el log de adecuaciones. */
    public function recalcularMontoModificado(PartidaPresupuestal $partida): void
    {
        $neto = $partida->modificaciones()->get()->sum(
            fn (ModificacionPresupuestal $m) => $m->tipo->signo() * (float) $m->monto
        );

        $partida->monto_modificado = (float) $partida->monto_aprobado + $neto;
        $partida->save();
    }
}
