<?php

namespace Database\Seeders;

use App\Models\OdsMeta;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlineacionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando alineaciones de ejemplo...');

        // ============================================
        // Alineación PED → PND → ODS
        // ============================================

        $pedObjetivo = PedObjetivoEstrategico::first();

        if (!$pedObjetivo) {
            $this->command->error('No hay objetivos estratégicos PED. Ejecuta PedSeeder primero.');
            return;
        }

        $pndObjetivo = PndObjetivo::first();

        if (!$pndObjetivo) {
            $this->command->error('No hay objetivos PND. Ejecuta PndSeeder primero.');
            return;
        }

        // Alinear PED → PND
        $pedObjetivo->pndObjetivos()->syncWithoutDetaching([$pndObjetivo->id]);
        $this->command->info("  PED {$pedObjetivo->clave_completa} → PND {$pndObjetivo->clave}");

        // Alinear PND → ODS (tomar 2 metas del ODS 1)
        $odsMetas = OdsMeta::where('clave', 'like', '1.%')->take(2)->get();

        foreach ($odsMetas as $odsMeta) {
            $pndObjetivo->odsMetas()->syncWithoutDetaching([$odsMeta->id]);
            $this->command->info("    PND {$pndObjetivo->clave} → ODS Meta {$odsMeta->clave}");
        }

        // ============================================
        // Alineación Línea de Acción ↔ Programa Derivado
        // ============================================

        $lineaAccion = PedLineaAccion::first();
        $progObjetivo = ProgramaDerivadoObjetivo::first();

        if ($lineaAccion && $progObjetivo) {
            $lineaAccion->programasDerivadosObjetivos()->syncWithoutDetaching([$progObjetivo->id]);
            $this->command->info("  Línea {$lineaAccion->clave_completa} → Programa {$progObjetivo->clave_completa}");
        }

        // ============================================
        // Resumen
        // ============================================

        $this->command->newLine();
        $this->command->table(
            ['Tipo de Alineación', 'Registros'],
            [
                ['PED ↔ PND', DB::table('alineacion_ped_pnd')->count()],
                ['PND ↔ ODS', DB::table('alineacion_pnd_ods')->count()],
                ['Línea ↔ Programa', DB::table('alineacion_linea_programa')->count()],
            ]
        );
    }
}
