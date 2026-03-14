<?php

namespace Database\Seeders;

use App\Enums\NivelJerarquiaLegal;
use App\Models\Juridico\CatalogoOrdenamiento;
use Illuminate\Database\Seeder;

class CatalogoOrdenamientosSeeder extends Seeder
{
    public function run(): void
    {
        $ordenamientos = [
            ['nombre' => 'Constitución Política de los Estados Unidos Mexicanos', 'nivel_jerarquia' => NivelJerarquiaLegal::CONSTITUCIONAL, 'abreviatura' => 'CPEUM', 'orden' => 1],
            ['nombre' => 'Constitución Política del Estado Libre y Soberano de Oaxaca', 'nivel_jerarquia' => NivelJerarquiaLegal::CONSTITUCIONAL, 'abreviatura' => 'CPEO', 'orden' => 2],
            ['nombre' => 'Ley General de Contabilidad Gubernamental', 'nivel_jerarquia' => NivelJerarquiaLegal::FEDERAL, 'abreviatura' => 'LGCG', 'orden' => 3],
            ['nombre' => 'Ley Federal de Presupuesto y Responsabilidad Hacendaria', 'nivel_jerarquia' => NivelJerarquiaLegal::FEDERAL, 'abreviatura' => 'LFPRH', 'orden' => 4],
            ['nombre' => 'Ley de Planeación', 'nivel_jerarquia' => NivelJerarquiaLegal::FEDERAL, 'abreviatura' => 'LP', 'orden' => 5],
            ['nombre' => 'Ley Estatal de Planeación', 'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL, 'abreviatura' => 'LEP', 'orden' => 6],
            ['nombre' => 'Ley Estatal de Presupuesto y Responsabilidad Hacendaria', 'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL, 'abreviatura' => 'LEPRH', 'orden' => 7],
            ['nombre' => 'Ley de Fiscalización Superior y Rendición de Cuentas del Estado de Oaxaca', 'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL, 'abreviatura' => 'LFSRC', 'orden' => 8],
            ['nombre' => 'Ley Orgánica del Poder Ejecutivo del Estado de Oaxaca', 'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL, 'abreviatura' => 'LOEPO', 'orden' => 9],
            ['nombre' => 'Ley de Transparencia y Acceso a la Información Pública para el Estado de Oaxaca', 'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL, 'abreviatura' => 'LTAIP', 'orden' => 10],
            ['nombre' => 'Presupuesto de Egresos del Estado de Oaxaca', 'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL, 'abreviatura' => 'PEE', 'orden' => 11],
            ['nombre' => 'Reglamento Interior de la Secretaría de Finanzas', 'nivel_jerarquia' => NivelJerarquiaLegal::REGLAMENTARIO, 'abreviatura' => 'RISF', 'orden' => 12],
            ['nombre' => 'Lineamientos para la Construcción de Indicadores de Desempeño', 'nivel_jerarquia' => NivelJerarquiaLegal::REGLAMENTARIO, 'abreviatura' => 'LCID', 'orden' => 13],
            ['nombre' => 'Reglas de Operación del Programa', 'nivel_jerarquia' => NivelJerarquiaLegal::OPERATIVO, 'abreviatura' => 'ROP', 'orden' => 14],
            ['nombre' => 'Manual de Programación y Presupuestación', 'nivel_jerarquia' => NivelJerarquiaLegal::OPERATIVO, 'abreviatura' => 'MPP', 'orden' => 15],
        ];

        foreach ($ordenamientos as $data) {
            CatalogoOrdenamiento::updateOrCreate(
                ['abreviatura' => $data['abreviatura']],
                $data
            );
        }
    }
}
