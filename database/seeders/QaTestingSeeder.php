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
        // ===== PROGRAMA 1: FER-001 — MIR bien estructurada =====
        $prog1 = ProgramaPresupuestario::where('clave', 'FER-001')->first();
        $prog1Fin = MirNivel::where('programa_presupuestario_id', $prog1->id)
            ->where('tipo_nivel', TipoNivelMir::FIN->value)->first();
        $prog1Proposito = MirNivel::where('programa_presupuestario_id', $prog1->id)
            ->where('tipo_nivel', TipoNivelMir::PROPOSITO->value)->first();
        $prog1Comps = MirNivel::where('programa_presupuestario_id', $prog1->id)
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE->value)->orderBy('orden')->get();

        // Ind1: FIN, trimestral, ascendente
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog1Fin->id, 'nombre' => 'Tasa de crecimiento del PIB estatal'],
            [
                'tipo' => 'estrategico',
                'dimension' => 'eficacia',
                'frecuencia' => 'trimestral',
                'sentido' => SentidoIndicador::ASCENDENTE->value,
                'meta' => 80,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );

        // Ind2: PROPOSITO, trimestral, descendente
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog1Proposito->id, 'nombre' => 'Tasa de desempleo en MiPyMEs beneficiadas'],
            [
                'tipo' => 'estrategico',
                'dimension' => 'eficacia',
                'frecuencia' => 'trimestral',
                'sentido' => SentidoIndicador::DESCENDENTE->value,
                'meta' => 30,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );

        // Ind3: COMPONENTE 1, semestral, ascendente
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog1Comps[0]->id, 'nombre' => 'Porcentaje de créditos otorgados vs solicitados'],
            [
                'tipo' => 'gestion',
                'dimension' => 'eficiencia',
                'frecuencia' => 'semestral',
                'sentido' => SentidoIndicador::ASCENDENTE->value,
                'meta' => 85,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );

        // Ind4: COMPONENTE 2, semestral, regular — with ranges
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog1Comps[1]->id, 'nombre' => 'Índice de satisfacción de capacitados'],
            [
                'tipo' => 'gestion',
                'dimension' => 'calidad',
                'frecuencia' => 'semestral',
                'sentido' => SentidoIndicador::REGULAR->value,
                'meta' => 80,
                'rango_verde_min' => 75,
                'rango_verde_max' => 90,
                'rango_amarillo_min' => 60,
                'rango_amarillo_max' => 95,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );

        // ===== PROGRAMA 2: DP-002 — MIR con defectos =====
        $prog2 = ProgramaPresupuestario::where('clave', 'DP-002')->first();
        $prog2Fin = MirNivel::where('programa_presupuestario_id', $prog2->id)
            ->where('tipo_nivel', TipoNivelMir::FIN->value)->first();
        $prog2Comps = MirNivel::where('programa_presupuestario_id', $prog2->id)
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE->value)->orderBy('orden')->get();

        // Ind1: FIN, trimestral, ascendente — SIN CREMAA
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog2Fin->id, 'nombre' => 'Porcentaje de cosas'],
            [
                'tipo' => 'estrategico',
                'dimension' => 'eficacia',
                'frecuencia' => 'trimestral',
                'sentido' => SentidoIndicador::ASCENDENTE->value,
                'meta' => 100,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );

        // Ind2: COMPONENTE 1, trimestral, ascendente
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog2Comps[0]->id, 'nombre' => 'Kilómetros de carretera pavimentados'],
            [
                'tipo' => 'gestion',
                'dimension' => 'eficacia',
                'frecuencia' => 'trimestral',
                'sentido' => SentidoIndicador::ASCENDENTE->value,
                'meta' => 50,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );

        // Ind3: COMPONENTE 2, anual, ascendente
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog2Comps[1]->id, 'nombre' => 'Número de productores beneficiados'],
            [
                'tipo' => 'gestion',
                'dimension' => 'eficacia',
                'frecuencia' => 'anual',
                'sentido' => SentidoIndicador::ASCENDENTE->value,
                'meta' => 500,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );

        // ===== PROGRAMA 3: SP-003 =====
        $prog3 = ProgramaPresupuestario::where('clave', 'SP-003')->first();
        $prog3Proposito = MirNivel::where('programa_presupuestario_id', $prog3->id)
            ->where('tipo_nivel', TipoNivelMir::PROPOSITO->value)->first();
        $prog3Comp = MirNivel::where('programa_presupuestario_id', $prog3->id)
            ->where('tipo_nivel', TipoNivelMir::COMPONENTE->value)->first();

        // Ind1: PROPOSITO, trimestral, ascendente
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog3Proposito->id, 'nombre' => 'Porcentaje de cobertura de vacunación en población objetivo'],
            [
                'tipo' => 'estrategico',
                'dimension' => 'eficacia',
                'frecuencia' => 'trimestral',
                'sentido' => SentidoIndicador::ASCENDENTE->value,
                'meta' => 80,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );

        // Ind2: COMPONENTE, trimestral, ascendente
        Indicador::firstOrCreate(
            ['mir_nivel_id' => $prog3Comp->id, 'nombre' => 'Número de jornadas de vacunación realizadas'],
            [
                'tipo' => 'gestion',
                'dimension' => 'eficacia',
                'frecuencia' => 'trimestral',
                'sentido' => SentidoIndicador::ASCENDENTE->value,
                'meta' => 12,
                'activo_seguimiento' => true,
                'orden' => 1,
            ]
        );
    }

    private function crearMetaPeriodos(): void
    {
        $indicadores = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->whereIn('clave', ['FER-001', 'DP-002', 'SP-003']))
            ->get();

        foreach ($indicadores as $indicador) {
            $frecuencia = $indicador->frecuencia->value ?? $indicador->frecuencia;
            $periodos = match ($frecuencia) {
                'trimestral' => $this->generarPeriodosTrimestral($indicador),
                'semestral' => $this->generarPeriodosSemestral($indicador),
                'anual' => $this->generarPeriodosAnual($indicador),
                default => [],
            };

            foreach ($periodos as $periodo) {
                MetaPeriodo::firstOrCreate(
                    [
                        'indicador_id' => $indicador->id,
                        'periodo' => $periodo['periodo'],
                        'ejercicio_fiscal' => 2026,
                    ],
                    [
                        'meta_periodo' => $periodo['meta_periodo'],
                        'activo' => true,
                        'fecha_apertura' => $periodo['fecha_apertura'],
                        'fecha_cierre' => $periodo['fecha_cierre'],
                    ]
                );
            }
        }

        // Make DP-002 "Kilómetros de carretera pavimentados" P1 overdue (fecha_cierre in the past)
        // This meta period should have NO avance associated, so it counts as "vencido" in dashboard
        $indVencido = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'DP-002'))
            ->where('nombre', 'Kilómetros de carretera pavimentados')->first();

        if ($indVencido) {
            $metaP1 = MetaPeriodo::where('indicador_id', $indVencido->id)
                ->where('periodo', 1)
                ->where('ejercicio_fiscal', 2026)
                ->first();

            if ($metaP1) {
                $metaP1->update(['fecha_cierre' => Carbon::parse('2026-02-28')]);
            }
        }
    }

    private function generarPeriodosTrimestral(Indicador $indicador): array
    {
        $metaPorPeriodo = $indicador->meta / 4;

        return [
            ['periodo' => 1, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => '2026-01-01', 'fecha_cierre' => '2026-03-31'],
            ['periodo' => 2, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => '2026-04-01', 'fecha_cierre' => '2026-06-30'],
            ['periodo' => 3, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => '2026-07-01', 'fecha_cierre' => '2026-09-30'],
            ['periodo' => 4, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => '2026-10-01', 'fecha_cierre' => '2026-12-31'],
        ];
    }

    private function generarPeriodosSemestral(Indicador $indicador): array
    {
        $metaPorPeriodo = $indicador->meta / 2;

        return [
            ['periodo' => 1, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => '2026-01-01', 'fecha_cierre' => '2026-06-30'],
            ['periodo' => 2, 'meta_periodo' => $metaPorPeriodo, 'fecha_apertura' => '2026-07-01', 'fecha_cierre' => '2026-12-31'],
        ];
    }

    private function generarPeriodosAnual(Indicador $indicador): array
    {
        return [
            ['periodo' => 1, 'meta_periodo' => $indicador->meta, 'fecha_apertura' => '2026-01-01', 'fecha_cierre' => '2026-12-31'],
        ];
    }

    private function crearAvances(): void
    {
        $semaforoService = app(SemaforoService::class);

        $operador = User::where('email', 'ele.operador@gmail.com')->first();
        $operador2 = User::where('email', 'ele.operador2@gmail.com')->first();

        // === PROG1 FER-001 ===

        // Ind1: "Tasa de crecimiento del PIB estatal" (ascendente, meta=80, meta_periodo=20)
        // Resultado=19 → (19/20)*100 = 95% → ≥90% → VERDE
        $this->crearAvance(
            programaClave: 'FER-001',
            indicadorNombre: 'Tasa de crecimiento del PIB estatal',
            periodo: 1,
            resultado: 19,
            estado: EstadoAvance::APROBADO,
            capturadoPor: $operador,
            semaforoService: $semaforoService,
        );

        // Ind2: "Tasa de desempleo en MiPyMEs beneficiadas" (descendente, meta=30, meta_periodo=7.5)
        // Resultado=9 → 9 > 7.5 but 9 ≤ 7.5*1.3=9.75 → AMARILLO
        $this->crearAvance(
            programaClave: 'FER-001',
            indicadorNombre: 'Tasa de desempleo en MiPyMEs beneficiadas',
            periodo: 1,
            resultado: 9,
            estado: EstadoAvance::EN_REVISION,
            capturadoPor: $operador,
            semaforoService: $semaforoService,
        );

        // === PROG2 DP-002 ===

        // Ind1: "Porcentaje de cosas" (ascendente, meta=100, meta_periodo=25)
        // Resultado=3.75 → (3.75/25)*100 = 15% → <70% → ROJO
        $this->crearAvance(
            programaClave: 'DP-002',
            indicadorNombre: 'Porcentaje de cosas',
            periodo: 1,
            resultado: 3.75,
            estado: EstadoAvance::OBSERVADO,
            capturadoPor: $operador,
            semaforoService: $semaforoService,
            observacion: 'El indicador no cumple criterios CREMAA. Favor de revisar nombre y método de cálculo.',
        );

        // Ind2: "Número de productores beneficiados" (anual, meta=500, meta_periodo=500)
        // No result yet — en_captura
        $this->crearAvance(
            programaClave: 'DP-002',
            indicadorNombre: 'Número de productores beneficiados',
            periodo: 1,
            resultado: null,
            estado: EstadoAvance::EN_CAPTURA,
            capturadoPor: $operador,
            semaforoService: $semaforoService,
        );

        // === PROG3 SP-003 ===

        // Ind1: "Porcentaje de cobertura de vacunación" (ascendente, meta=80, meta_periodo=20)
        // Resultado=18 → (18/20)*100 = 90% → ≥90% → VERDE
        $this->crearAvance(
            programaClave: 'SP-003',
            indicadorNombre: 'Porcentaje de cobertura de vacunación en población objetivo',
            periodo: 1,
            resultado: 18,
            estado: EstadoAvance::APROBADO,
            capturadoPor: $operador2,
            semaforoService: $semaforoService,
        );
    }

    private function crearAvance(
        string $programaClave,
        string $indicadorNombre,
        int $periodo,
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
            ->where('ejercicio_fiscal', 2026)
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
        $se = Team::where('clave_ur', 'SE-001')->first();
        $ss = Team::where('clave_ur', 'SS-002')->first();

        $content = "# Resultados Esperados — QA Testing\n\n";
        $content .= "> Generado automáticamente por QaTestingSeeder el " . now()->format('Y-m-d H:i') . "\n\n";

        // Semáforos por avance
        $content .= "## Semáforos Esperados por Avance\n\n";
        $content .= "| Programa | Indicador | Resultado | Meta Periodo | Sentido | Semáforo | Estado |\n";
        $content .= "|----------|-----------|-----------|-------------|---------|----------|--------|\n";

        $avances = Avance::with(['indicador.mirNivel.programa', 'metaPeriodo'])->get();
        foreach ($avances as $avance) {
            $prog = $avance->indicador->mirNivel->programa->clave ?? '?';
            $ind = $avance->indicador->nombre ?? '?';
            $res = $avance->resultado ?? 'N/A';
            $mp = $avance->metaPeriodo->meta_periodo ?? 'N/A';
            $sentido = $avance->indicador->sentido->value ?? $avance->indicador->sentido ?? '?';
            $sem = $avance->semaforo_calculado ?? 'N/A';
            $est = $avance->estado->value ?? $avance->estado ?? '?';
            $content .= "| {$prog} | {$ind} | {$res} | {$mp} | {$sentido} | {$sem} | {$est} |\n";
        }

        // Dashboard Admin SE-001
        $content .= "\n## Dashboard Admin — SE-001 (team_id={$se->id})\n\n";
        $progsSE = ProgramaPresupuestario::paraTeam($se->id)->count();
        $indsSE = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->paraTeam($se->id))
            ->where('activo_seguimiento', true)->count();
        $vencidosSE = MetaPeriodo::where('fecha_cierre', '<', now())
            ->where('activo', true)
            ->doesntHave('avance')
            ->whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($se->id))
            ->count();

        $content .= "- **Programas:** {$progsSE}\n";
        $content .= "- **Indicadores con seguimiento:** {$indsSE}\n";
        $content .= "- **Vencidos (metas sin avance con fecha pasada):** {$vencidosSE}\n";

        // Dashboard Admin SS-002
        $content .= "\n## Dashboard Admin — SS-002 (team_id={$ss->id})\n\n";
        $progsSS = ProgramaPresupuestario::paraTeam($ss->id)->count();
        $indsSS = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->paraTeam($ss->id))
            ->where('activo_seguimiento', true)->count();

        $content .= "- **Programas:** {$progsSS}\n";
        $content .= "- **Indicadores con seguimiento:** {$indsSS}\n";

        // Distribución Semáforo Global SE-001
        $content .= "\n## Distribución Semáforo — SE-001\n\n";
        $semSE = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($se->id))
            ->whereNotNull('semaforo_calculado')
            ->get()
            ->groupBy('semaforo_calculado')
            ->map->count();

        $content .= "- Verde: " . ($semSE['verde'] ?? 0) . "\n";
        $content .= "- Amarillo: " . ($semSE['amarillo'] ?? 0) . "\n";
        $content .= "- Rojo: " . ($semSE['rojo'] ?? 0) . "\n";

        // Distribución Semáforo Global SS-002
        $content .= "\n## Distribución Semáforo — SS-002\n\n";
        $semSS = Avance::whereHas('indicador.mirNivel.programa', fn ($q) => $q->paraTeam($ss->id))
            ->whereNotNull('semaforo_calculado')
            ->get()
            ->groupBy('semaforo_calculado')
            ->map->count();

        $content .= "- Verde: " . ($semSS['verde'] ?? 0) . "\n";
        $content .= "- Amarillo: " . ($semSS['amarillo'] ?? 0) . "\n";
        $content .= "- Rojo: " . ($semSS['rojo'] ?? 0) . "\n";

        // Operador stats
        $operador = User::where('email', 'ele.operador@gmail.com')->first();
        $pendientes = Avance::where('capturado_por', $operador->id)
            ->where('estado', EstadoAvance::EN_CAPTURA)->count();
        $capturadosMes = Avance::where('capturado_por', $operador->id)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->whereIn('estado', [EstadoAvance::EN_REVISION, EstadoAvance::APROBADO])
            ->count();

        $content .= "\n## Dashboard Operador — ele.operador@gmail.com\n\n";
        $content .= "- **Pendientes (en_captura):** {$pendientes}\n";
        $content .= "- **Capturados este mes (en_revision + aprobado):** {$capturadosMes}\n";

        // Operador2 stats
        $operador2 = User::where('email', 'ele.operador2@gmail.com')->first();
        $pendientes2 = Avance::where('capturado_por', $operador2->id)
            ->where('estado', EstadoAvance::EN_CAPTURA)->count();

        $content .= "\n## Dashboard Operador — ele.operador2@gmail.com\n\n";
        $content .= "- **Pendientes (en_captura):** {$pendientes2}\n";

        // Write file
        $dir = base_path('docs/qa');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put("{$dir}/expected-results.md", $content);

        $this->command->info('✓ Resultados esperados generados en docs/qa/expected-results.md');
    }
}
