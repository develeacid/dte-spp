<?php

namespace App\Services\Tracking;

use App\Enums\FrecuenciaMedicion;
use App\Services\Mml\CalendarizacionService;
use Carbon\Carbon;

class CalendarioService
{
    public function calcularFechas(int $ejercicio, FrecuenciaMedicion $frecuencia): array
    {
        $calendarizacion = new CalendarizacionService();
        $numPeriodos = $calendarizacion->numeroPeriodos($frecuencia);
        $periodos = [];
        for ($i = 1; $i <= $numPeriodos; $i++) {
            $mesApertura = $this->mesApertura($frecuencia, $i);
            $anio = $ejercicio;
            if ($mesApertura > 12) {
                $mesApertura -= 12;
                $anio++;
            }
            $fechaApertura = Carbon::create($anio, $mesApertura, 1);
            $fechaCierre = $fechaApertura->copy()->addDays(14);
            $periodos[] = [
                'periodo' => $i,
                'fecha_apertura' => $fechaApertura->toDateString(),
                'fecha_cierre' => $fechaCierre->toDateString(),
            ];
        }
        return $periodos;
    }

    private function mesApertura(FrecuenciaMedicion $frecuencia, int $periodo): int
    {
        return match ($frecuencia) {
            FrecuenciaMedicion::MENSUAL => $periodo + 1,
            FrecuenciaMedicion::TRIMESTRAL => ($periodo * 3) + 1,
            FrecuenciaMedicion::SEMESTRAL => ($periodo * 6) + 1,
            FrecuenciaMedicion::ANUAL, FrecuenciaMedicion::BIANUAL, FrecuenciaMedicion::SEXENAL => 13,
        };
    }
}
