# Sprint 11: QA Testing Seeder — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Crear un seeder idempotente (`QaTestingSeeder`) que genere datos realistas para pruebas manuales de QA pre-beta.

**Architecture:** Un seeder PHP organizado en métodos privados por dominio (usuarios, programas, MIR, calendarización, avances). Usa `firstOrCreate`/`updateOrCreate` para idempotencia. Genera un archivo `docs/qa/expected-results.md` con valores calculados para comparación.

**Tech Stack:** Laravel 12, PostgreSQL, Jetstream Teams, Spatie Permission

---

### Task 1: Crear el test del seeder

**Files:**
- Create: `tests/Feature/Seeders/QaTestingSeederTest.php`

**Step 1: Escribir el test que verifica la idempotencia y datos creados**

```php
<?php

namespace Tests\Feature\Seeders;

use App\Enums\EstadoAvance;
use App\Enums\SystemRole;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaTestingSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
    }

    public function test_seeder_creates_six_users(): void
    {
        $this->seed(QaTestingSeeder::class);

        $emails = [
            'ele.admin@gmail.com',
            'ele.planeador@gmail.com',
            'ele.operador@gmail.com',
            'ele.planeador2@gmail.com',
            'ele.revisor@gmail.com',
            'ele.operador2@gmail.com',
        ];

        foreach ($emails as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }
    }

    public function test_users_have_correct_roles(): void
    {
        $this->seed(QaTestingSeeder::class);

        $this->assertTrue(User::where('email', 'ele.admin@gmail.com')->first()->hasRole('admin'));
        $this->assertTrue(User::where('email', 'ele.planeador@gmail.com')->first()->hasRole('planeador'));
        $this->assertTrue(User::where('email', 'ele.operador@gmail.com')->first()->hasRole('operador'));
        $this->assertTrue(User::where('email', 'ele.planeador2@gmail.com')->first()->hasRole('planeador'));
        $this->assertTrue(User::where('email', 'ele.revisor@gmail.com')->first()->hasRole('planeador'));
        $this->assertTrue(User::where('email', 'ele.operador2@gmail.com')->first()->hasRole('operador'));
    }

    public function test_users_assigned_to_correct_teams(): void
    {
        $this->seed(QaTestingSeeder::class);

        $se = Team::where('clave_ur', 'SE-001')->first();
        $ss = Team::where('clave_ur', 'SS-002')->first();

        // SE-001: planeador, operador, planeador2
        $seMemberEmails = $se->users->pluck('email')->toArray();
        $this->assertContains('ele.planeador@gmail.com', $seMemberEmails);
        $this->assertContains('ele.operador@gmail.com', $seMemberEmails);
        $this->assertContains('ele.planeador2@gmail.com', $seMemberEmails);

        // SS-002: revisor, operador2
        $ssMemberEmails = $ss->users->pluck('email')->toArray();
        $this->assertContains('ele.revisor@gmail.com', $ssMemberEmails);
        $this->assertContains('ele.operador2@gmail.com', $ssMemberEmails);
    }

    public function test_creates_three_programs(): void
    {
        $this->seed(QaTestingSeeder::class);

        $this->assertDatabaseHas('programas_presupuestarios', ['clave' => 'FER-001']);
        $this->assertDatabaseHas('programas_presupuestarios', ['clave' => 'DP-002']);
        $this->assertDatabaseHas('programas_presupuestarios', ['clave' => 'SP-003']);
    }

    public function test_programs_belong_to_correct_teams(): void
    {
        $this->seed(QaTestingSeeder::class);

        $se = Team::where('clave_ur', 'SE-001')->first();
        $ss = Team::where('clave_ur', 'SS-002')->first();

        $this->assertEquals($se->id, ProgramaPresupuestario::where('clave', 'FER-001')->first()->team_id);
        $this->assertEquals($se->id, ProgramaPresupuestario::where('clave', 'DP-002')->first()->team_id);
        $this->assertEquals($ss->id, ProgramaPresupuestario::where('clave', 'SP-003')->first()->team_id);
    }

    public function test_mir_levels_created_for_each_program(): void
    {
        $this->seed(QaTestingSeeder::class);

        // Prog1: FIN, PROPOSITO, 2 COMP, 4 ACT = 8 niveles
        $prog1 = ProgramaPresupuestario::where('clave', 'FER-001')->first();
        $this->assertEquals(8, MirNivel::where('programa_presupuestario_id', $prog1->id)->count());

        // Prog2: FIN, PROPOSITO, 2 COMP, 4 ACT = 8 niveles
        $prog2 = ProgramaPresupuestario::where('clave', 'DP-002')->first();
        $this->assertEquals(8, MirNivel::where('programa_presupuestario_id', $prog2->id)->count());

        // Prog3: FIN, PROPOSITO, 1 COMP, 2 ACT = 5 niveles
        $prog3 = ProgramaPresupuestario::where('clave', 'SP-003')->first();
        $this->assertEquals(5, MirNivel::where('programa_presupuestario_id', $prog3->id)->count());
    }

    public function test_indicators_created_with_correct_frequencies(): void
    {
        $this->seed(QaTestingSeeder::class);

        // Prog1: 4 indicadores (2 trim, 2 sem)
        $prog1 = ProgramaPresupuestario::where('clave', 'FER-001')->first();
        $ind1 = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $prog1->id))->get();
        $this->assertCount(4, $ind1);
        $this->assertEquals(2, $ind1->where('frecuencia', 'trimestral')->count());
        $this->assertEquals(2, $ind1->where('frecuencia', 'semestral')->count());

        // Prog2: 3 indicadores (2 trim, 1 anual)
        $prog2 = ProgramaPresupuestario::where('clave', 'DP-002')->first();
        $ind2 = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $prog2->id))->get();
        $this->assertCount(3, $ind2);
        $this->assertEquals(2, $ind2->where('frecuencia', 'trimestral')->count());
        $this->assertEquals(1, $ind2->where('frecuencia', 'anual')->count());

        // Prog3: 2 indicadores trim
        $prog3 = ProgramaPresupuestario::where('clave', 'SP-003')->first();
        $ind3 = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $prog3->id))->get();
        $this->assertCount(2, $ind3);
        $this->assertEquals(2, $ind3->where('frecuencia', 'trimestral')->count());
    }

    public function test_meta_periodos_generated(): void
    {
        $this->seed(QaTestingSeeder::class);

        // Trimestrales deben tener 4 periodos
        $trimIndicador = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'FER-001'))
            ->where('frecuencia', 'trimestral')->first();
        $this->assertEquals(4, MetaPeriodo::where('indicador_id', $trimIndicador->id)->count());

        // Semestrales deben tener 2 periodos
        $semIndicador = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'FER-001'))
            ->where('frecuencia', 'semestral')->first();
        $this->assertEquals(2, MetaPeriodo::where('indicador_id', $semIndicador->id)->count());

        // Anuales deben tener 1 periodo
        $anualIndicador = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'DP-002'))
            ->where('frecuencia', 'anual')->first();
        $this->assertEquals(1, MetaPeriodo::where('indicador_id', $anualIndicador->id)->count());
    }

    public function test_avances_created_with_expected_states(): void
    {
        $this->seed(QaTestingSeeder::class);

        $this->assertTrue(Avance::where('estado', EstadoAvance::APROBADO)->exists());
        $this->assertTrue(Avance::where('estado', EstadoAvance::EN_REVISION)->exists());
        $this->assertTrue(Avance::where('estado', EstadoAvance::OBSERVADO)->exists());
        $this->assertTrue(Avance::where('estado', EstadoAvance::EN_CAPTURA)->exists());
    }

    public function test_semaforo_colors_present(): void
    {
        $this->seed(QaTestingSeeder::class);

        $this->assertTrue(Avance::where('semaforo_calculado', 'verde')->exists());
        $this->assertTrue(Avance::where('semaforo_calculado', 'amarillo')->exists());
        $this->assertTrue(Avance::where('semaforo_calculado', 'rojo')->exists());
    }

    public function test_vencido_meta_exists(): void
    {
        $this->seed(QaTestingSeeder::class);

        $this->assertTrue(
            MetaPeriodo::where('fecha_cierre', '<', now())
                ->where('activo', true)
                ->doesntHave('avance')
                ->exists()
        );
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(QaTestingSeeder::class);
        $usersAfterFirst = User::count();
        $programsAfterFirst = ProgramaPresupuestario::count();

        // Ejecutar de nuevo
        $this->seed(QaTestingSeeder::class);
        $usersAfterSecond = User::count();
        $programsAfterSecond = ProgramaPresupuestario::count();

        $this->assertEquals($usersAfterFirst, $usersAfterSecond);
        $this->assertEquals($programsAfterFirst, $programsAfterSecond);
    }
}
```

**Step 2: Correr el test para verificar que falla**

Run: `./vendor/bin/sail artisan test --filter=QaTestingSeederTest 2>&1 | head -30`
Expected: FAIL — class `QaTestingSeeder` no existe

**Step 3: Commit**

```bash
git add tests/Feature/Seeders/QaTestingSeederTest.php
git commit -m "test(S11): add QaTestingSeeder tests — TDD skeleton

Resolves DTE-XX"
```

---

### Task 2: Crear el esqueleto del seeder con usuarios

**Files:**
- Create: `database/seeders/QaTestingSeeder.php`

**Step 1: Crear el seeder con la lógica de usuarios**

```php
<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QaTestingSeeder extends Seeder
{
    private const PASSWORD = 'LseRdlP0P';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('No se puede ejecutar este seeder en producción.');
            return;
        }

        $this->command->info('Iniciando carga de datos de QA...');

        // Verificar que existan las URs del DesarrolloSeeder
        $se = Team::where('clave_ur', 'SE-001')->first();
        $ss = Team::where('clave_ur', 'SS-002')->first();

        if (! $se || ! $ss) {
            $this->command->error('Las URs SE-001 y SS-002 deben existir. Ejecuta DesarrolloSeeder primero.');
            return;
        }

        $this->crearUsuarios($se, $ss);

        $this->command->info('✓ Datos de QA cargados.');
        $this->command->table(
            ['Usuario', 'Email', 'Rol', 'UR'],
            [
                ['Admin QA', 'ele.admin@gmail.com', 'admin', 'Todas'],
                ['Planeador SE', 'ele.planeador@gmail.com', 'planeador', 'SE-001'],
                ['Operador SE', 'ele.operador@gmail.com', 'operador', 'SE-001'],
                ['Planeador2 SE', 'ele.planeador2@gmail.com', 'planeador', 'SE-001'],
                ['Revisor SS', 'ele.revisor@gmail.com', 'planeador', 'SS-002'],
                ['Operador2 SS', 'ele.operador2@gmail.com', 'operador', 'SS-002'],
            ]
        );
        $this->command->info('Contraseña para todos: ' . self::PASSWORD);
    }

    private function crearUsuarios(Team $se, Team $ss): void
    {
        $usuarios = [
            [
                'name' => 'Admin QA',
                'email' => 'ele.admin@gmail.com',
                'role' => SystemRole::ADMIN,
                'team' => null, // admin global, se une a ambas
                'team_role' => 'admin',
            ],
            [
                'name' => 'Planeador SE',
                'email' => 'ele.planeador@gmail.com',
                'role' => SystemRole::PLANEADOR,
                'team' => $se,
                'team_role' => 'planeador',
            ],
            [
                'name' => 'Operador SE',
                'email' => 'ele.operador@gmail.com',
                'role' => SystemRole::OPERADOR,
                'team' => $se,
                'team_role' => 'operador',
            ],
            [
                'name' => 'Planeador2 SE',
                'email' => 'ele.planeador2@gmail.com',
                'role' => SystemRole::PLANEADOR,
                'team' => $se,
                'team_role' => 'planeador',
            ],
            [
                'name' => 'Revisor SS',
                'email' => 'ele.revisor@gmail.com',
                'role' => SystemRole::PLANEADOR,
                'team' => $ss,
                'team_role' => 'planeador',
            ],
            [
                'name' => 'Operador2 SS',
                'email' => 'ele.operador2@gmail.com',
                'role' => SystemRole::OPERADOR,
                'team' => $ss,
                'team_role' => 'operador',
            ],
        ];

        foreach ($usuarios as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make(self::PASSWORD),
                ]
            );

            if (! $user->hasRole($data['role']->value)) {
                $user->assignRole($data['role']->value);
            }

            if ($data['team']) {
                $data['team']->users()->syncWithoutDetaching([
                    $user->id => ['role' => $data['team_role']],
                ]);
                $user->forceFill(['current_team_id' => $data['team']->id])->save();
            } else {
                // Admin: unir a ambas URs
                $se->users()->syncWithoutDetaching([$user->id => ['role' => 'admin']]);
                $ss->users()->syncWithoutDetaching([$user->id => ['role' => 'admin']]);
                $user->forceFill(['current_team_id' => $se->id])->save();
            }
        }
    }
}
```

**Step 2: Correr los tests de usuarios**

Run: `./vendor/bin/sail artisan test --filter="QaTestingSeederTest::test_seeder_creates_six_users|test_users_have_correct_roles|test_users_assigned_to_correct_teams"`
Expected: 3 tests PASS

**Step 3: Commit**

```bash
git add database/seeders/QaTestingSeeder.php
git commit -m "feat(S11): add QaTestingSeeder — user creation with idempotency"
```

---

### Task 3: Agregar programas presupuestarios y niveles MIR

**Files:**
- Modify: `database/seeders/QaTestingSeeder.php`

**Step 1: Agregar métodos para crear programas y MIR**

Agregar al final del método `run()`, después de `$this->crearUsuarios($se, $ss);`:

```php
$this->crearProgramasYMir($se, $ss);
```

Agregar estos métodos privados a la clase:

```php
private function crearProgramasYMir(Team $se, Team $ss): void
{
    $this->crearPrograma1FomentoEconomico($se);
    $this->crearPrograma2DesarrolloProductivo($se);
    $this->crearPrograma3SaludPreventiva($ss);
}

private function crearPrograma1FomentoEconomico(Team $se): ProgramaPresupuestario
{
    $programa = ProgramaPresupuestario::firstOrCreate(
        ['clave' => 'FER-001', 'team_id' => $se->id],
        ['nombre' => 'Fomento Económico Regional', 'ejercicio_fiscal' => 2026]
    );

    // FIN
    $fin = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'Contribuir al incremento del producto interno bruto estatal mediante el fortalecimiento del tejido empresarial y la generación de empleo formal en la región.',
            'supuestos' => 'Las condiciones macroeconómicas nacionales se mantienen estables y favorecen la inversión productiva.',
            'team_id' => $se->id,
        ]
    );

    // PROPOSITO
    $proposito = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'Las micro, pequeñas y medianas empresas del estado incrementan su productividad y competitividad mediante apoyos financieros, capacitación técnica y acceso a mercados.',
            'supuestos' => 'Los empresarios participan activamente en los programas de apoyo y capacitación ofrecidos.',
            'team_id' => $se->id,
        ]
    );

    // COMPONENTE 1
    $comp1 = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'Créditos y financiamientos otorgados a MiPyMEs para capital de trabajo e inversión productiva.',
            'supuestos' => 'Los beneficiarios cumplen con los requisitos de elegibilidad y presentan proyectos viables.',
            'team_id' => $se->id,
        ]
    );

    // ACTIVIDADES del Componente 1
    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
        [
            'resumen_narrativo' => 'Evaluar y dictaminar solicitudes de crédito presentadas por MiPyMEs del estado.',
            'supuestos' => 'Las solicitudes contienen la documentación completa requerida.',
            'team_id' => $se->id,
        ]
    );

    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
        [
            'resumen_narrativo' => 'Dispersar los recursos financieros aprobados y dar seguimiento a la correcta aplicación.',
            'supuestos' => 'Los recursos presupuestales se liberan en tiempo y forma.',
            'team_id' => $se->id,
        ]
    );

    // COMPONENTE 2
    $comp2 = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 2],
        [
            'resumen_narrativo' => 'Programas de capacitación técnica y empresarial impartidos a emprendedores y empresarios.',
            'supuestos' => 'Se cuenta con instructores calificados y espacios adecuados para la capacitación.',
            'team_id' => $se->id,
        ]
    );

    // ACTIVIDADES del Componente 2
    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 3, 'componente_id' => $comp2->id],
        [
            'resumen_narrativo' => 'Diseñar e implementar cursos de capacitación en habilidades empresariales y digitales.',
            'supuestos' => 'Los participantes tienen disponibilidad de horario para asistir a las capacitaciones.',
            'team_id' => $se->id,
        ]
    );

    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 4, 'componente_id' => $comp2->id],
        [
            'resumen_narrativo' => 'Realizar ferias de vinculación comercial para conectar productores con mercados nacionales e internacionales.',
            'supuestos' => 'Existe interés de compradores nacionales e internacionales en productos locales.',
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

    // FIN — narrativa VAGA (defecto para IA: suggestNarrativeSyntax)
    $fin = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'Hacer que la economía mejore y que haya más desarrollo en el estado para todos.',
            'supuestos' => 'Todo sale bien.',
            'team_id' => $se->id,
        ]
    );

    // PROPOSITO — narrativa vaga
    $proposito = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'Los ciudadanos tienen más cosas buenas gracias a los apoyos que da el gobierno.',
            'supuestos' => 'La gente coopera.',
            'team_id' => $se->id,
        ]
    );

    // COMPONENTE 1 — infraestructura vial (para defecto de lógica vertical)
    $comp1 = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'Infraestructura vial construida y modernizada en zonas rurales del estado.',
            'supuestos' => 'Se obtienen permisos de construcción en tiempo.',
            'team_id' => $se->id,
        ]
    );

    // ACTIVIDAD 1 de Comp1 — INCOHERENTE: capacitación bajo infraestructura (defecto lógica vertical)
    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
        [
            'resumen_narrativo' => 'Impartir talleres de capacitación en ventas y marketing digital a emprendedores.',
            'supuestos' => 'Los emprendedores asisten a los talleres.',
            'team_id' => $se->id,
        ]
    );

    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
        [
            'resumen_narrativo' => 'Realizar estudios topográficos para proyectos de pavimentación.',
            'supuestos' => 'Se cuenta con equipo técnico disponible.',
            'team_id' => $se->id,
        ]
    );

    // COMPONENTE 2
    $comp2 = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 2],
        [
            'resumen_narrativo' => 'Apoyos económicos entregados a productores agrícolas del estado.',
            'supuestos' => 'Los productores cumplen requisitos de elegibilidad.',
            'team_id' => $se->id,
        ]
    );

    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 3, 'componente_id' => $comp2->id],
        [
            'resumen_narrativo' => 'Recibir y evaluar solicitudes de apoyo económico de productores.',
            'supuestos' => 'Los productores presentan solicitudes completas.',
            'team_id' => $se->id,
        ]
    );

    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 4, 'componente_id' => $comp2->id],
        [
            'resumen_narrativo' => 'Entregar insumos agrícolas y dar seguimiento a su uso productivo.',
            'supuestos' => 'Los insumos están disponibles en el mercado.',
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

    // FIN
    $fin = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::FIN->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'Contribuir a la reducción de la morbilidad por enfermedades prevenibles en la población del estado mediante acciones de salud preventiva.',
            'supuestos' => 'El sistema de salud estatal mantiene cobertura suficiente en comunidades rurales.',
            'team_id' => $ss->id,
        ]
    );

    // PROPOSITO
    $proposito = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::PROPOSITO->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'La población objetivo recibe servicios integrales de salud preventiva, incluyendo vacunación, detección oportuna y educación para la salud.',
            'supuestos' => 'La población acude voluntariamente a los servicios de salud preventiva.',
            'team_id' => $ss->id,
        ]
    );

    // COMPONENTE 1
    $comp1 = MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::COMPONENTE->value, 'orden' => 1],
        [
            'resumen_narrativo' => 'Campañas de vacunación y detección oportuna realizadas en comunidades prioritarias.',
            'supuestos' => 'Se cuenta con el abasto suficiente de vacunas e insumos médicos.',
            'team_id' => $ss->id,
        ]
    );

    // ACTIVIDADES
    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 1, 'componente_id' => $comp1->id],
        [
            'resumen_narrativo' => 'Organizar y ejecutar jornadas de vacunación en las 12 jurisdicciones sanitarias del estado.',
            'supuestos' => 'El personal de salud está capacitado y disponible para las jornadas.',
            'team_id' => $ss->id,
        ]
    );

    MirNivel::firstOrCreate(
        ['programa_presupuestario_id' => $programa->id, 'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value, 'orden' => 2, 'componente_id' => $comp1->id],
        [
            'resumen_narrativo' => 'Realizar tamizajes de detección oportuna de diabetes, hipertensión y cáncer en población mayor de 40 años.',
            'supuestos' => 'Los equipos de diagnóstico funcionan correctamente y tienen calibración vigente.',
            'team_id' => $ss->id,
        ]
    );

    return $programa;
}
```

Agregar los imports necesarios al inicio del archivo:

```php
use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
```

**Step 2: Correr tests de programas y MIR**

Run: `./vendor/bin/sail artisan test --filter="QaTestingSeederTest::test_creates_three_programs|test_programs_belong_to_correct_teams|test_mir_levels_created_for_each_program"`
Expected: 3 tests PASS

**Step 3: Commit**

```bash
git add database/seeders/QaTestingSeeder.php
git commit -m "feat(S11): add programs and MIR levels to QaTestingSeeder"
```

---

### Task 4: Agregar indicadores a cada programa

**Files:**
- Modify: `database/seeders/QaTestingSeeder.php`

**Step 1: Agregar método de indicadores**

Agregar al final de `run()`, después de `$this->crearProgramasYMir($se, $ss);`:

```php
$this->crearIndicadores();
```

Agregar imports:

```php
use App\Enums\SentidoIndicador;
use App\Models\Mml\Indicador;
```

Agregar método:

```php
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

    // Ind1: FIN, trimestral, ascendente — "Tasa de crecimiento del PIB estatal"
    Indicador::firstOrCreate(
        ['mir_nivel_id' => $prog1Fin->id, 'nombre' => 'Tasa de crecimiento del PIB estatal'],
        [
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 80,
            'unidad_medida_id' => null,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]
    );

    // Ind2: PROPOSITO, trimestral, descendente — "Tasa de desempleo en MiPyMEs"
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

    // Ind3: COMPONENTE 1, semestral, ascendente — "Porcentaje de créditos otorgados"
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

    // Ind4: COMPONENTE 2, semestral, regular — "Índice de satisfacción de capacitados"
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

    // Ind1: FIN, trimestral, ascendente — SIN CREMAA (defecto)
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

    // Ind2: COMPONENTE 1, trimestral, ascendente — indicador normal
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

    // ===== PROGRAMA 3: SP-003 — MIR bien estructurada =====
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
```

**Step 2: Correr tests de indicadores**

Run: `./vendor/bin/sail artisan test --filter="QaTestingSeederTest::test_indicators_created_with_correct_frequencies"`
Expected: PASS

**Step 3: Commit**

```bash
git add database/seeders/QaTestingSeeder.php
git commit -m "feat(S11): add indicators to QaTestingSeeder — 9 across 3 programs"
```

---

### Task 5: Agregar MetaPeriodos (calendarización)

**Files:**
- Modify: `database/seeders/QaTestingSeeder.php`

**Step 1: Agregar método de calendarización**

Agregar al final de `run()`, después de `$this->crearIndicadores();`:

```php
$this->crearMetaPeriodos();
```

Agregar imports:

```php
use App\Models\Mml\MetaPeriodo;
use Carbon\Carbon;
```

Agregar método:

```php
private function crearMetaPeriodos(): void
{
    $indicadores = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->whereIn('clave', ['FER-001', 'DP-002', 'SP-003']))
        ->get();

    foreach ($indicadores as $indicador) {
        $periodos = match ($indicador->frecuencia->value ?? $indicador->frecuencia) {
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

    // Meta vencida especial para DP-002 (sin avance asociado)
    $indVencido = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'DP-002'))
        ->where('nombre', 'Kilómetros de carretera pavimentados')->first();

    if ($indVencido) {
        // Crear un periodo extra ya vencido (periodo 0 = pre-arranque ficticio)
        // Usamos periodo 1 con fecha_cierre pasada — ya creado arriba,
        // simplemente actualizamos la fecha_cierre para que esté vencida
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
```

**Step 2: Correr tests**

Run: `./vendor/bin/sail artisan test --filter="QaTestingSeederTest::test_meta_periodos_generated|test_vencido_meta_exists"`
Expected: 2 tests PASS

**Step 3: Commit**

```bash
git add database/seeders/QaTestingSeeder.php
git commit -m "feat(S11): add MetaPeriodos with calendar dates to QaTestingSeeder"
```

---

### Task 6: Agregar avances con semáforos

**Files:**
- Modify: `database/seeders/QaTestingSeeder.php`

**Step 1: Agregar método de avances**

Agregar al final de `run()`, después de `$this->crearMetaPeriodos();`:

```php
$this->crearAvances();
```

Agregar imports:

```php
use App\Enums\EstadoAvance;
use App\Models\Tracking\Avance;
use App\Services\Tracking\SemaforoService;
```

Agregar método:

```php
private function crearAvances(): void
{
    $semaforoService = app(SemaforoService::class);

    $operador = User::where('email', 'ele.operador@gmail.com')->first();
    $operador2 = User::where('email', 'ele.operador2@gmail.com')->first();

    // === PROG1 FER-001 ===

    // Ind1: "Tasa de crecimiento del PIB estatal" (asc, meta=80, meta_periodo=20)
    // Resultado=19 → (19/20)*100 = 95% → ≥90% → verde
    $this->crearAvance(
        programaClave: 'FER-001',
        indicadorNombre: 'Tasa de crecimiento del PIB estatal',
        periodo: 1,
        resultado: 19,
        estado: EstadoAvance::APROBADO,
        capturadoPor: $operador,
        semaforoService: $semaforoService,
    );

    // Ind2: "Tasa de desempleo en MiPyMEs beneficiadas" (desc, meta=30, meta_periodo=7.5)
    // Resultado=9 → 9 > 7.5 pero 9 ≤ 7.5*1.3=9.75 → amarillo
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

    // Ind1: "Porcentaje de cosas" (asc, meta=100, meta_periodo=25)
    // Resultado=3.75 → (3.75/25)*100 = 15% → <70% → rojo
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

    // Ind2: "Kilómetros de carretera pavimentados" (asc, meta=50, meta_periodo=12.5)
    // Sin resultado — en_captura (P1 tiene fecha_cierre vencida, pero este avance existe en en_captura)
    // Nota: el P1 de este indicador ya se marcó como vencido (fecha_cierre=2026-02-28)
    // Pero creamos un avance en_captura para que el operador lo tenga pendiente
    $this->crearAvance(
        programaClave: 'DP-002',
        indicadorNombre: 'Kilómetros de carretera pavimentados',
        periodo: 1,
        resultado: null,
        estado: EstadoAvance::EN_CAPTURA,
        capturadoPor: $operador,
        semaforoService: $semaforoService,
    );

    // === PROG3 SP-003 ===

    // Ind1: "Porcentaje de cobertura de vacunación" (asc, meta=80, meta_periodo=20)
    // Resultado=18 → (18/20)*100 = 90% → ≥90% → verde
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
```

**Step 2: Correr tests de avances y semáforos**

Run: `./vendor/bin/sail artisan test --filter="QaTestingSeederTest::test_avances_created_with_expected_states|test_semaforo_colors_present"`
Expected: 2 tests PASS

**Step 3: Commit**

```bash
git add database/seeders/QaTestingSeeder.php
git commit -m "feat(S11): add avances with semaforo calculation to QaTestingSeeder"
```

---

### Task 7: Generar documento de resultados esperados

**Files:**
- Modify: `database/seeders/QaTestingSeeder.php`
- Create: `docs/qa/expected-results.md` (generado por el seeder)

**Step 1: Agregar generación de resultados esperados**

Agregar al final de `run()`, después de `$this->crearAvances();`:

```php
$this->generarResultadosEsperados();
```

Agregar import:

```php
use Illuminate\Support\Facades\File;
```

Agregar método:

```php
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

    // Escribir archivo
    $dir = base_path('docs/qa');
    if (! File::isDirectory($dir)) {
        File::makeDirectory($dir, 0755, true);
    }
    File::put("{$dir}/expected-results.md", $content);

    $this->command->info('✓ Resultados esperados generados en docs/qa/expected-results.md');
}
```

**Step 2: Correr todos los tests del seeder**

Run: `./vendor/bin/sail artisan test --filter=QaTestingSeederTest`
Expected: ALL tests PASS

**Step 3: Commit**

```bash
git add database/seeders/QaTestingSeeder.php
git commit -m "feat(S11): generate expected-results.md from QaTestingSeeder"
```

---

### Task 8: Correr test completo de idempotencia y suite general

**Files:**
- No changes — solo verificación

**Step 1: Correr test de idempotencia**

Run: `./vendor/bin/sail artisan test --filter="QaTestingSeederTest::test_seeder_is_idempotent"`
Expected: PASS

**Step 2: Correr la suite completa de tests**

Run: `./vendor/bin/sail artisan test 2>&1 | tail -20`
Expected: 461+ passed (baseline), no nuevos fallos

**Step 3: Ejecutar el seeder manualmente para verificar output**

Run: `./vendor/bin/sail artisan db:seed --class=QaTestingSeeder`
Expected: Tabla con 6 usuarios y mensaje de contraseña

**Step 4: Verificar el archivo de resultados esperados**

Run: `cat docs/qa/expected-results.md`
Expected: Tabla con semáforos, stats por dashboard

**Step 5: Commit final con docs generados**

```bash
git add docs/qa/expected-results.md
git commit -m "docs(S11): add generated QA expected results"
```

---

### Task 9: Agregar docs/qa a .gitignore y commit final del plan

**Files:**
- No code changes — solo verificación y cleanup

**Step 1: Verificar que el archivo expected-results.md NO está en .gitignore**

El archivo `docs/qa/expected-results.md` DEBE estar en el repo para referencia. No agregar a .gitignore.

**Step 2: Correr la suite de tests una última vez**

Run: `./vendor/bin/sail artisan test 2>&1 | tail -5`
Expected: All tests pass, no regresiones

**Step 3: Verificar idempotencia ejecutando el seeder 2 veces**

Run: `./vendor/bin/sail artisan migrate:fresh --seed && ./vendor/bin/sail artisan db:seed --class=QaTestingSeeder && ./vendor/bin/sail artisan db:seed --class=QaTestingSeeder`
Expected: Sin errores, sin duplicados

**Step 4: Usar superpowers:finishing-a-development-branch para completar**
