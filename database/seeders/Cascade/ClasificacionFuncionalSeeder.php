<?php

namespace Database\Seeders\Cascade;

use App\Models\Presupuesto\ClasificacionFuncional;
use Illuminate\Database\Seeder;

/**
 * Catálogo CONAC — Clasificación Funcional del Gasto.
 * Fuente: Acuerdo por el que se emite la Clasificación Funcional del Gasto (DOF 27/12/2010).
 * 4 finalidades / 28 funciones / 111 subfunciones. Idempotente (firstOrCreate por [nivel, clave]).
 */
class ClasificacionFuncionalSeeder extends Seeder
{
    public function run(): void
    {
        // Catálogo CONAC — Clasificación Funcional del Gasto (DOF 27/12/2010).
        // Estructura: ['clave_finalidad' => ['Nombre', ['clave_funcion' => ['Nombre', ['clave_subfuncion' => 'Nombre', ...]]]]]
        $catalogo = [
            '1' => ['Gobierno', [
                '11' => ['Legislación', ['111' => 'Legislación', '112' => 'Fiscalización']],
                '12' => ['Justicia', ['121' => 'Impartición de Justicia', '122' => 'Procuración de Justicia', '123' => 'Reclusión y Readaptación Social', '124' => 'Derechos Humanos']],
                '13' => ['Coordinación de la Política de Gobierno', ['131' => 'Presidencia / Gubernatura', '132' => 'Política Interior', '133' => 'Preservación y Cuidado del Patrimonio Público', '134' => 'Función Pública', '135' => 'Asuntos Jurídicos', '136' => 'Organización de Procesos Electorales', '137' => 'Población', '138' => 'Territorio', '139' => 'Otros']],
                '14' => ['Relaciones Exteriores', ['141' => 'Relaciones Exteriores']],
                '15' => ['Asuntos Financieros y Hacendarios', ['151' => 'Asuntos Financieros', '152' => 'Asuntos Hacendarios']],
                '16' => ['Seguridad Nacional', ['161' => 'Defensa', '162' => 'Marina', '163' => 'Inteligencia para la Preservación de la Seguridad Nacional']],
                '17' => ['Asuntos de Orden Público y de Seguridad Interior', ['171' => 'Policía', '172' => 'Protección Civil', '173' => 'Otros Asuntos de Orden Público y Seguridad', '174' => 'Sistema Nacional de Seguridad Pública']],
                '18' => ['Otros Servicios Generales', ['181' => 'Servicios Registrales, Administrativos y Patrimoniales', '182' => 'Servicios Estadísticos', '183' => 'Servicios de Comunicación y Medios', '184' => 'Acceso a la Información Pública Gubernamental', '185' => 'Otros']],
            ]],
            '2' => ['Desarrollo Social', [
                '21' => ['Protección Ambiental', ['211' => 'Ordenación de Desechos', '212' => 'Administración del Agua', '213' => 'Ordenación de Aguas Residuales, Drenaje y Alcantarillado', '214' => 'Reducción de la Contaminación', '215' => 'Protección de la Diversidad Biológica y del Paisaje', '216' => 'Otros de Protección Ambiental']],
                '22' => ['Vivienda y Servicios a la Comunidad', ['221' => 'Urbanización', '222' => 'Desarrollo Comunitario', '223' => 'Abastecimiento de Agua', '224' => 'Alumbrado Público', '225' => 'Vivienda', '226' => 'Servicios Comunales', '227' => 'Desarrollo Regional']],
                '23' => ['Salud', ['231' => 'Prestación de Servicios de Salud a la Comunidad', '232' => 'Prestación de Servicios de Salud a la Persona', '233' => 'Generación de Recursos para la Salud', '234' => 'Rectoría del Sistema de Salud', '235' => 'Protección Social en Salud']],
                '24' => ['Recreación, Cultura y Otras Manifestaciones Sociales', ['241' => 'Deporte y Recreación', '242' => 'Cultura', '243' => 'Radio, Televisión y Editoriales', '244' => 'Asuntos Religiosos y Otras Manifestaciones Sociales']],
                '25' => ['Educación', ['251' => 'Educación Básica', '252' => 'Educación Media Superior', '253' => 'Educación Superior', '254' => 'Posgrado', '255' => 'Educación para Adultos', '256' => 'Otros Servicios Educativos y Actividades Inherentes']],
                '26' => ['Protección Social', ['261' => 'Enfermedad e Incapacidad', '262' => 'Edad Avanzada', '263' => 'Familia e Hijos', '264' => 'Desempleo', '265' => 'Alimentación y Nutrición', '266' => 'Apoyo Social para la Vivienda', '267' => 'Indígenas', '268' => 'Otros Grupos Vulnerables', '269' => 'Otros de Seguridad Social y Asistencia Social']],
                '27' => ['Otros Asuntos Sociales', ['271' => 'Otros Asuntos Sociales']],
            ]],
            '3' => ['Desarrollo Económico', [
                '31' => ['Asuntos Económicos, Comerciales y Laborales en General', ['311' => 'Asuntos Económicos y Comerciales en General', '312' => 'Asuntos Laborales Generales']],
                '32' => ['Agropecuaria, Silvicultura, Pesca y Caza', ['321' => 'Agropecuaria', '322' => 'Silvicultura', '323' => 'Acuacultura, Pesca y Caza', '324' => 'Agroindustrial', '325' => 'Hidroagrícola', '326' => 'Apoyo Financiero a la Banca y Seguro Agropecuario']],
                '33' => ['Combustibles y Energía', ['331' => 'Carbón y Otros Combustibles Minerales Sólidos', '332' => 'Petróleo y Gas Natural (Hidrocarburos)', '333' => 'Combustibles Nucleares', '334' => 'Otros Combustibles', '335' => 'Electricidad', '336' => 'Energía no Eléctrica']],
                '34' => ['Minería, Manufacturas y Construcción', ['341' => 'Extracción de Recursos Minerales Excepto los Combustibles Minerales', '342' => 'Manufacturas', '343' => 'Construcción']],
                '35' => ['Transporte', ['351' => 'Transporte por Carretera', '352' => 'Transporte por Agua y Puertos', '353' => 'Transporte por Ferrocarril', '354' => 'Transporte Aéreo', '355' => 'Transporte por Oleoductos y Gasoductos y Otros Sistemas de Transporte', '356' => 'Otros Relacionados con Transporte']],
                '36' => ['Comunicaciones', ['361' => 'Comunicaciones']],
                '37' => ['Turismo', ['371' => 'Turismo', '372' => 'Hoteles y Restaurantes']],
                '38' => ['Ciencia, Tecnología e Innovación', ['381' => 'Investigación Científica', '382' => 'Desarrollo Tecnológico', '383' => 'Servicios Científicos y Tecnológicos', '384' => 'Innovación']],
                '39' => ['Otras Industrias y Otros Asuntos Económicos', ['391' => 'Comercio, Distribución, Almacenamiento y Depósito', '392' => 'Otras Industrias', '393' => 'Otros Asuntos Económicos']],
            ]],
            '4' => ['Otras No Clasificadas en Funciones Anteriores', [
                '41' => ['Transacciones de la Deuda Pública / Costo Financiero de la Deuda', ['411' => 'Deuda Pública Interna', '412' => 'Deuda Pública Externa']],
                '42' => ['Transferencias, Participaciones y Aportaciones entre Diferentes Niveles y Órdenes de Gobierno', ['421' => 'Transferencias entre Diferentes Niveles y Órdenes de Gobierno', '422' => 'Participaciones entre Diferentes Niveles y Órdenes de Gobierno', '423' => 'Aportaciones entre Diferentes Niveles y Órdenes de Gobierno']],
                '43' => ['Saneamiento del Sistema Financiero', ['431' => 'Saneamiento del Sistema Financiero', '432' => 'Apoyos IPAB', '433' => 'Banca de Desarrollo', '434' => 'Apoyo a los Programas de Reestructura en Unidades de Inversión (UDIS)']],
                '44' => ['Adeudos de Ejercicios Fiscales Anteriores', ['441' => 'Adeudos de Ejercicios Fiscales Anteriores']],
            ]],
        ];
        foreach ($catalogo as $finClave => [$finNombre, $funciones]) {
            $finalidad = ClasificacionFuncional::firstOrCreate(
                ['nivel' => 'finalidad', 'clave' => $finClave],
                ['nombre' => $finNombre, 'padre_id' => null],
            );

            foreach ($funciones as $funClave => [$funNombre, $subfunciones]) {
                $funcion = ClasificacionFuncional::firstOrCreate(
                    ['nivel' => 'funcion', 'clave' => $funClave],
                    ['nombre' => $funNombre, 'padre_id' => $finalidad->id],
                );

                foreach ($subfunciones as $subClave => $subNombre) {
                    ClasificacionFuncional::firstOrCreate(
                        ['nivel' => 'subfuncion', 'clave' => $subClave],
                        ['nombre' => $subNombre, 'padre_id' => $funcion->id],
                    );
                }
            }
        }
    }
}
