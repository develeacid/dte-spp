<?php

namespace Database\Seeders;

use App\Models\Presupuesto\AvanceFinanciero;
use App\Models\Presupuesto\MetaGastoTrimestral;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Database\Seeder;

class Fase2PresupuestoSeeder extends Seeder
{
    /**
     * Catálogo COG (Clasificador por Objeto del Gasto) - capítulos.
     */
    private array $partidasCatalogo = [
        ['1000', 'Servicios Personales'],
        ['2000', 'Materiales y Suministros'],
        ['3000', 'Servicios Generales'],
        ['4000', 'Transferencias y Subsidios'],
        ['5000', 'Bienes Muebles e Inmuebles'],
        ['6000', 'Obras Públicas'],
    ];

    /**
     * Distribución trimestral del gasto: 20/25/30/25.
     */
    private array $distribucionTrimestral = [0.20, 0.25, 0.30, 0.25];

    /**
     * Programas por UR con sus claves (mismos 16 de Fase1).
     * Escenario financiero por programa:
     *   'normal'        → pagado/programado ~0.90-1.10 → VERDE
     *   'subejercicio'  → pagado/programado ~0.40-0.55 → ROJO
     *   'sobreejercicio'→ pagado/programado ~1.35-1.40 → ROJO
     */
    private array $programasDef = [
        // SE-001: Ejecución normal (85-115%)
        'SE-001' => [
            ['clave' => 'ISM-001', 'escenario' => 'normal',  'base' => 1200000],
            ['clave' => 'EDU-002', 'escenario' => 'normal',  'base' => 1800000],
            ['clave' => 'EDU-003', 'escenario' => 'normal',  'base' => 900000],
            ['clave' => 'EDU-004', 'escenario' => 'normal',  'base' => 1500000],
        ],
        // SS-002: Subejercicio en 2 programas
        'SS-002' => [
            ['clave' => 'PEC-001', 'escenario' => 'subejercicio', 'base' => 800000],
            ['clave' => 'SAL-002', 'escenario' => 'normal',       'base' => 1200000],
            ['clave' => 'SAL-003', 'escenario' => 'subejercicio', 'base' => 600000],
            ['clave' => 'SAL-004', 'escenario' => 'normal',       'base' => 1000000],
        ],
        // SEG-003: Mixto (1 normal, 1 subejercicio, 1 sobreejercicio, 1 normal)
        'SEG-003' => [
            ['clave' => 'FSP-001',  'escenario' => 'normal',         'base' => 700000],
            ['clave' => 'SEG-002',  'escenario' => 'subejercicio',   'base' => 500000],
            ['clave' => 'SEG-003P', 'escenario' => 'sobreejercicio', 'base' => 400000],
            ['clave' => 'SEG-004',  'escenario' => 'normal',         'base' => 600000],
        ],
        // SECTUR-004: Sobreejercicio en 1
        'SECTUR-004' => [
            ['clave' => 'DDT-001', 'escenario' => 'sobreejercicio', 'base' => 1600000],
            ['clave' => 'TUR-002', 'escenario' => 'normal',         'base' => 1000000],
            ['clave' => 'TUR-003', 'escenario' => 'normal',         'base' => 800000],
            ['clave' => 'TUR-004', 'escenario' => 'normal',         'base' => 1400000],
        ],
    ];

    /**
     * Sufijo de email para cada UR (financiero.<slug>@sistema.test).
     */
    private array $slugMap = [
        'SE-001'     => 'se',
        'SS-002'     => 'ss',
        'SEG-003'    => 'seg',
        'SECTUR-004' => 'sectur',
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar en producción.');
            return;
        }

        $this->command->info('Fase 2: Creando datos presupuestales para 16 programas...');

        $totales = ['partidas' => 0, 'metas' => 0, 'avances' => 0];

        foreach ($this->programasDef as $urClave => $programas) {
            $urSlug = $this->slugMap[$urClave];
            $financieroUser = User::where('email', "financiero.{$urSlug}@sistema.test")->first();

            if (! $financieroUser) {
                $this->command->warn("No se encontró financiero.{$urSlug}@sistema.test — saltando UR {$urClave}");
                continue;
            }

            foreach ($programas as $progIdx => $progDef) {
                $programa = ProgramaPresupuestario::where('clave', $progDef['clave'])->first();

                if (! $programa) {
                    $this->command->warn("Programa {$progDef['clave']} no encontrado — saltando");
                    continue;
                }

                $counts = $this->crearPartidasParaPrograma(
                    $programa,
                    $progDef,
                    $progIdx,
                    $financieroUser
                );

                $totales['partidas'] += $counts['partidas'];
                $totales['metas']    += $counts['metas'];
                $totales['avances']  += $counts['avances'];
            }
        }

        $this->command->newLine();
        $this->command->table(
            ['Entidad', 'Registros'],
            [
                ['Partidas presupuestales', $totales['partidas']],
                ['Metas gasto trimestral', $totales['metas']],
                ['Avances financieros', $totales['avances']],
            ]
        );
        $this->command->info('Fase 2 completada.');
    }

    /**
     * Crea 4 partidas COG, metas trimestrales y avances para un programa.
     */
    private function crearPartidasParaPrograma(
        ProgramaPresupuestario $programa,
        array $progDef,
        int $progIdx,
        User $financieroUser,
    ): array {
        $counts = ['partidas' => 0, 'metas' => 0, 'avances' => 0];

        for ($i = 0; $i < 4; $i++) {
            // Monto determinista basado en base + offset por índice de partida
            $montoAprobado = $progDef['base'] + ($i * 150000);

            // Sobreejercicio: monto_modificado > aprobado (ampliación presupuestal)
            $montoModificado = $progDef['escenario'] === 'sobreejercicio'
                ? round($montoAprobado * 1.15, 2) // +15% por adecuación
                : null;

            $partida = PartidaPresupuestal::firstOrCreate(
                [
                    'programa_presupuestario_id' => $programa->id,
                    'clave_partida'              => $this->partidasCatalogo[$i][0],
                    'ejercicio_fiscal'           => $programa->ejercicio_fiscal,
                ],
                [
                    'descripcion'      => $this->partidasCatalogo[$i][1],
                    'monto_aprobado'   => $montoAprobado,
                    'monto_modificado' => $montoModificado,
                    'team_id'          => $programa->team_id,
                    'registrado_por'   => $financieroUser->id,
                ]
            );

            $counts['partidas']++;

            // ── Metas de gasto trimestral ──
            $montoEfectivo = $partida->monto_efectivo;

            foreach ($this->distribucionTrimestral as $tri => $pct) {
                MetaGastoTrimestral::firstOrCreate(
                    [
                        'partida_presupuestal_id' => $partida->id,
                        'trimestre'               => $tri + 1,
                    ],
                    [
                        'monto_programado' => round($montoEfectivo * $pct, 2),
                    ]
                );
                $counts['metas']++;
            }

            // ── Avances financieros T1-T3 (T4 y 2026 quedan pendientes) ──
            $factorComprometido = $this->factorComprometido($progDef['escenario'], $progIdx);

            for ($t = 1; $t <= 3; $t++) {
                $metaProgramado = round($montoEfectivo * $this->distribucionTrimestral[$t - 1], 2);

                $comprometido = round($metaProgramado * $factorComprometido, 2);
                $devengado    = round($comprometido * 0.95, 2);
                $pagado       = round($devengado * 0.90, 2);

                $observaciones = $this->observacionesPorEscenario($progDef['escenario'], $t);

                AvanceFinanciero::firstOrCreate(
                    [
                        'partida_presupuestal_id' => $partida->id,
                        'trimestre'               => $t,
                    ],
                    [
                        'monto_comprometido' => $comprometido,
                        'monto_devengado'    => $devengado,
                        'monto_pagado'       => $pagado,
                        'registrado_por'     => $financieroUser->id,
                        'observaciones'      => $observaciones,
                    ]
                );
                $counts['avances']++;
            }
        }

        return $counts;
    }

    /**
     * Factor de comprometido según escenario financiero.
     *
     * Normal:         ~1.0  (pagado/programado ≈ 0.855 → dentro de 0.85-1.15 = VERDE)
     * Subejercicio:   ~0.45 (pagado/programado ≈ 0.384 → bajo 0.60 = ROJO)
     * Sobreejercicio: ~1.40 (pagado/programado ≈ 1.197 → sobre 1.15 pero con mod. presup.)
     */
    private function factorComprometido(string $escenario, int $progIdx): float
    {
        return match ($escenario) {
            'normal'         => 1.0 + ($progIdx * 0.02),        // 1.00, 1.02, 1.04, 1.06
            'subejercicio'   => 0.45 + ($progIdx * 0.01),       // 0.45, 0.46
            'sobreejercicio' => 1.40,
        };
    }

    /**
     * Observaciones descriptivas por escenario.
     */
    private function observacionesPorEscenario(string $escenario, int $trimestre): ?string
    {
        return match (true) {
            $escenario === 'subejercicio' && $trimestre === 3
                => 'Subejercicio detectado: analizar causas de baja ejecución presupuestal.',
            $escenario === 'sobreejercicio' && $trimestre >= 2
                => 'Sobreejercicio: ampliación presupuestal autorizada por adecuación.',
            default => null,
        };
    }
}
