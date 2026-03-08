<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QaTestingSeeder extends Seeder
{
    public function run(): void
    {
        // Guardia de seguridad: NUNCA correr en producción
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar QaTestingSeeder en producción.');
            return;
        }

        $this->command->info('Iniciando carga de datos QA Testing...');

        // Verificar que las URs de DesarrolloSeeder existen
        $se = Team::where('clave_ur', 'SE-001')->firstOrFail();
        $ss = Team::where('clave_ur', 'SS-002')->firstOrFail();

        $this->crearUsuarios($se, $ss);
        $this->crearProgramasYMir($se, $ss);
        $this->crearIndicadores();
        $this->crearMetaPeriodos();
        $this->crearAvances();
        $this->generarResultadosEsperados();

        $this->command->info('QaTestingSeeder completado.');
    }

    private function crearUsuarios(Team $se, Team $ss): void
    {
        $password = Hash::make('LseRdlP0P');

        $usuarios = [
            [
                'name'  => 'QA Admin',
                'email' => 'ele.admin@gmail.com',
                'role'  => SystemRole::ADMIN,
                'teams' => [$se, $ss],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Planeador',
                'email' => 'ele.planeador@gmail.com',
                'role'  => SystemRole::PLANEADOR,
                'teams' => [$se],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Operador',
                'email' => 'ele.operador@gmail.com',
                'role'  => SystemRole::OPERADOR,
                'teams' => [$se],
                'team_role' => 'operador',
            ],
            [
                'name'  => 'QA Planeador 2',
                'email' => 'ele.planeador2@gmail.com',
                'role'  => SystemRole::PLANEADOR,
                'teams' => [$se],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Revisor',
                'email' => 'ele.revisor@gmail.com',
                'role'  => SystemRole::PLANEADOR,
                'teams' => [$ss],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Operador 2',
                'email' => 'ele.operador2@gmail.com',
                'role'  => SystemRole::OPERADOR,
                'teams' => [$ss],
                'team_role' => 'operador',
            ],
        ];

        $tableRows = [];

        foreach ($usuarios as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => $password,
                ]
            );

            // Asignar rol Spatie (idempotente por naturaleza)
            if (! $user->hasRole($data['role']->value)) {
                $user->assignRole($data['role']->value);
            }

            // Asignar a equipos
            foreach ($data['teams'] as $team) {
                $team->users()->syncWithoutDetaching([
                    $user->id => ['role' => $data['team_role']],
                ]);
            }

            // Establecer equipo activo (el primero de la lista)
            $user->forceFill(['current_team_id' => $data['teams'][0]->id])->save();

            $teamNames = collect($data['teams'])->pluck('clave_ur')->implode(', ');
            $tableRows[] = [$data['name'], $data['email'], $data['role']->value, $teamNames];
        }

        $this->command->table(
            ['Usuario', 'Email', 'Rol', 'UR(s)'],
            $tableRows
        );
        $this->command->info('Contraseña para todos: LseRdlP0P');
    }

    private function crearProgramasYMir(Team $se, Team $ss): void
    {
        $this->crearPrograma1FomentoEconomico($se);
        $this->crearPrograma2DesarrolloProductivo($se);
        $this->crearPrograma3SaludPreventiva($ss);

        $this->command->info('Programas y niveles MIR creados.');
    }

    private function crearPrograma1FomentoEconomico(Team $se): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::firstOrCreate(
            ['clave' => 'FER-001', 'team_id' => $se->id],
            ['nombre' => 'Fomento Económico Regional', 'ejercicio_fiscal' => 2026]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Contribuir al incremento del producto interno bruto estatal mediante el fortalecimiento del tejido empresarial y la generación de empleo formal en la región.',
                'supuestos' => 'Las condiciones macroeconómicas del país se mantienen estables y permiten el crecimiento de las MiPyMEs.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Las micro, pequeñas y medianas empresas del estado incrementan su productividad y competitividad mediante apoyos financieros, capacitación técnica y acceso a mercados.',
                'supuestos' => 'Las MiPyMEs participan activamente en los programas de apoyo y aplican los conocimientos adquiridos.',
                'team_id' => $se->id,
            ]
        );

        $comp1 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Créditos y financiamientos otorgados a MiPyMEs para capital de trabajo e inversión productiva.',
                'supuestos' => 'Existe demanda suficiente de créditos por parte de las MiPyMEs y los recursos presupuestarios se liberan oportunamente.',
                'team_id' => $se->id,
            ]
        );

        $comp2 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 2],
            [
                'resumen_narrativo' => 'Programas de capacitación técnica y empresarial impartidos a emprendedores y empresarios.',
                'supuestos' => 'Los emprendedores y empresarios asisten a los cursos programados y completan la capacitación.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Evaluar y dictaminar solicitudes de crédito presentadas por MiPyMEs del estado.',
                'supuestos' => 'Las solicitudes cumplen con los requisitos documentales establecidos en las reglas de operación.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Dispersar los recursos financieros aprobados y dar seguimiento a la correcta aplicación.',
                'supuestos' => 'Los beneficiarios proporcionan la documentación bancaria necesaria para la dispersión.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 3, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Diseñar e implementar cursos de capacitación en habilidades empresariales y digitales.',
                'supuestos' => 'Se cuenta con instructores calificados y espacios adecuados para la impartición de cursos.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 4, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Realizar ferias de vinculación comercial para conectar productores con mercados nacionales e internacionales.',
                'supuestos' => 'Los compradores potenciales confirman su participación en las ferias de vinculación.',
                'team_id' => $se->id,
            ]
        );

        return $programa;
    }

    private function crearPrograma2DesarrolloProductivo(Team $se): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::firstOrCreate(
            ['clave' => 'DP-002', 'team_id' => $se->id],
            ['nombre' => 'Desarrollo Productivo', 'ejercicio_fiscal' => 2026]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Hacer que la economía mejore y que haya más desarrollo en el estado para todos.',
                'supuestos' => 'El entorno económico general no presenta crisis que impidan el desarrollo.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Los ciudadanos tienen más cosas buenas gracias a los apoyos que da el gobierno.',
                'supuestos' => 'Los ciudadanos se enteran de los programas de apoyo disponibles.',
                'team_id' => $se->id,
            ]
        );

        $comp1 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Infraestructura vial construida y modernizada en zonas rurales del estado.',
                'supuestos' => 'Los permisos de construcción y derechos de vía se obtienen en tiempo.',
                'team_id' => $se->id,
            ]
        );

        $comp2 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 2],
            [
                'resumen_narrativo' => 'Apoyos económicos entregados a productores agrícolas del estado.',
                'supuestos' => 'Los productores agrícolas presentan solicitudes dentro del periodo establecido.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Impartir talleres de capacitación en ventas y marketing digital a emprendedores.',
                'supuestos' => 'Los emprendedores se inscriben en los talleres de capacitación.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Realizar estudios topográficos para proyectos de pavimentación.',
                'supuestos' => 'Se cuenta con el equipo topográfico necesario y personal técnico capacitado.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 3, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Recibir y evaluar solicitudes de apoyo económico de productores.',
                'supuestos' => 'Las solicitudes se reciben completas y con la documentación requerida.',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 4, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Entregar insumos agrícolas y dar seguimiento a su uso productivo.',
                'supuestos' => 'Los proveedores de insumos agrícolas cumplen con los tiempos de entrega.',
                'team_id' => $se->id,
            ]
        );

        return $programa;
    }

    private function crearPrograma3SaludPreventiva(Team $ss): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::firstOrCreate(
            ['clave' => 'SP-003', 'team_id' => $ss->id],
            ['nombre' => 'Salud Preventiva Comunitaria', 'ejercicio_fiscal' => 2026]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Contribuir a la reducción de la morbilidad por enfermedades prevenibles en la población del estado mediante acciones de salud preventiva.',
                'supuestos' => 'Las políticas nacionales de salud se mantienen alineadas con las estrategias estatales de prevención.',
                'team_id' => $ss->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'La población objetivo recibe servicios integrales de salud preventiva, incluyendo vacunación, detección oportuna y educación para la salud.',
                'supuestos' => 'La población objetivo acude a los centros de salud y participa en las campañas preventivas.',
                'team_id' => $ss->id,
            ]
        );

        $comp1 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Campañas de vacunación y detección oportuna realizadas en comunidades prioritarias.',
                'supuestos' => 'Se cuenta con el suministro oportuno de vacunas e insumos médicos para las campañas.',
                'team_id' => $ss->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Organizar y ejecutar jornadas de vacunación en las 12 jurisdicciones sanitarias del estado.',
                'supuestos' => 'El personal de salud está disponible y capacitado para las jornadas de vacunación.',
                'team_id' => $ss->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Realizar tamizajes de detección oportuna de diabetes, hipertensión y cáncer en población mayor de 40 años.',
                'supuestos' => 'La población mayor de 40 años acude a los tamizajes y autoriza la realización de estudios clínicos.',
                'team_id' => $ss->id,
            ]
        );

        return $programa;
    }

    private function crearIndicadores(): void
    {
        // Stub — se implementará en Task 4
    }

    private function crearMetaPeriodos(): void
    {
        // Stub — se implementará en Task 5
    }

    private function crearAvances(): void
    {
        // Stub — se implementará en Task 6
    }

    private function generarResultadosEsperados(): void
    {
        // Stub — se implementará en Task 7
    }
}
