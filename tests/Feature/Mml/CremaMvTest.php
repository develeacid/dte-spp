<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
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
}
