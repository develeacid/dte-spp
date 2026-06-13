<?php

namespace App\Services\Presupuesto;

use App\Enums\EstadoCierreFiscal;
use App\Models\Presupuesto\CierreFiscal;
use App\Models\Presupuesto\Iaff;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use DomainException;

/**
 * D5 · Máquina de estados del cierre fiscal anual de un programa
 * (PREVALIDACION → CONSOLIDACION → FIRMA → CERRADO, forward-only).
 * La transición a FIRMA exige el IAFF del Q4 firmado.
 */
class CierreFiscalService
{
    public function iniciar(ProgramaPresupuestario $programa, int $ejercicio): CierreFiscal
    {
        return CierreFiscal::firstOrCreate(
            ['programa_id' => $programa->id, 'ejercicio_fiscal' => $ejercicio],
            ['estado' => EstadoCierreFiscal::PREVALIDACION, 'historial' => []],
        );
    }

    public function avanzar(CierreFiscal $cierre, User $user): CierreFiscal
    {
        $siguiente = $cierre->estado->siguiente();

        if ($siguiente === null) {
            throw new DomainException('El cierre fiscal ya está CERRADO; no admite más avances.');
        }

        $this->validarGate($cierre, $siguiente);

        $historial = $cierre->historial ?? [];
        $historial[] = [
            'estado' => $siguiente->value,
            'en' => now()->toIso8601String(),
            'por' => $user->id,
        ];

        $cierre->update([
            'estado' => $siguiente,
            'historial' => $historial,
        ]);

        return $cierre->fresh();
    }

    private function validarGate(CierreFiscal $cierre, EstadoCierreFiscal $destino): void
    {
        if ($destino === EstadoCierreFiscal::FIRMA && ! $this->iaffQ4Firmado($cierre)) {
            throw new DomainException(
                'No se puede avanzar a FIRMA: el IAFF del 4º trimestre del ejercicio debe estar firmado.'
            );
        }
    }

    private function iaffQ4Firmado(CierreFiscal $cierre): bool
    {
        return Iaff::where('programa_id', $cierre->programa_id)
            ->where('ejercicio_fiscal', $cierre->ejercicio_fiscal)
            ->where('trimestre', 4)
            ->firmados()
            ->exists();
    }
}
