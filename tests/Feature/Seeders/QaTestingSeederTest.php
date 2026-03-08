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

        $seMemberEmails = $se->users->pluck('email')->toArray();
        $this->assertContains('ele.planeador@gmail.com', $seMemberEmails);
        $this->assertContains('ele.operador@gmail.com', $seMemberEmails);
        $this->assertContains('ele.planeador2@gmail.com', $seMemberEmails);

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

        $prog1 = ProgramaPresupuestario::where('clave', 'FER-001')->first();
        $this->assertEquals(8, MirNivel::where('programa_presupuestario_id', $prog1->id)->count());

        $prog2 = ProgramaPresupuestario::where('clave', 'DP-002')->first();
        $this->assertEquals(8, MirNivel::where('programa_presupuestario_id', $prog2->id)->count());

        $prog3 = ProgramaPresupuestario::where('clave', 'SP-003')->first();
        $this->assertEquals(5, MirNivel::where('programa_presupuestario_id', $prog3->id)->count());
    }

    public function test_indicators_created_with_correct_frequencies(): void
    {
        $this->seed(QaTestingSeeder::class);

        $prog1 = ProgramaPresupuestario::where('clave', 'FER-001')->first();
        $ind1 = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $prog1->id))->get();
        $this->assertCount(4, $ind1);
        $this->assertEquals(2, $ind1->where('frecuencia', 'trimestral')->count());
        $this->assertEquals(2, $ind1->where('frecuencia', 'semestral')->count());

        $prog2 = ProgramaPresupuestario::where('clave', 'DP-002')->first();
        $ind2 = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $prog2->id))->get();
        $this->assertCount(3, $ind2);
        $this->assertEquals(2, $ind2->where('frecuencia', 'trimestral')->count());
        $this->assertEquals(1, $ind2->where('frecuencia', 'anual')->count());

        $prog3 = ProgramaPresupuestario::where('clave', 'SP-003')->first();
        $ind3 = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $prog3->id))->get();
        $this->assertCount(2, $ind3);
        $this->assertEquals(2, $ind3->where('frecuencia', 'trimestral')->count());
    }

    public function test_meta_periodos_generated(): void
    {
        $this->seed(QaTestingSeeder::class);

        $trimIndicador = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'FER-001'))
            ->where('frecuencia', 'trimestral')->first();
        $this->assertEquals(4, MetaPeriodo::where('indicador_id', $trimIndicador->id)->count());

        $semIndicador = Indicador::whereHas('mirNivel.programa', fn ($q) => $q->where('clave', 'FER-001'))
            ->where('frecuencia', 'semestral')->first();
        $this->assertEquals(2, MetaPeriodo::where('indicador_id', $semIndicador->id)->count());

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

        $this->seed(QaTestingSeeder::class);
        $usersAfterSecond = User::count();
        $programsAfterSecond = ProgramaPresupuestario::count();

        $this->assertEquals($usersAfterFirst, $usersAfterSecond);
        $this->assertEquals($programsAfterFirst, $programsAfterSecond);
    }
}
