<?php

namespace Tests\Feature\Presupuesto;

use App\Livewire\Presupuesto\CapturaAvanceFinanciero;
use App\Livewire\Presupuesto\GestionPartidas;
use App\Livewire\Presupuesto\PanelPresupuestal;
use App\Models\Presupuesto\PartidaPresupuestal;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\PresupuestoTestHelpers;

class AislamientoPresupuestoTest extends TestCase
{
    use PresupuestoTestHelpers;
    use RefreshDatabase;

    private User $financieroA;

    private User $financieroB;

    private int $teamAId;

    private int $teamBId;

    private int $programaAId;

    private int $programaBId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        // Team A
        $this->financieroA = $this->crearUsuarioFinanciero();
        $this->teamAId = $this->financieroA->currentTeam->id;
        $programaA = $this->crearPrograma($this->teamAId, 'PA-001');
        $this->programaAId = $programaA->id;

        $this->crearPartidaConAvance(
            $this->programaAId,
            $this->teamAId,
            $this->financieroA->id,
            clave: '1000',
        );

        // Team B
        $this->financieroB = $this->crearUsuarioFinanciero();
        $this->teamBId = $this->financieroB->currentTeam->id;
        $programaB = $this->crearPrograma($this->teamBId, 'PB-001');
        $this->programaBId = $programaB->id;

        $this->crearPartidaConAvance(
            $this->programaBId,
            $this->teamBId,
            $this->financieroB->id,
            clave: '2000',
        );
    }

    public function test_financiero_solo_ve_partidas_de_su_team(): void
    {
        Livewire::actingAs($this->financieroA)
            ->test(GestionPartidas::class)
            ->assertSee('1000')
            ->assertDontSee('2000');
    }

    public function test_scope_para_team_filtra_partidas(): void
    {
        $partidasA = PartidaPresupuestal::paraTeam($this->teamAId)->count();
        $partidasB = PartidaPresupuestal::paraTeam($this->teamBId)->count();

        $this->assertEquals(1, $partidasA);
        $this->assertEquals(1, $partidasB);

        // Team A no incluye partidas de Team B
        $idsA = PartidaPresupuestal::paraTeam($this->teamAId)->pluck('team_id')->unique();
        $this->assertCount(1, $idsA);
        $this->assertEquals($this->teamAId, $idsA->first());
    }

    public function test_scope_para_team_filtra_avances(): void
    {
        $partidasA = PartidaPresupuestal::paraTeam($this->teamAId)
            ->with('avancesFinancieros')
            ->get();

        $avancesA = $partidasA->flatMap(fn ($p) => $p->avancesFinancieros);
        $this->assertCount(1, $avancesA);

        // Los avances pertenecen a partidas del team A
        foreach ($avancesA as $avance) {
            $this->assertEquals($this->teamAId, $avance->partida->team_id);
        }
    }

    public function test_captura_avance_solo_programas_del_team(): void
    {
        // Financiero A accede a captura de programa de Team B → página carga
        // pero no muestra partidas (filtro por team en el componente)
        Livewire::actingAs($this->financieroA)
            ->test(CapturaAvanceFinanciero::class, [
                'programa' => ProgramaPresupuestario::find($this->programaBId),
            ])
            ->assertDontSee('2000'); // No ve la partida del otro team
    }

    public function test_panel_presupuestal_muestra_solo_datos_del_team(): void
    {
        Livewire::actingAs($this->financieroA)
            ->test(PanelPresupuestal::class)
            ->assertSee('PA-001')
            ->assertDontSee('PB-001');
    }
}
