<?php

namespace Database\Seeders\Cascade;

use App\Models\OdsMeta;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivado;
use App\Models\ProgramaDerivadoObjetivo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlineacionesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando alineaciones PND↔ODS, PED↔PND y Líneas↔Programas...');

        $this->seedPndOds();
        $this->seedPedPnd();
        $this->seedLineaPrograma();

        $this->command->newLine();
        $this->command->table(['Tabla', 'Registros'], [
            ['alineacion_pnd_ods', DB::table('alineacion_pnd_ods')->count()],
            ['alineacion_ped_pnd', DB::table('alineacion_ped_pnd')->count()],
            ['alineacion_linea_programa', DB::table('alineacion_linea_programa')->count()],
        ]);
    }

    /**
     * Alineación PND Objetivos ↔ ODS Metas (8 rows).
     */
    private function seedPndOds(): void
    {
        $alignments = [
            ['1.1', ['16.1', '16.a']],
            ['2.1', ['3.4', '3.8']],
            ['3.1', ['8.3', '2.3']],
            ['3.2', ['8.9', '12.b']],
        ];

        foreach ($alignments as [$pndClave, $odsClaves]) {
            $pnd = PndObjetivo::where('clave', $pndClave)->firstOrFail();
            $odsIds = OdsMeta::whereIn('clave', $odsClaves)->pluck('id')->toArray();
            $pnd->odsMetas()->syncWithoutDetaching($odsIds);
        }

        $this->command->info('  PND↔ODS: 8 alineaciones');
    }

    /**
     * Alineación PED Objetivos Estratégicos ↔ PND Objetivos (5 rows).
     */
    private function seedPedPnd(): void
    {
        $alignments = [
            ['1.9', '2.1'],
            ['3.1', '1.1'],
            ['4.1', '3.1'],
            ['4.4', '3.2'],
            ['4.5', '3.1'],
        ];

        foreach ($alignments as [$pedClave, $pndClave]) {
            $ped = PedObjetivoEstrategico::where('clave', $pedClave)->firstOrFail();
            $pnd = PndObjetivo::where('clave', $pndClave)->firstOrFail();
            $ped->pndObjetivos()->syncWithoutDetaching([$pnd->id]);
        }

        $this->command->info('  PED↔PND: 5 alineaciones');
    }

    /**
     * Alineación Líneas de Acción ↔ Programas Derivados Objetivos (13 rows).
     */
    private function seedLineaPrograma(): void
    {
        $alignments = [
            ['4.1.1.2', 'Desarrollo Económico', '4'],
            ['4.1.1.3', 'Desarrollo Económico', '1'],
            ['4.5.1.5', 'Desarrollo Económico', '3'],
            ['4.5.1.6', 'Desarrollo Económico', '3'],
            ['4.4.1.5', 'Desarrollo Económico', '2'],
            ['4.4.3.1', 'Desarrollo Económico', '2'],
            ['1.9.1.1', 'Salud', '1'],
            ['1.9.1.2', 'Salud', '2'],
            ['1.9.1.3', 'Salud', '3'],
            ['3.1.1.3', 'Seguridad', '1'],
            ['3.1.1.4', 'Seguridad', '1'],
            ['3.1.1.6', 'Seguridad', '2'],
            ['3.1.2.1', 'Seguridad', '3'],
        ];

        foreach ($alignments as [$laClave, $programaLike, $objClave]) {
            $la = $this->findLineaAccion($laClave);
            $programa = ProgramaDerivado::where('nombre', 'LIKE', "%{$programaLike}%")->firstOrFail();
            $objetivo = ProgramaDerivadoObjetivo::where('programa_derivado_id', $programa->id)
                ->where('clave', $objClave)
                ->firstOrFail();
            $la->programasDerivadosObjetivos()->syncWithoutDetaching([$objetivo->id]);
        }

        $this->command->info('  Líneas↔Programas: 13 alineaciones');
    }

    /**
     * Encuentra una PedLineaAccion a partir de su clave completa (e.g. "4.1.1.2").
     *
     * Descompone "4.1.1.2" en:
     *  - Objetivo Estratégico clave: "4.1"
     *  - Estrategia clave local: "1"
     *  - Línea de Acción clave local: "2"
     */
    private function findLineaAccion(string $fullClave): PedLineaAccion
    {
        $parts = explode('.', $fullClave);

        $objClave = "{$parts[0]}.{$parts[1]}";
        $estrategiaClave = $parts[2];
        $laClave = $parts[3];

        $objetivo = PedObjetivoEstrategico::where('clave', $objClave)->firstOrFail();

        $estrategia = PedEstrategia::where('ped_objetivo_estrategico_id', $objetivo->id)
            ->where('clave', $estrategiaClave)
            ->firstOrFail();

        return PedLineaAccion::where('ped_estrategia_id', $estrategia->id)
            ->where('clave', $laClave)
            ->firstOrFail();
    }
}
