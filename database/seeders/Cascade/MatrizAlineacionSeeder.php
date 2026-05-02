<?php

namespace Database\Seeders\Cascade;

use App\Models\OdsMeta;
use App\Models\PedObjetivoEstrategico;
use App\Models\PndObjetivo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MatrizAlineacionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Limpiando alineaciones anteriores...');
        DB::table('alineacion_ped_pnd')->truncate();
        DB::table('alineacion_pnd_ods')->truncate();

        $this->seedPedPnd();
        $this->seedPndOds();
    }

    /**
     * Alineación PED Oaxaca 2022-2028 ↔ PND 2025-2030
     * Basado en correspondencia temática entre objetivos estratégicos estatales y nacionales.
     */
    private function seedPedPnd(): void
    {
        $this->command->info('Creando alineaciones PED ↔ PND...');

        // Mapeo: clave PED objetivo → claves PND objetivo(s)
        // PED Eje 1: Estado de Bienestar
        $map = [
            // Tema 1.1: Combate a la pobreza → PND 2.1 (protección social)
            '1.1' => ['2.1'],
            // Tema 1.2: Alimentación → PND 3.4 (soberanía alimentaria)
            '1.2' => ['3.4'],
            // Tema 1.3: Inclusión grupos prioritarios → PND 2.1 (protección social)
            '1.3' => ['2.1'],
            // Tema 1.4: Migración → PND 2.2 (atención a vulnerables), 1.2 (derechos humanos)
            '1.4' => ['2.2', '1.2'],
            // Tema 1.5: Cultura y Artes → PND 2.5 (derecho a la cultura), 2.6 (educación y cultura)
            '1.5' => ['2.5', '2.6'],
            // Tema 1.6: Juventudes → PND 2.4 (desarrollo científico y capacitación)
            '1.6' => ['2.4'],
            // Tema 1.7: Deporte → PND 2.3 (educación inclusiva)
            '1.7' => ['2.3'],
            // Tema 1.8: Educación → PND 2.3 (educación), 2.4 (ciencia y tecnología)
            '1.8' => ['2.3', '2.4'],
            // Tema 1.9: Salud → PND 2.7 (salud)
            '1.9' => ['2.7'],

            // PED Eje 2: Gobierno Honesto
            // Tema 2.1: Combate a la corrupción → PND 1.3 (erradicar corrupción)
            '2.1' => ['1.3'],
            // Tema 2.2: Administración eficiente → PND 1.4 (uso eficiente recursos), T2.1 (simplificación trámites)
            '2.2' => ['1.4', 'T2.1'],
            // Tema 2.3: Planeación para el bienestar → PND 2.10 (entornos públicos justos)
            '2.3' => ['2.10'],
            // Tema 2.4: Recaudación eficiente → PND 1.4 (fortalecer ingresos)
            '2.4' => ['1.4'],
            // Tema 2.5: Ejercicio del gasto → PND 1.4 (austeridad republicana)
            '2.5' => ['1.4'],
            // Tema 2.6: Mejora continua gestión pública → PND T2.2 (transformación digital)
            '2.6' => ['T2.2'],

            // PED Eje 3: Seguridad y Justicia
            // Tema 3.1: Prevención y seguridad → PND 1.5 (seguridad pública)
            '3.1' => ['1.5'],
            // Tema 3.2: Gobernabilidad y DDHH → PND 1.1 (sociedad democrática), 1.2 (derechos humanos)
            '3.2' => ['1.1', '1.2'],
            // Tema 3.3: Conflicto agrario → PND T3.4 (libre determinación pueblos)
            '3.3' => ['T3.4'],
            // Tema 3.4: Protección civil → PND 2.2 (atención a emergencias)
            '3.4' => ['2.2'],

            // PED Eje 4: Crecimiento Económico
            // Tema 4.1: Fortalecimiento desarrollo dinámico → PND 3.9 (desarrollo económico equilibrado)
            '4.1' => ['3.9', '3.10'],
            // Tema 4.2: Impulso económico → PND 3.9 (crecimiento), 3.10 (cadenas proveeduría)
            '4.2' => ['3.9'],
            // Tema 4.3: Empleo → PND 3.1 (salarios justos), 3.2 (trabajo digno)
            '4.3' => ['3.1', '3.2'],
            // Tema 4.4: Turismo → PND 3.11 (desarrollo turístico)
            '4.4' => ['3.11'],
            // Tema 4.5: Fomento agroalimentario → PND 3.4 (soberanía alimentaria), 3.5 (bienestar rural), 3.6 (campo)
            '4.5' => ['3.4', '3.5', '3.6'],
            // Tema 4.6: Desarrollo forestal → PND 4.5 (ecosistemas naturales)
            '4.6' => ['4.5'],

            // PED Eje 5: Infraestructura
            // Tema 5.1: Infraestructura ciudades → PND 2.10 (entornos públicos)
            '5.1' => ['2.10'],
            // Tema 5.2: Caminos y carreteras → PND 3.7 (movilidad e infraestructura)
            '5.2' => ['3.7'],
            // Tema 5.3: Vivienda → PND 2.9 (vivienda)
            '5.3' => ['2.9'],
            // Tema 5.4: Agua y saneamiento → PND 4.6 (derecho al agua)
            '5.4' => ['4.6'],
            // Tema 5.5: Infraestructura educativa → PND 2.3 (educación)
            '5.5' => ['2.3'],
            // Tema 5.6: Patrimonio cultural → PND 2.5 (cultura)
            '5.6' => ['2.5'],
            // Tema 5.7: Movilidad y seguridad vial → PND 3.7 (movilidad)
            '5.7' => ['3.7'],
        ];

        $count = 0;
        foreach ($map as $pedClave => $pndClaves) {
            $pedObj = PedObjetivoEstrategico::where('clave', $pedClave)->first();
            if (! $pedObj) {
                $this->command->warn("  PED Objetivo {$pedClave} no encontrado");

                continue;
            }

            foreach ($pndClaves as $pndClave) {
                $pndObj = PndObjetivo::where('clave', $pndClave)->first();
                if (! $pndObj) {
                    $this->command->warn("  PND Objetivo {$pndClave} no encontrado");

                    continue;
                }

                $pedObj->pndObjetivos()->syncWithoutDetaching([$pndObj->id]);
                $count++;
                $this->command->info("  PED {$pedClave} → PND {$pndClave}");
            }
        }

        $this->command->info("Total alineaciones PED↔PND: {$count}");
    }

    /**
     * Alineación PND 2025-2030 ↔ ODS Agenda 2030
     * Basado en correspondencia temática entre objetivos nacionales y metas ODS.
     */
    private function seedPndOds(): void
    {
        $this->command->info('');
        $this->command->info('Creando alineaciones PND ↔ ODS...');

        // Mapeo: clave PND objetivo → claves ODS meta(s)
        $map = [
            // Eje 1: Gobernanza
            '1.1' => ['16.6', '16.7'],         // Sociedad democrática → Paz, instituciones
            '1.2' => ['16.3', '10.3'],          // Derechos humanos → Justicia, reducir desigualdad
            '1.3' => ['16.5', '16.6'],          // Combate corrupción → Instituciones eficaces
            '1.4' => ['16.6'],                  // Recursos públicos → Instituciones eficaces
            '1.5' => ['16.1', '16.a'],          // Seguridad pública → Reducir violencia
            '1.6' => ['16.a'],                  // Seguridad nacional → Instituciones
            '1.7' => ['17.16', '17.17'],        // Relaciones internacionales → Alianzas

            // Eje 2: Bienestar
            '2.1' => ['1.3', '1.4', '10.2'],   // Protección social → Fin pobreza
            '2.2' => ['10.7', '1.5'],           // Atención vulnerables/migrantes → Reducir desigualdad
            '2.3' => ['4.1', '4.2', '4.5'],     // Educación → Educación de calidad
            '2.4' => ['4.4', '8.6', '9.5'],     // Ciencia y capacitación → Educación, trabajo, innovación
            '2.5' => ['11.4'],                  // Cultura → Patrimonio cultural
            '2.6' => ['4.7'],                   // Educación y cultura → Educación para desarrollo sostenible
            '2.7' => ['3.1', '3.2', '3.8'],     // Salud → Salud y bienestar
            '2.8' => ['3.b', '3.d', '9.5'],     // Investigación en salud → Salud, innovación
            '2.9' => ['11.1'],                  // Vivienda → Ciudades sostenibles
            '2.10' => ['11.2', '11.3', '11.7'], // Entornos públicos → Ciudades sostenibles

            // Eje 3: Economía
            '3.1' => ['8.5', '10.4'],           // Salarios justos → Trabajo decente
            '3.2' => ['8.5', '8.6', '8.8'],     // Trabajo digno → Trabajo decente
            '3.3' => ['1.3', '8.5'],            // Pensiones → Fin pobreza, trabajo decente
            '3.4' => ['2.1', '2.3', '2.4'],     // Soberanía alimentaria → Hambre cero
            '3.5' => ['2.3', '1.4'],            // Bienestar rural → Hambre cero, fin pobreza
            '3.6' => ['2.4', '2.a'],            // Campo mexicano → Hambre cero
            '3.7' => ['9.1', '11.2'],           // Movilidad/infraestructura → Industria, ciudades
            '3.8' => ['9.c', '17.8'],           // Telecomunicaciones → Industria, alianzas
            '3.9' => ['8.1', '8.2', '8.3'],     // Desarrollo económico → Trabajo decente
            '3.10' => ['9.2', '9.3'],           // Cadenas proveeduría → Industria
            '3.11' => ['8.9', '12.b'],          // Turismo → Trabajo decente, producción sostenible

            // Eje 4: Desarrollo sustentable
            '4.1' => ['7.1', '7.2'],            // Soberanía energética → Energía
            '4.2' => ['7.2', '7.a'],            // Energías limpias → Energía
            '4.3' => ['13.2', '12.4', '12.5'],  // Emisiones/resiliencia climática → Clima, producción
            '4.4' => ['7.1', '7.b'],            // Acceso equitativo energía → Energía
            '4.5' => ['15.1', '15.2', '15.5'],  // Ecosistemas → Vida terrestre
            '4.6' => ['6.1', '6.3', '6.4'],     // Derecho al agua → Agua limpia

            // Ejes Transversales
            'T1.1' => ['5.1', '5.a', '8.5'],    // Autonomía económica mujeres → Igualdad género
            'T1.2' => ['5.4'],                  // Sociedad de cuidados → Igualdad género
            'T1.3' => ['5.5', '16.7'],          // Participación mujeres → Igualdad género
            'T1.4' => ['5.2', '5.3'],           // Erradicar violencia mujeres → Igualdad género
            'T1.5' => ['5.2', '16.3'],          // Protección víctimas → Igualdad género, justicia

            'T2.1' => ['9.c', '16.6'],          // Digitalización trámites → Industria, instituciones
            'T2.2' => ['9.c', '16.6'],          // Transformación digital → Industria, instituciones
            'T2.3' => ['9.c', '17.8'],          // Programa Espacial → Industria, alianzas
            'T2.4' => ['9.5', '9.b'],           // Investigación e innovación → Industria

            'T3.1' => ['10.3', '16.3'],         // Derechos pueblos indígenas → Reducir desigualdad
            'T3.2' => ['1.4', '10.2'],          // Planes justicia regional → Fin pobreza
            'T3.3' => ['10.2', '10.3'],         // Políticas directas pueblos → Reducir desigualdad
            'T3.4' => ['16.7'],                 // Libre determinación → Instituciones inclusivas
            'T3.5' => ['11.4', '2.5'],          // Patrimonio cultural indígena → Ciudades, hambre cero
            'T3.6' => ['1.4', '2.3', '10.2'],   // Desarrollo integral indígena → Pobreza, hambre, desigualdad
        ];

        $count = 0;
        foreach ($map as $pndClave => $odsClaves) {
            $pndObj = PndObjetivo::where('clave', $pndClave)->first();
            if (! $pndObj) {
                $this->command->warn("  PND Objetivo {$pndClave} no encontrado");

                continue;
            }

            foreach ($odsClaves as $odsClave) {
                $odsMeta = OdsMeta::where('clave', $odsClave)->first();
                if (! $odsMeta) {
                    $this->command->warn("  ODS Meta {$odsClave} no encontrada");

                    continue;
                }

                $pndObj->odsMetas()->syncWithoutDetaching([$odsMeta->id]);
                $count++;
                $this->command->info("  PND {$pndClave} → ODS {$odsClave}");
            }
        }

        $this->command->info("Total alineaciones PND↔ODS: {$count}");
    }
}
