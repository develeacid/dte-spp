<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\FlujosAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\Tracking\Avance;
use App\Models\User;
use App\Notifications\AvanceEnRevisionNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotificarPlaneadoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_notifica_planeador_coordinadora_cuando_team_id_es_null(): void
    {
        Notification::fake();

        $teamCoord = Team::forceCreate([
            'user_id' => 1,
            'name' => 'UR Coordinadora',
            'personal_team' => false,
        ]);

        $planeadorCoord = User::factory()->create();
        $planeadorCoord->assignRole('planeador');
        $teamCoord->users()->attach($planeadorCoord, ['role' => 'planeador']);
        $planeadorCoord->forceFill(['current_team_id' => $teamCoord->id])->save();

        $operadorCoord = User::factory()->create();
        $operadorCoord->assignRole('operador');
        $teamCoord->users()->attach($operadorCoord, ['role' => 'operador']);
        $operadorCoord->forceFill(['current_team_id' => $teamCoord->id])->save();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Coordinadora',
            'clave' => 'PC-001',
            'team_id' => $teamCoord->id,
        ]);

        // Nivel con team_id = null (pertenece a la coordinadora implicitamente)
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Componente coordinadora',
            'orden' => 1,
            'team_id' => null,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador coordinadora',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        $avance = Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $operadorCoord->id,
        ]);

        Livewire::actingAs($operadorCoord)
            ->test(FlujosAvance::class, ['avance' => $avance])
            ->call('enviarRevision');

        Notification::assertSentTo($planeadorCoord, AvanceEnRevisionNotification::class);
    }

    public function test_notifica_planeador_coadyuvante_cuando_team_id_asignado(): void
    {
        Notification::fake();

        $teamCoord = Team::forceCreate([
            'user_id' => 1,
            'name' => 'UR Coordinadora',
            'personal_team' => false,
        ]);

        $teamCoad = Team::forceCreate([
            'user_id' => 1,
            'name' => 'UR Coadyuvante',
            'personal_team' => false,
        ]);

        $planeadorCoord = User::factory()->create();
        $planeadorCoord->assignRole('planeador');
        $teamCoord->users()->attach($planeadorCoord, ['role' => 'planeador']);
        $planeadorCoord->forceFill(['current_team_id' => $teamCoord->id])->save();

        $planeadorCoad = User::factory()->create();
        $planeadorCoad->assignRole('planeador');
        $teamCoad->users()->attach($planeadorCoad, ['role' => 'planeador']);
        $planeadorCoad->forceFill(['current_team_id' => $teamCoad->id])->save();

        $operadorCoad = User::factory()->create();
        $operadorCoad->assignRole('operador');
        $teamCoad->users()->attach($operadorCoad, ['role' => 'operador']);
        $operadorCoad->forceFill(['current_team_id' => $teamCoad->id])->save();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Transversal',
            'clave' => 'PT-001',
            'team_id' => $teamCoord->id,
        ]);

        $programa->equipos()->attach($teamCoad->id, ['rol' => 'coadyuvante']);

        // Nivel asignado a la coadyuvante
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Componente coadyuvante',
            'orden' => 2,
            'team_id' => $teamCoad->id,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador coadyuvante',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 50,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 12.5,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        $avance = Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $operadorCoad->id,
        ]);

        Livewire::actingAs($operadorCoad)
            ->test(FlujosAvance::class, ['avance' => $avance])
            ->call('enviarRevision');

        // Planeador coadyuvante SI recibe notificacion
        Notification::assertSentTo($planeadorCoad, AvanceEnRevisionNotification::class);

        // Planeador coordinadora NO recibe (no es su nivel)
        Notification::assertNotSentTo($planeadorCoord, AvanceEnRevisionNotification::class);
    }

    public function test_no_notifica_si_no_hay_team_ni_programa(): void
    {
        Notification::fake();

        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('operador');

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Huerfano',
            'clave' => 'PH-001',
            'team_id' => null,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Nivel huerfano',
            'orden' => 1,
            'team_id' => null,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador huerfano',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
            'activo' => true,
        ]);

        $avance = Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(FlujosAvance::class, ['avance' => $avance])
            ->call('enviarRevision');

        // No explota, simplemente no notifica a nadie
        Notification::assertNothingSent();
    }
}
