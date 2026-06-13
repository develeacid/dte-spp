<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\EstadoCierreFiscal;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\CapturaAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\Presupuesto\CierreFiscal;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CierreFiscalBloqueaCapturaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Avance $avance;

    private ProgramaPresupuestario $programa;

    private IndicadorVariable $varA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->withPersonalTeam()->create();

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PC-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Cobertura',
            'formula_texto' => 'A', 'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100, 'activo_seguimiento' => true, 'orden' => 1,
        ]);
        $this->varA = IndicadorVariable::create([
            'indicador_id' => $indicador->id, 'simbolo' => 'A', 'nombre' => 'Resultado', 'orden' => 1,
        ]);
        $meta = MetaPeriodo::create([
            'indicador_id' => $indicador->id, 'periodo' => 1, 'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026, 'activo' => true,
        ]);
        $this->avance = Avance::create([
            'meta_periodo_id' => $meta->id, 'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value, 'capturado_por' => $this->user->id,
        ]);
    }

    public function test_bloquea_captura_si_cierre_del_ejercicio_esta_cerrado(): void
    {
        CierreFiscal::create([
            'programa_id' => $this->programa->id, 'ejercicio_fiscal' => 2026,
            'estado' => EstadoCierreFiscal::CERRADO,
        ]);

        Livewire::actingAs($this->user)
            ->test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 100)
            ->call('guardar')
            ->assertStatus(403);
    }

    public function test_permite_captura_si_cierre_no_cerrado(): void
    {
        CierreFiscal::create([
            'programa_id' => $this->programa->id, 'ejercicio_fiscal' => 2026,
            'estado' => EstadoCierreFiscal::CONSOLIDACION,
        ]);

        Livewire::actingAs($this->user)
            ->test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 100)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertStatus(200);
    }
}
