<?php

namespace Database\Seeders;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\SystemRole;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\Mml\MetaPeriodo;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Services\Tracking\SemaforoService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class QaTestingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar QaTestingSeeder en producción.');
            return;
        }

        $this->command->info('Iniciando carga de datos QA Testing...');

        $se     = Team::where('clave_ur', 'SE-001')->firstOrFail();
        $ss     = Team::where('clave_ur', 'SS-002')->firstOrFail();
        $seg    = Team::where('clave_ur', 'SEG-003')->firstOrFail();
        $sectur = Team::where('clave_ur', 'SECTUR-004')->firstOrFail();

        $this->crearUsuarios($se, $ss, $seg, $sectur);
        $this->crearProgramasYMir($se, $ss, $seg, $sectur);
        $this->crearIndicadores();
        $this->crearMetaPeriodos();
        $this->crearAvances();
        $this->generarResultadosEsperados();

        $this->command->info('QaTestingSeeder completado.');
    }

    private function crearUsuarios(Team $se, Team $ss, Team $seg, Team $sectur): void
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
            [
                'name'  => 'QA Planeador SECTUR',
                'email' => 'ele.planeador.sectur@gmail.com',
                'role'  => SystemRole::PLANEADOR,
                'teams' => [$sectur],
                'team_role' => 'planeador',
            ],
            [
                'name'  => 'QA Operador SECTUR',
                'email' => 'ele.operador.sectur@gmail.com',
                'role'  => SystemRole::OPERADOR,
                'teams' => [$sectur],
                'team_role' => 'operador',
            ],
            // ele.leader@gmail.com — NO seedear, crear via flujo de invitación
            // [
            //     'name'  => 'QA Leader',
            //     'email' => 'ele.leader@gmail.com',
            //     'role'  => SystemRole::PLANEADOR,
            //     'teams' => [$se],
            //     'team_role' => 'planeador',
            // ],
        ];

        $tableRows = [];

        foreach ($usuarios as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => $password,
                    'email_verified_at' => now(),
                    'activated_at'      => now(),
                    'active'            => true,
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

    private function crearProgramasYMir(Team $se, Team $ss, Team $seg, Team $sectur): void
    {
        $this->crearPrograma1ImpulsoMezcalero($se, $sectur);
        $this->crearPrograma2PrevencionCronicas($ss);
        $this->crearPrograma3SeguridadPublica($seg);
        $this->crearPrograma4DestinosTuristicos($sectur);

        $this->command->info('Programas y niveles MIR creados.');
    }

    private function crearPrograma1ImpulsoMezcalero(Team $se, Team $sectur): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::firstOrCreate(
            ['clave' => 'ISM-001', 'team_id' => $se->id],
            ['nombre' => 'Impulso al Sector Mezcalero', 'ejercicio_fiscal' => 2025]
        );

        $programa->equipos()->syncWithoutDetaching([
            $se->id => ['rol' => 'coordinadora'],
            $sectur->id => ['rol' => 'coadyuvante'],
        ]);

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Contribuir al desarrollo económico del Estado mediante el fortalecimiento de cadenas productivas estratégicas',
                'supuestos' => 'Las condiciones macroeconómicas estatales y nacionales permiten el crecimiento del sector agroindustrial',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Productores de mezcal del Estado incrementan sus ventas nacionales e internacionales mediante subsidios de equipamiento y certificaciones turísticas',
                'supuestos' => 'Los productores de mezcal participan activamente en los programas de subsidio y certificación',
                'team_id' => $se->id,
            ]
        );

        $comp1 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Subsidios para equipamiento productivo entregados a productores de mezcal',
                'supuestos' => 'Existe demanda suficiente de subsidios y los recursos presupuestarios se liberan oportunamente',
                'team_id' => $se->id,
            ]
        );

        $comp2 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 2],
            [
                'resumen_narrativo' => 'Certificaciones de "Ruta del Mezcal" otorgadas a palenques como destino turístico',
                'supuestos' => 'Los palenques cumplen con los requisitos mínimos de infraestructura y seguridad para recibir visitantes',
                'team_id' => $sectur->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Recepción y evaluación de solicitudes de subsidio para equipamiento productivo',
                'supuestos' => 'Las solicitudes cumplen con los requisitos documentales establecidos en las reglas de operación',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Entrega y verificación de equipos productivos instalados en palenques',
                'supuestos' => 'Los proveedores de equipos cumplen con los tiempos de entrega acordados',
                'team_id' => $se->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Inspección de palenques para certificación de uso turístico',
                'supuestos' => 'Los propietarios de palenques permiten el acceso para la inspección y proporcionan la documentación requerida',
                'team_id' => $sectur->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Capacitación a productores de mezcal en atención al visitante y turista',
                'supuestos' => 'Los productores asisten a las sesiones de capacitación programadas',
                'team_id' => $sectur->id,
            ]
        );

        return $programa;
    }

    private function crearPrograma2PrevencionCronicas(Team $ss): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::firstOrCreate(
            ['clave' => 'PEC-002', 'team_id' => $ss->id],
            ['nombre' => 'Prevención de Enfermedades Crónicas', 'ejercicio_fiscal' => 2025]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Contribuir a la reducción de la mortalidad por enfermedades crónico-degenerativas en el Estado',
                'supuestos' => 'Las políticas nacionales de salud se mantienen alineadas con las estrategias estatales de prevención',
                'team_id' => $ss->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'La población en riesgo del Estado adopta hábitos de prevención de enfermedades crónicas mediante detección temprana y educación nutricional',
                'supuestos' => 'La población objetivo acude a los centros de salud y participa en las campañas preventivas',
                'team_id' => $ss->id,
            ]
        );

        $comp1 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Detecciones tempranas de diabetes e hipertensión realizadas en comunidades prioritarias',
                'supuestos' => 'Se cuenta con el suministro oportuno de insumos médicos y reactivos para las detecciones',
                'team_id' => $ss->id,
            ]
        );

        $comp2 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 2],
            [
                'resumen_narrativo' => 'Programas de alimentación saludable implementados en escuelas de zonas prioritarias',
                'supuestos' => 'Las autoridades educativas autorizan la implementación de los programas en los planteles',
                'team_id' => $ss->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Realización de jornadas de detección en comunidades prioritarias',
                'supuestos' => 'El personal de salud está disponible y capacitado para las jornadas de detección',
                'team_id' => $ss->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Seguimiento clínico a pacientes detectados con factores de riesgo',
                'supuestos' => 'Los pacientes detectados autorizan su seguimiento y acuden a las citas programadas',
                'team_id' => $ss->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Capacitación a personal docente en nutrición preventiva',
                'supuestos' => 'Los docentes asisten a las capacitaciones durante los periodos establecidos',
                'team_id' => $ss->id,
            ]
        );

        return $programa;
    }

    private function crearPrograma3SeguridadPublica(Team $seg): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::firstOrCreate(
            ['clave' => 'FSP-003', 'team_id' => $seg->id],
            ['nombre' => 'Fortalecimiento de la Seguridad Pública Municipal', 'ejercicio_fiscal' => 2025]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Contribuir a la disminución de la incidencia delictiva en el Estado',
                'supuestos' => 'Las políticas federales de seguridad se mantienen coordinadas con las estrategias estatales',
                'team_id' => $seg->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Los municipios del Estado fortalecen sus capacidades operativas de seguridad pública',
                'supuestos' => 'Los municipios colaboran activamente en la implementación de los programas de fortalecimiento',
                'team_id' => $seg->id,
            ]
        );

        $comp1 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Equipamiento policial entregado a corporaciones municipales',
                'supuestos' => 'Los procesos de adquisición se realizan conforme a la normatividad y los tiempos establecidos',
                'team_id' => $seg->id,
            ]
        );

        $comp2 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 2],
            [
                'resumen_narrativo' => 'Operativos de seguridad coordinados realizados en zonas prioritarias',
                'supuestos' => 'Las corporaciones municipales disponen de personal suficiente para participar en los operativos',
                'team_id' => $seg->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Adquisición y distribución de chalecos balísticos y radios de comunicación',
                'supuestos' => 'Los proveedores cumplen con las especificaciones técnicas y los tiempos de entrega',
                'team_id' => $seg->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Capacitación en uso de equipo táctico a elementos policiales municipales',
                'supuestos' => 'Los elementos policiales cumplen con los requisitos de evaluación de control de confianza',
                'team_id' => $seg->id,
            ]
        );

        // Act 2.1: deliberate defect — verb in infinitive, wrong supuestos
        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Hacer patrullajes coordinados intermunicipales',
                'supuestos' => 'Reportes internos de la dirección',
                'team_id' => $seg->id,
                'sintaxis_valida' => false,
                'sintaxis_observacion' => 'El resumen narrativo inicia con verbo en infinitivo. Debe expresarse como sustantivo derivado (ej. "Realización de patrullajes coordinados intermunicipales").',
                'sintaxis_sugerencia' => 'Realización de patrullajes coordinados intermunicipales',
                'sintaxis_validada_at' => now(),
            ]
        );

        return $programa;
    }

    private function crearPrograma4DestinosTuristicos(Team $sectur): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::firstOrCreate(
            ['clave' => 'DDT-004', 'team_id' => $sectur->id],
            ['nombre' => 'Desarrollo de Destinos Turísticos Sustentables', 'ejercicio_fiscal' => 2025]
        );

        // Fin: deliberate defect — null supuestos
        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Contribuir al posicionamiento turístico del Estado',
                'supuestos' => null,
                'team_id' => $sectur->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Destinos turísticos del Estado son competitivos y sustentables',
                'supuestos' => 'Los destinos turísticos mantienen condiciones de seguridad adecuadas para los visitantes',
                'team_id' => $sectur->id,
            ]
        );

        $comp1 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
            [
                'resumen_narrativo' => 'Infraestructura turística rehabilitada en destinos prioritarios',
                'supuestos' => 'Los permisos de construcción y derechos de vía se obtienen en tiempo',
                'team_id' => $sectur->id,
            ]
        );

        $comp2 = MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 2],
            [
                'resumen_narrativo' => 'Campañas de promoción turística ejecutadas en medios nacionales e internacionales',
                'supuestos' => 'Las agencias de publicidad entregan los materiales en los tiempos acordados',
                'team_id' => $sectur->id,
            ]
        );

        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
            [
                'resumen_narrativo' => 'Elaboración de proyectos ejecutivos de rehabilitación de infraestructura turística',
                'supuestos' => 'Los municipios proporcionan la información técnica necesaria para los proyectos',
                'team_id' => $sectur->id,
            ]
        );

        // Act 2.1: deliberate defect — null supuestos
        MirNivel::firstOrCreate(
            ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp2->id],
            [
                'resumen_narrativo' => 'Diseño y lanzamiento de campañas de promoción digital',
                'supuestos' => null,
                'team_id' => $sectur->id,
            ]
        );

        return $programa;
    }

    private function crearIndicadores(): void
    {
        // --- P1: ISM-001 ---
        $prog1 = ProgramaPresupuestario::where('clave', 'ISM-001')->first();
        $fin1 = MirNivel::where('programa_presupuestario_id', $prog1->id)->where('tipo_nivel', 'fin')->first();
        $prop1 = MirNivel::where('programa_presupuestario_id', $prog1->id)->where('tipo_nivel', 'proposito')->first();
        $comps1 = MirNivel::where('programa_presupuestario_id', $prog1->id)->where('tipo_nivel', 'componente')->orderBy('orden')->get();
        $acts1c1 = MirNivel::where('programa_presupuestario_id', $prog1->id)->where('tipo_nivel', 'actividad')->where('componente_id', $comps1[0]->id)->orderBy('orden')->get();
        $acts1c2 = MirNivel::where('programa_presupuestario_id', $prog1->id)->where('tipo_nivel', 'actividad')->where('componente_id', $comps1[1]->id)->orderBy('orden')->get();

        $indicadoresP1 = [
            [$fin1->id, 'Tasa de crecimiento del PIB del sector agroindustrial', 'estrategico', 'eficacia', 'bianual', 'ascendente', 3.5, 2.1],
            [$prop1->id, 'Porcentaje de variación en ventas de productores beneficiados', 'estrategico', 'eficacia', 'anual', 'ascendente', 15.0, 8.0],
            [$comps1[0]->id, 'Porcentaje de subsidios otorgados respecto a solicitudes aprobadas', 'gestion', 'eficacia', 'trimestral', 'ascendente', 85.0, null],
            [$comps1[1]->id, 'Número de palenques certificados como destino turístico', 'gestion', 'eficacia', 'semestral', 'ascendente', 24.0, 6.0],
            [$acts1c1[0]->id, 'Porcentaje de solicitudes evaluadas en plazo', 'gestion', 'eficiencia', 'trimestral', 'ascendente', 90.0, null],
            [$acts1c1[1]->id, 'Número de verificaciones de instalación realizadas', 'gestion', 'eficacia', 'mensual', 'ascendente', 120.0, null],
            [$acts1c2[0]->id, 'Número de inspecciones de palenques realizadas', 'gestion', 'eficacia', 'trimestral', 'ascendente', 48.0, null],
            [$acts1c2[1]->id, 'Índice de satisfacción de productores capacitados', 'gestion', 'calidad', 'trimestral', 'regular', 8.5, 7.2],
        ];

        foreach ($indicadoresP1 as $ind) {
            $extra = [];
            if ($ind[1] === 'Índice de satisfacción de productores capacitados') {
                $extra = [
                    'rango_verde_min' => 7.5,
                    'rango_verde_max' => 9.5,
                    'rango_amarillo_min' => 6.0,
                    'rango_amarillo_max' => 10.0,
                ];
            }
            Indicador::firstOrCreate(
                ['mir_nivel_id' => $ind[0], 'nombre' => $ind[1]],
                array_merge([
                    'tipo' => $ind[2], 'dimension' => $ind[3], 'frecuencia' => $ind[4],
                    'sentido' => $ind[5], 'meta' => $ind[6], 'linea_base' => $ind[7],
                    'activo_seguimiento' => true, 'orden' => 1,
                ], $extra)
            );
        }

        // --- P2: PEC-002 ---
        $prog2 = ProgramaPresupuestario::where('clave', 'PEC-002')->first();
        $fin2 = MirNivel::where('programa_presupuestario_id', $prog2->id)->where('tipo_nivel', 'fin')->first();
        $prop2 = MirNivel::where('programa_presupuestario_id', $prog2->id)->where('tipo_nivel', 'proposito')->first();
        $comps2 = MirNivel::where('programa_presupuestario_id', $prog2->id)->where('tipo_nivel', 'componente')->orderBy('orden')->get();
        $acts2c1 = MirNivel::where('programa_presupuestario_id', $prog2->id)->where('tipo_nivel', 'actividad')->where('componente_id', $comps2[0]->id)->orderBy('orden')->get();
        $acts2c2 = MirNivel::where('programa_presupuestario_id', $prog2->id)->where('tipo_nivel', 'actividad')->where('componente_id', $comps2[1]->id)->orderBy('orden')->get();

        $indicadoresP2 = [
            [$fin2->id, 'Tasa de mortalidad por diabetes mellitus tipo 2 por cada 100,000 hab', 'estrategico', 'eficacia', 'anual', 'descendente', 45.0, 52.3],
            [$prop2->id, 'Porcentaje de población atendida que mejora indicadores biométricos a 12 meses', 'estrategico', 'eficacia', 'anual', 'ascendente', 35.0, 22.0],
            [$comps2[0]->id, 'Porcentaje de detecciones realizadas respecto a la meta programada', 'gestion', 'eficacia', 'trimestral', 'ascendente', 90.0, null],
            [$comps2[1]->id, 'Porcentaje de escuelas con programa activo respecto al universo objetivo', 'gestion', 'eficacia', 'semestral', 'ascendente', 60.0, 25.0],
            [$acts2c1[0]->id, 'Número de jornadas de detección realizadas', 'gestion', 'eficacia', 'mensual', 'ascendente', 240.0, null],
            [$acts2c1[1]->id, 'Porcentaje de pacientes con seguimiento completo a 3 meses', 'gestion', 'calidad', 'trimestral', 'ascendente', 80.0, 65.0],
            [$acts2c2[0]->id, 'Número de docentes capacitados en nutrición preventiva', 'gestion', 'eficacia', 'trimestral', 'ascendente', 500.0, null],
        ];

        foreach ($indicadoresP2 as $ind) {
            Indicador::firstOrCreate(
                ['mir_nivel_id' => $ind[0], 'nombre' => $ind[1]],
                [
                    'tipo' => $ind[2], 'dimension' => $ind[3], 'frecuencia' => $ind[4],
                    'sentido' => $ind[5], 'meta' => $ind[6], 'linea_base' => $ind[7],
                    'activo_seguimiento' => true, 'orden' => 1,
                ]
            );
        }

        // --- P3: FSP-003 ---
        $prog3 = ProgramaPresupuestario::where('clave', 'FSP-003')->first();
        $fin3 = MirNivel::where('programa_presupuestario_id', $prog3->id)->where('tipo_nivel', 'fin')->first();
        $prop3 = MirNivel::where('programa_presupuestario_id', $prog3->id)->where('tipo_nivel', 'proposito')->first();
        $comps3 = MirNivel::where('programa_presupuestario_id', $prog3->id)->where('tipo_nivel', 'componente')->orderBy('orden')->get();
        $acts3c1 = MirNivel::where('programa_presupuestario_id', $prog3->id)->where('tipo_nivel', 'actividad')->where('componente_id', $comps3[0]->id)->orderBy('orden')->get();
        $acts3c2 = MirNivel::where('programa_presupuestario_id', $prog3->id)->where('tipo_nivel', 'actividad')->where('componente_id', $comps3[1]->id)->orderBy('orden')->get();

        $indicadoresP3 = [
            [$fin3->id, 'Tasa de incidencia delictiva por cada 100,000 hab', 'estrategico', 'eficacia', 'anual', 'descendente', 1200.0, 1450.0],
            [$prop3->id, 'Porcentaje de municipios que cumplen el estándar mínimo de operación policial', 'estrategico', 'eficacia', 'semestral', 'ascendente', 70.0, 35.0],
            [$comps3[0]->id, 'Porcentaje de equipamiento entregado respecto al programado', 'gestion', 'eficacia', 'trimestral', 'ascendente', 100.0, null],
            [$comps3[1]->id, 'Promedio de operativos mensuales realizados por municipio', 'gestion', 'eficiencia', 'trimestral', 'ascendente', 8.0, 3.0],
            [$acts3c1[0]->id, 'Número de equipos distribuidos (chalecos y radios)', 'gestion', 'eficacia', 'trimestral', 'ascendente', 800.0, null],
            [$acts3c1[1]->id, 'Porcentaje de elementos capacitados del total asignado', 'gestion', 'eficacia', 'trimestral', 'ascendente', 85.0, null],
            [$acts3c2[0]->id, 'Número de patrullajes coordinados realizados', 'gestion', 'eficacia', 'mensual', 'ascendente', 960.0, null],
        ];

        foreach ($indicadoresP3 as $ind) {
            Indicador::firstOrCreate(
                ['mir_nivel_id' => $ind[0], 'nombre' => $ind[1]],
                [
                    'tipo' => $ind[2], 'dimension' => $ind[3], 'frecuencia' => $ind[4],
                    'sentido' => $ind[5], 'meta' => $ind[6], 'linea_base' => $ind[7],
                    'activo_seguimiento' => true, 'orden' => 1,
                ]
            );
        }

        // --- P4: DDT-004 ---
        $prog4 = ProgramaPresupuestario::where('clave', 'DDT-004')->first();
        $fin4 = MirNivel::where('programa_presupuestario_id', $prog4->id)->where('tipo_nivel', 'fin')->first();
        $prop4 = MirNivel::where('programa_presupuestario_id', $prog4->id)->where('tipo_nivel', 'proposito')->first();
        $comps4 = MirNivel::where('programa_presupuestario_id', $prog4->id)->where('tipo_nivel', 'componente')->orderBy('orden')->get();
        $acts4c1 = MirNivel::where('programa_presupuestario_id', $prog4->id)->where('tipo_nivel', 'actividad')->where('componente_id', $comps4[0]->id)->orderBy('orden')->get();
        $acts4c2 = MirNivel::where('programa_presupuestario_id', $prog4->id)->where('tipo_nivel', 'actividad')->where('componente_id', $comps4[1]->id)->orderBy('orden')->get();

        $indicadoresP4 = [
            [$fin4->id, 'Porcentaje de cosas buenas del turismo', 'estrategico', 'eficacia', 'sexenal', 'ascendente', 50.0, null],
            [$prop4->id, 'Número de visitantes nacionales e internacionales', 'estrategico', 'eficacia', 'trimestral', 'ascendente', 500000.0, 320000.0],
            [$comps4[0]->id, 'Porcentaje de proyectos de rehabilitación completados', 'gestion', 'eficacia', 'trimestral', 'ascendente', 100.0, null],
            [$comps4[1]->id, 'Número de campañas de promoción lanzadas', 'gestion', 'economia', 'semestral', 'ascendente', 6.0, 4.0],
            [$acts4c1[0]->id, 'Razón de proyectos aprobados vs presentados', 'gestion', 'eficiencia', 'trimestral', 'ascendente', 0.8, 0.5],
            [$acts4c2[0]->id, 'Proporción de alcance digital', 'gestion', 'eficacia', 'mensual', 'ascendente', 0.0, null],
        ];

        foreach ($indicadoresP4 as $ind) {
            Indicador::firstOrCreate(
                ['mir_nivel_id' => $ind[0], 'nombre' => $ind[1]],
                [
                    'tipo' => $ind[2], 'dimension' => $ind[3], 'frecuencia' => $ind[4],
                    'sentido' => $ind[5], 'meta' => $ind[6], 'linea_base' => $ind[7],
                    'activo_seguimiento' => true, 'orden' => 1,
                ]
            );
        }

        $this->command->info('Indicadores creados (28 total).');
    }

    private function crearMetaPeriodos(): void
    {
        $indicadores = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->whereIn('clave', ['ISM-001', 'PEC-002', 'FSP-003', 'DDT-004']))->get();

        foreach ($indicadores as $indicador) {
            foreach ([2025, 2026] as $year) {
                $periodos = $this->generarPeriodosParaIndicador($indicador, $year);

                // For 2026, limit to Q1 only
                if ($year === 2026) {
                    $periodos = array_filter($periodos, function ($p) {
                        return Carbon::parse($p['fecha_cierre'])->month <= 3;
                    });
                }

                foreach ($periodos as $periodo) {
                    MetaPeriodo::firstOrCreate(
                        [
                            'indicador_id' => $indicador->id,
                            'periodo' => $periodo['periodo'],
                            'ejercicio_fiscal' => $year,
                        ],
                        [
                            'meta_periodo' => $periodo['meta_periodo'],
                            'fecha_apertura' => $periodo['fecha_apertura'],
                            'fecha_cierre' => $periodo['fecha_cierre'],
                            'activo' => true,
                        ]
                    );
                }
            }
        }

        $total = MetaPeriodo::whereHas('indicador.mirNivel.programa', fn ($q) => $q->whereIn('clave', ['ISM-001', 'PEC-002', 'FSP-003', 'DDT-004']))->count();
        $this->command->info("MetaPeriodos creados ({$total} total).");
    }

    private function generarPeriodosParaIndicador(Indicador $indicador, int $ejercicio): array
    {
        $frecuencia = $indicador->frecuencia->value ?? $indicador->frecuencia;
        $meta = (float) $indicador->meta;

        return match ($frecuencia) {
            'mensual' => $this->generarPeriodosMensual($meta, $ejercicio),
            'trimestral' => $this->generarPeriodosTrimestral($meta, $ejercicio),
            'semestral' => $this->generarPeriodosSemestral($meta, $ejercicio),
            'anual' => $this->generarPeriodosAnual($meta, $ejercicio),
            'bianual' => $this->generarPeriodosBianual($meta, $ejercicio),
            'sexenal' => $this->generarPeriodosSexenal($meta, $ejercicio),
            default => [],
        };
    }

    private function generarPeriodosMensual(float $meta, int $year): array
    {
        $metaPorPeriodo = $meta / 12;
        $periodos = [];
        for ($m = 1; $m <= 12; $m++) {
            $lastDay = Carbon::create($year, $m)->endOfMonth()->day;
            $periodos[] = [
                'periodo' => $m,
                'meta_periodo' => $metaPorPeriodo,
                'fecha_apertura' => "{$year}-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-01",
                'fecha_cierre' => "{$year}-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-{$lastDay}",
            ];
        }
        return $periodos;
    }

    private function generarPeriodosTrimestral(float $meta, int $year): array
    {
        $metaPorPeriodo = $meta / 4;
        return [
            ['periodo' => 1, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => "{$year}-01-01", 'fecha_cierre' => "{$year}-03-31"],
            ['periodo' => 2, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => "{$year}-04-01", 'fecha_cierre' => "{$year}-06-30"],
            ['periodo' => 3, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => "{$year}-07-01", 'fecha_cierre' => "{$year}-09-30"],
            ['periodo' => 4, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => "{$year}-10-01", 'fecha_cierre' => "{$year}-12-31"],
        ];
    }

    private function generarPeriodosSemestral(float $meta, int $year): array
    {
        $metaPorPeriodo = $meta / 2;
        return [
            ['periodo' => 1, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => "{$year}-01-01", 'fecha_cierre' => "{$year}-06-30"],
            ['periodo' => 2, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => "{$year}-07-01", 'fecha_cierre' => "{$year}-12-31"],
        ];
    }

    private function generarPeriodosAnual(float $meta, int $year): array
    {
        return [
            ['periodo' => 1, 'meta_periodo' => $meta, 'fecha_apertura' => "{$year}-01-01", 'fecha_cierre' => "{$year}-12-31"],
        ];
    }

    private function generarPeriodosBianual(float $meta, int $year): array
    {
        if ($year % 2 !== 0) {
            return [];
        }
        return [
            ['periodo' => 1, 'meta_periodo' => $meta, 'fecha_apertura' => "{$year}-01-01", 'fecha_cierre' => "{$year}-12-31"],
        ];
    }

    private function generarPeriodosSexenal(float $meta, int $year): array
    {
        return [
            ['periodo' => 1, 'meta_periodo' => $meta, 'fecha_apertura' => "{$year}-01-01", 'fecha_cierre' => "{$year}-12-31"],
        ];
    }

    private function crearAvances(): void
    {
        $semaforoService = app(SemaforoService::class);
        $operador = User::where('email', 'ele.operador@gmail.com')->first();
        $operador2 = User::where('email', 'ele.operador2@gmail.com')->first();
        $operadorSectur = User::where('email', 'ele.operador.sectur@gmail.com')->first();

        // --- P1 ISM-001: V-shape narrative ("Porcentaje de subsidios otorgados...") ---
        $this->crearAvance('ISM-001', 'Porcentaje de subsidios otorgados respecto a solicitudes aprobadas', 1, 2025, 20.0, EstadoAvance::APROBADO, $operador, $semaforoService);
        $this->crearAvance('ISM-001', 'Porcentaje de subsidios otorgados respecto a solicitudes aprobadas', 2, 2025, 19.5, EstadoAvance::APROBADO, $operador, $semaforoService);
        $this->crearAvance('ISM-001', 'Porcentaje de subsidios otorgados respecto a solicitudes aprobadas', 3, 2025, 11.7, EstadoAvance::OBSERVADO, $operador, $semaforoService, 'Sequía prolongada afectó producción de agave y demanda de subsidios disminuyó significativamente');
        $this->crearAvance('ISM-001', 'Porcentaje de subsidios otorgados respecto a solicitudes aprobadas', 4, 2025, 16.6, EstadoAvance::EN_REVISION, $operador, $semaforoService);
        $this->crearAvance('ISM-001', 'Porcentaje de subsidios otorgados respecto a solicitudes aprobadas', 1, 2026, 19.8, EstadoAvance::APROBADO, $operador, $semaforoService);

        // P1 other trimestral indicators — consistently green
        foreach (['Porcentaje de solicitudes evaluadas en plazo', 'Número de inspecciones de palenques realizadas', 'Índice de satisfacción de productores capacitados'] as $indName) {
            foreach ([['q' => 1, 'y' => 2025], ['q' => 2, 'y' => 2025], ['q' => 3, 'y' => 2025], ['q' => 4, 'y' => 2025], ['q' => 1, 'y' => 2026]] as $p) {
                $ind = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'ISM-001'))->where('nombre', $indName)->first();
                if ($ind) {
                    $metaPeriodo = (float) $ind->meta / 4;
                    $resultado = $metaPeriodo * 0.95; // 95% of target = verde
                    $this->crearAvance('ISM-001', $indName, $p['q'], $p['y'], round($resultado, 2), EstadoAvance::APROBADO, $operador, $semaforoService);
                }
            }
        }

        // --- P2 PEC-002: consistently green ("Porcentaje de detecciones realizadas...") ---
        $this->crearAvance('PEC-002', 'Porcentaje de detecciones realizadas respecto a la meta programada', 1, 2025, 21.5, EstadoAvance::APROBADO, $operador2, $semaforoService);
        $this->crearAvance('PEC-002', 'Porcentaje de detecciones realizadas respecto a la meta programada', 2, 2025, 22.0, EstadoAvance::APROBADO, $operador2, $semaforoService);
        $this->crearAvance('PEC-002', 'Porcentaje de detecciones realizadas respecto a la meta programada', 3, 2025, 20.7, EstadoAvance::APROBADO, $operador2, $semaforoService);
        $this->crearAvance('PEC-002', 'Porcentaje de detecciones realizadas respecto a la meta programada', 4, 2025, 21.8, EstadoAvance::APROBADO, $operador2, $semaforoService);
        $this->crearAvance('PEC-002', 'Porcentaje de detecciones realizadas respecto a la meta programada', 1, 2026, 22.3, EstadoAvance::APROBADO, $operador2, $semaforoService);

        // P2 other trimestral indicators — consistently green
        foreach (['Porcentaje de pacientes con seguimiento completo a 3 meses', 'Número de docentes capacitados en nutrición preventiva'] as $indName) {
            foreach ([['q' => 1, 'y' => 2025], ['q' => 2, 'y' => 2025], ['q' => 3, 'y' => 2025], ['q' => 4, 'y' => 2025], ['q' => 1, 'y' => 2026]] as $p) {
                $ind = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'PEC-002'))->where('nombre', $indName)->first();
                if ($ind) {
                    $metaPeriodo = (float) $ind->meta / 4;
                    $resultado = $metaPeriodo * 0.95;
                    $this->crearAvance('PEC-002', $indName, $p['q'], $p['y'], round($resultado, 2), EstadoAvance::APROBADO, $operador2, $semaforoService);
                }
            }
        }

        // --- P3 FSP-003: ascending narrative ("Porcentaje de equipamiento entregado...") ---
        $this->crearAvance('FSP-003', 'Porcentaje de equipamiento entregado respecto al programado', 1, 2025, 10.0, EstadoAvance::OBSERVADO, $operador, $semaforoService, 'Programa en fase inicial. Procesos de licitación retrasados.');
        $this->crearAvance('FSP-003', 'Porcentaje de equipamiento entregado respecto al programado', 2, 2025, 13.75, EstadoAvance::OBSERVADO, $operador, $semaforoService, 'Avance insuficiente. Se requiere acelerar entregas.');
        $this->crearAvance('FSP-003', 'Porcentaje de equipamiento entregado respecto al programado', 3, 2025, 18.0, EstadoAvance::EN_REVISION, $operador, $semaforoService);
        $this->crearAvance('FSP-003', 'Porcentaje de equipamiento entregado respecto al programado', 4, 2025, 22.0, EstadoAvance::APROBADO, $operador, $semaforoService);
        $this->crearAvance('FSP-003', 'Porcentaje de equipamiento entregado respecto al programado', 1, 2026, 23.75, EstadoAvance::APROBADO, $operador, $semaforoService);

        // P3 other trimestral indicators — consistently green
        foreach (['Promedio de operativos mensuales realizados por municipio', 'Número de equipos distribuidos (chalecos y radios)', 'Porcentaje de elementos capacitados del total asignado'] as $indName) {
            foreach ([['q' => 1, 'y' => 2025], ['q' => 2, 'y' => 2025], ['q' => 3, 'y' => 2025], ['q' => 4, 'y' => 2025], ['q' => 1, 'y' => 2026]] as $p) {
                $ind = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'FSP-003'))->where('nombre', $indName)->first();
                if ($ind) {
                    $metaPeriodo = (float) $ind->meta / 4;
                    $resultado = $metaPeriodo * 0.95;
                    $this->crearAvance('FSP-003', $indName, $p['q'], $p['y'], round($resultado, 2), EstadoAvance::APROBADO, $operador, $semaforoService);
                }
            }
        }

        // --- P4 DDT-004: irregular narrative ("Porcentaje de proyectos de rehabilitación completados") ---
        $this->crearAvance('DDT-004', 'Porcentaje de proyectos de rehabilitación completados', 1, 2025, 7.5, EstadoAvance::OBSERVADO, $operadorSectur, $semaforoService, 'Temporada baja turística. Pocos proyectos en ejecución.');
        $this->crearAvance('DDT-004', 'Porcentaje de proyectos de rehabilitación completados', 2, 2025, 27.5, EstadoAvance::APROBADO, $operadorSectur, $semaforoService);
        // Q3-25: NO avance (will be vencido since fecha_cierre is in the past)
        $this->crearAvance('DDT-004', 'Porcentaje de proyectos de rehabilitación completados', 4, 2025, 21.25, EstadoAvance::EN_REVISION, $operadorSectur, $semaforoService);
        $this->crearAvance('DDT-004', 'Porcentaje de proyectos de rehabilitación completados', 1, 2026, null, EstadoAvance::EN_CAPTURA, $operadorSectur, $semaforoService);

        // P4 other trimestral indicators — consistently green
        foreach (['Número de visitantes nacionales e internacionales', 'Razón de proyectos aprobados vs presentados'] as $indName) {
            foreach ([['q' => 1, 'y' => 2025], ['q' => 2, 'y' => 2025], ['q' => 3, 'y' => 2025], ['q' => 4, 'y' => 2025], ['q' => 1, 'y' => 2026]] as $p) {
                $ind = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'DDT-004'))->where('nombre', $indName)->first();
                if ($ind) {
                    $metaPeriodo = (float) $ind->meta / 4;
                    $resultado = $metaPeriodo * 0.95;
                    $this->crearAvance('DDT-004', $indName, $p['q'], $p['y'], round($resultado, 2), EstadoAvance::APROBADO, $operadorSectur, $semaforoService);
                }
            }
        }

        $total = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->whereIn('clave', ['ISM-001', 'PEC-002', 'FSP-003', 'DDT-004']))->count();
        $this->command->info("Avances creados ({$total} total).");
    }

    private function crearAvance(
        string $programaClave,
        string $indicadorNombre,
        int $periodo,
        int $ejercicioFiscal,
        ?float $resultado,
        EstadoAvance $estado,
        User $capturadoPor,
        SemaforoService $semaforoService,
        ?string $observacion = null,
    ): void {
        $indicador = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', $programaClave))
            ->where('nombre', $indicadorNombre)->first();

        if (! $indicador) {
            return;
        }

        $meta = MetaPeriodo::where('indicador_id', $indicador->id)
            ->where('periodo', $periodo)
            ->where('ejercicio_fiscal', $ejercicioFiscal)
            ->first();

        if (! $meta) {
            return;
        }

        $semaforo = null;
        if ($resultado !== null) {
            $semaforo = $semaforoService->calcular($resultado, $indicador, $meta->meta_periodo);
        }

        $historial = [];
        if ($observacion) {
            $historial[] = [
                'fecha' => now()->toISOString(),
                'observacion' => $observacion,
                'por' => 'Sistema QA',
            ];
        }

        Avance::updateOrCreate(
            [
                'meta_periodo_id' => $meta->id,
                'indicador_id' => $indicador->id,
            ],
            [
                'resultado' => $resultado,
                'semaforo_calculado' => $semaforo,
                'estado' => $estado->value,
                'capturado_por' => $capturadoPor->id,
                'historial_observaciones' => $historial ?: [],
            ]
        );
    }

    private function generarResultadosEsperados(): void
    {
        $se     = Team::where('clave_ur', 'SE-001')->first();
        $ss     = Team::where('clave_ur', 'SS-002')->first();
        $seg    = Team::where('clave_ur', 'SEG-003')->first();
        $sectur = Team::where('clave_ur', 'SECTUR-004')->first();

        $content = "# Resultados Esperados — QA Testing\n\n";
        $content .= "> Generado automáticamente por QaTestingSeeder el " . now()->format('Y-m-d H:i') . "\n\n";

        // Semáforos por avance
        $content .= "## Semáforos Esperados por Avance\n\n";
        $content .= "| Programa | Indicador | Periodo | Ejercicio | Resultado | Meta Periodo | Sentido | Semáforo | Estado |\n";
        $content .= "|----------|-----------|---------|-----------|-----------|-------------|---------|----------|--------|\n";

        $avances = Avance::with(['indicador.mirNivel.programa', 'metaPeriodo'])->get();
        foreach ($avances as $avance) {
            $prog = $avance->indicador->mirNivel->programa->clave ?? '?';
            $ind = $avance->indicador->nombre ?? '?';
            $per = $avance->metaPeriodo->periodo ?? '?';
            $ej = $avance->metaPeriodo->ejercicio_fiscal ?? '?';
            $res = $avance->resultado ?? 'N/A';
            $mp = $avance->metaPeriodo->meta_periodo ?? 'N/A';
            $sentido = $avance->indicador->sentido->value ?? $avance->indicador->sentido ?? '?';
            $sem = $avance->semaforo_calculado ?? 'N/A';
            $est = $avance->estado->value ?? $avance->estado ?? '?';
            $content .= "| {$prog} | {$ind} | {$per} | {$ej} | {$res} | {$mp} | {$sentido} | {$sem} | {$est} |\n";
        }

        // Dashboard sections for all 4 URs
        $teams = [
            'SE-001' => $se,
            'SS-002' => $ss,
            'SEG-003' => $seg,
            'SECTUR-004' => $sectur,
        ];

        foreach ($teams as $clave => $team) {
            $content .= "\n## Dashboard Admin — {$clave} (team_id={$team->id})\n\n";

            $progs = ProgramaPresupuestario::paraTeam($team->id)->count();
            $inds = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->paraTeam($team->id))
                ->where('activo_seguimiento', true)->count();
            $vencidos = MetaPeriodo::where('fecha_cierre', '<', now())
                ->where('activo', true)
                ->doesntHave('avance')
                ->whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($team->id))
                ->count();

            $content .= "- **Programas:** {$progs}\n";
            $content .= "- **Indicadores con seguimiento:** {$inds}\n";
            $content .= "- **Vencidos (metas sin avance con fecha pasada):** {$vencidos}\n";

            // Semáforo distribution
            $content .= "\n### Distribución Semáforo — {$clave}\n\n";
            $sems = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($team->id))
                ->whereNotNull('semaforo_calculado')
                ->get()
                ->groupBy('semaforo_calculado')
                ->map->count();

            $content .= "- Verde: " . ($sems['verde'] ?? 0) . "\n";
            $content .= "- Amarillo: " . ($sems['amarillo'] ?? 0) . "\n";
            $content .= "- Rojo: " . ($sems['rojo'] ?? 0) . "\n";
        }

        // Write file
        $dir = base_path('docs/qa');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put("{$dir}/expected-results.md", $content);

        $this->command->info('Resultados esperados generados en docs/qa/expected-results.md');
    }
}
