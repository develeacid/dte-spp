<?php

namespace Tests\Feature\Mml;

use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MirEditorCapturaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($this->user);

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa captura',
            'clave' => 'PT-CAP-'.uniqid(),
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);
    }

    private function componenteConIndicador(): Indicador
    {
        $nivel = MirNivel::factory()->create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => 1,
        ]);

        return Indicador::factory()->create(['mir_nivel_id' => $nivel->id]);
    }

    private function editor()
    {
        return Livewire::test(MirEditor::class, ['programa' => $this->programa]);
    }

    public function test_guardar_indicador_persiste_sentido(): void
    {
        $indicador = $this->componenteConIndicador();

        $this->editor()->call('guardarIndicador', $indicador->id, [
            'nombre' => 'Tasa de variación',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => 'descendente',
        ]);

        $this->assertSame(SentidoIndicador::DESCENDENTE, $indicador->fresh()->sentido);
    }

    public function test_guardar_indicador_rechaza_sentido_invalido(): void
    {
        $indicador = $this->componenteConIndicador();

        $this->editor()->call('guardarIndicador', $indicador->id, [
            'nombre' => 'X',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => 'regular',
        ])->assertHasErrors('sentido');
    }
}
