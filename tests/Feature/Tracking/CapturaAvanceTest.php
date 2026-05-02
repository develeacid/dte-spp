<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\CapturaAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CapturaAvanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Avance $avance;

    private IndicadorVariable $varA;

    private IndicadorVariable $varB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PC-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa de cobertura',
            'formula_texto' => '(A/B) * 100',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100, 'activo_seguimiento' => true, 'orden' => 1,
        ]);

        $this->varA = IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'A', 'nombre' => 'Beneficiarios atendidos',
            'orden' => 1,
        ]);

        $this->varB = IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'B', 'nombre' => 'Poblacion objetivo',
            'orden' => 2,
        ]);

        $metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1, 'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026, 'activo' => true,
        ]);

        $this->avance = Avance::create([
            'meta_periodo_id' => $metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);
    }

    public function test_renderiza_formulario(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->assertSee('Tasa de cobertura')
            ->assertSee('Beneficiarios atendidos')
            ->assertSee('Poblacion objetivo')
            ->assertSee('(A/B) * 100');
    }

    public function test_guardar_avance(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 50)
            ->set("valores.{$this->varB->id}", 200)
            ->call('calcular')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->avance->refresh();
        $this->assertEquals(25.0, (float) $this->avance->resultado);
        $this->assertEquals('verde', $this->avance->semaforo_calculado);

        $this->assertDatabaseHas('avance_variables', [
            'avance_id' => $this->avance->id,
            'indicador_variable_id' => $this->varA->id,
            'valor' => 50,
        ]);
    }

    public function test_no_editable_si_congelado(): void
    {
        $this->avance->update(['congelado_at' => now()]);
        $this->avance->refresh();

        $this->actingAs($this->user);

        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->assertSee('congelado')
            ->assertDontSee('Guardar avance');
    }
}
