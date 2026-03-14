<?php

namespace Database\Seeders;

use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\MetaGastoTrimestral;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Seeder;

class PresupuestoTestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar PresupuestoTestSeeder en producción.');
            return;
        }

        $programas = ProgramaPresupuestario::whereNotNull('team_id')->take(3)->get();

        if ($programas->count() < 3) {
            $this->command->warn('Se necesitan al menos 3 programas presupuestarios. Ejecuta QaTestingSeeder primero.');
            return;
        }

        $user = User::first();

        // Claves COG típicas del gasto público
        $partidasCatalogo = [
            ['1000', 'Servicios Personales'],
            ['2000', 'Materiales y Suministros'],
            ['3000', 'Servicios Generales'],
            ['4000', 'Transferencias y Subsidios'],
            ['5000', 'Bienes Muebles e Inmuebles'],
            ['6000', 'Obras Públicas'],
        ];

        foreach ($programas as $idx => $programa) {
            $numPartidas = $idx === 0 ? 6 : 4; // Primer programa con más partidas
            $partidas = [];

            for ($i = 0; $i < $numPartidas; $i++) {
                $montoAprobado = match ($idx) {
                    0 => rand(500000, 2000000),   // Programa normal
                    1 => rand(200000, 800000),    // Programa con subejercicio
                    2 => rand(300000, 1200000),   // Programa con sobreejercicio
                };

                $montoModificado = $idx === 2
                    ? round($montoAprobado * 1.25, 2) // Sobreejercicio: modificado > aprobado
                    : null;

                $partida = PartidaPresupuestal::create([
                    'programa_presupuestario_id' => $programa->id,
                    'clave_partida' => $partidasCatalogo[$i][0],
                    'descripcion' => $partidasCatalogo[$i][1],
                    'monto_aprobado' => $montoAprobado,
                    'monto_modificado' => $montoModificado,
                    'ejercicio_fiscal' => $programa->ejercicio_fiscal,
                    'team_id' => $programa->team_id,
                    'registrado_por' => $user->id,
                ]);

                $partidas[] = $partida;
            }

            // Metas de gasto calendarizadas (distribución 20/25/30/25)
            $distribucion = [0.20, 0.25, 0.30, 0.25];

            foreach ($partidas as $partida) {
                $montoEfectivo = $partida->monto_modificado ?? $partida->monto_aprobado;

                foreach ($distribucion as $tri => $pct) {
                    MetaGastoTrimestral::create([
                        'partida_presupuestal_id' => $partida->id,
                        'trimestre' => $tri + 1,
                        'monto_programado' => round($montoEfectivo * $pct, 2),
                    ]);
                }
            }

            // Avances financieros para T1-T3
            foreach ($partidas as $partida) {
                $montoEfectivo = $partida->monto_modificado ?? $partida->monto_aprobado;

                for ($t = 1; $t <= 3; $t++) {
                    $factor = match ($idx) {
                        0 => $t * 0.25,           // Normal: ~25% por trimestre
                        1 => $t * 0.10,           // Subejercicio: solo ~10% por trimestre
                        2 => $t * 0.30,           // Sobreejercicio/desviación alta en T1
                    };

                    // Caso de desviación: programa 2, T1 con gasto muy alto
                    if ($idx === 2 && $t === 1) {
                        $factor = 0.45;
                    }

                    $comprometido = round($montoEfectivo * $factor, 2);
                    $devengado = round($comprometido * 0.95, 2);
                    $pagado = round($devengado * 0.90, 2);

                    AvanceFinanciero::create([
                        'partida_presupuestal_id' => $partida->id,
                        'trimestre' => $t,
                        'monto_comprometido' => $comprometido,
                        'monto_devengado' => $devengado,
                        'monto_pagado' => $pagado,
                        'registrado_por' => $user->id,
                        'observaciones' => $idx === 1 && $t === 3
                            ? 'Subejercicio detectado: revisar ejecución del programa'
                            : null,
                    ]);
                }
            }
        }

        $this->command->info('PresupuestoTestSeeder: datos de prueba creados para ' . $programas->count() . ' programas.');
    }
}
