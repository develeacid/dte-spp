<?php

namespace Tests\Feature\Mml;

use App\Contracts\LlmServiceInterface;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\CremaValidacionMv;
use App\Models\Mml\Indicador;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CremaMvTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test CREMA MV', 'clave' => 'PT-CMV',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    private function nivel(TipoNivelMir $tipo): MirNivel
    {
        return MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => $tipo->value,
            'resumen_narrativo' => 'Nivel '.$tipo->value,
            'orden' => 1,
        ]);
    }

    private function mv(TipoNivelMir $tipo): MedioVerificacion
    {
        $indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel($tipo)->id,
            'nombre' => 'Indicador test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'orden' => 1,
        ]);

        return MedioVerificacion::create([
            'indicador_id' => $indicador->id,
            'nombre' => 'MV test',
            'orden' => 1,
        ]);
    }

    // --- B9: tipo_fuente (C-073) -----------------------------------------------

    public function test_b9_mv_de_fin_con_fuente_no_externa_falla(): void
    {
        $mv = $this->mv(TipoNivelMir::FIN);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV registro propio', 'tipo_fuente' => 'administrativa_propia',
            ])
            ->assertHasErrors("tipo_fuente_mv_{$mv->id}");

        $this->assertNull($mv->fresh()->tipo_fuente);
    }

    public function test_b9_mv_de_proposito_con_fuente_externa_persiste(): void
    {
        $mv = $this->mv(TipoNivelMir::PROPOSITO);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV INEGI', 'tipo_fuente' => 'externa',
            ])
            ->assertHasNoErrors();

        $this->assertSame('externa', $mv->fresh()->tipo_fuente);
    }

    public function test_b9_mv_de_componente_admite_registro_administrativo(): void
    {
        $mv = $this->mv(TipoNivelMir::COMPONENTE);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV sistema interno', 'tipo_fuente' => 'administrativa_propia',
            ])
            ->assertHasNoErrors();

        $this->assertSame('administrativa_propia', $mv->fresh()->tipo_fuente);
    }

    public function test_b9_sin_tipo_fuente_no_bloquea_legacy(): void
    {
        $mv = $this->mv(TipoNivelMir::FIN);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV legacy sin clasificar',
            ])
            ->assertHasNoErrors();

        $this->assertNull($mv->fresh()->tipo_fuente);
    }

    public function test_tipo_fuente_invalido_rechazado(): void
    {
        $mv = $this->mv(TipoNivelMir::COMPONENTE);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV', 'tipo_fuente' => 'inventada',
            ])
            ->assertHasErrors();

        $this->assertNull($mv->fresh()->tipo_fuente);
    }

    // --- CREMA del MV (C-072): checklist manual --------------------------------

    public function test_guardar_crema_mv_manual_persiste_los_5_pares(): void
    {
        $mv = $this->mv(TipoNivelMir::COMPONENTE);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarCremaMv', $mv->id, [
                'confiable' => true,
                'relevante' => true,
                'economico' => false,
                'economico_observacion' => 'Requiere levantamiento de campo costoso',
                'monitoreable' => true,
                'asequible' => false,
            ])
            ->assertHasNoErrors();

        $crema = CremaValidacionMv::where('medio_verificacion_id', $mv->id)->first();
        $this->assertNotNull($crema);
        $this->assertTrue($crema->confiable);
        $this->assertFalse($crema->economico);
        $this->assertSame('Requiere levantamiento de campo costoso', $crema->economico_observacion);
        $this->assertFalse($crema->asequible);
    }

    public function test_guardar_crema_mv_actualiza_registro_existente(): void
    {
        $mv = $this->mv(TipoNivelMir::COMPONENTE);
        CremaValidacionMv::create(['medio_verificacion_id' => $mv->id, 'confiable' => false]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarCremaMv', $mv->id, ['confiable' => true]);

        $this->assertSame(1, CremaValidacionMv::where('medio_verificacion_id', $mv->id)->count());
        $this->assertTrue(CremaValidacionMv::where('medio_verificacion_id', $mv->id)->first()->confiable);
    }

    public function test_guardar_crema_mv_es_noop_sobre_otro_programa(): void
    {
        $otroUser = User::factory()->withPersonalTeam()->create();
        $otroPrograma = ProgramaPresupuestario::create([
            'nombre' => 'Otro', 'clave' => 'PT-OTRO', 'team_id' => $otroUser->currentTeam->id,
        ]);
        $nivelAjeno = MirNivel::create([
            'programa_presupuestario_id' => $otroPrograma->id,
            'tipo_nivel' => 'componente', 'resumen_narrativo' => 'X', 'orden' => 1,
        ]);
        $indicadorAjeno = Indicador::create([
            'mir_nivel_id' => $nivelAjeno->id, 'nombre' => 'I', 'tipo' => 'gestion',
            'dimension' => 'eficacia', 'frecuencia' => 'trimestral', 'orden' => 1,
        ]);
        $mvAjeno = MedioVerificacion::create([
            'indicador_id' => $indicadorAjeno->id, 'nombre' => 'MV ajeno', 'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarCremaMv', $mvAjeno->id, ['confiable' => true]);

        $this->assertDatabaseMissing('crema_validaciones_mv', ['medio_verificacion_id' => $mvAjeno->id]);
    }

    // --- CREMA del MV con IA ----------------------------------------------------

    public function test_validar_crema_mv_con_ia_mapea_los_5_criterios(): void
    {
        $mv = $this->mv(TipoNivelMir::COMPONENTE);

        $mock = \Mockery::mock(LlmServiceInterface::class);
        $mock->shouldReceive('suggest')->once()->andReturn(json_encode([
            'confiable' => true, 'confiable_observacion' => '',
            'relevante' => true, 'relevante_observacion' => '',
            'economico' => false, 'economico_observacion' => 'Costo alto',
            'monitoreable' => true, 'monitoreable_observacion' => '',
            'asequible' => true, 'asequible_observacion' => '',
        ]));
        $this->app->instance(LlmServiceInterface::class, $mock);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarCremaMv', $mv->id);

        $crema = CremaValidacionMv::where('medio_verificacion_id', $mv->id)->first();
        $this->assertNotNull($crema);
        $this->assertTrue($crema->confiable);
        $this->assertFalse($crema->economico);
        $this->assertSame('Costo alto', $crema->economico_observacion);
    }

    public function test_validar_crema_mv_con_ia_requiere_nombre(): void
    {
        $mv = $this->mv(TipoNivelMir::COMPONENTE);
        $mv->update(['nombre' => '']);

        $mock = \Mockery::mock(LlmServiceInterface::class);
        $mock->shouldNotReceive('suggest');
        $this->app->instance(LlmServiceInterface::class, $mock);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarCremaMv', $mv->id);

        $this->assertDatabaseMissing('crema_validaciones_mv', ['medio_verificacion_id' => $mv->id]);
    }
}
