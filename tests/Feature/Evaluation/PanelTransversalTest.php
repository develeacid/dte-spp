<?php

namespace Tests\Feature\Evaluation;

use App\Enums\TipoNivelMir;
use App\Livewire\Evaluation\PanelTransversal;
use App\Models\Evaluation\AnexoTransversal;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use App\Models\PndEje;
use App\Models\PndObjetivo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelTransversalTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private ProgramaPresupuestario $programa;

    private EvaluacionPrograma $evaluacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole('planeador');

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Transversal Test',
            'clave' => 'PTR-001',
            'team_id' => $this->planeador->currentTeam->id,
            'ejercicio_fiscal' => (int) config('app.ejercicio_fiscal', now()->year),
        ]);

        $this->evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => (int) config('app.ejercicio_fiscal', now()->year),
            'indice_eficacia' => 75.0000,
            'indicadores_evaluados' => 4,
            'indicadores_no_evaluados' => 0,
            'conteo_semaforos' => ['verde' => 2, 'amarillo' => 1, 'rojo' => 1, 'sin_dato' => 0],
            'desglose_niveles' => [],
            'configuracion_calculo' => [],
        ]);
    }

    public function test_renders_ped_tab_by_default(): void
    {
        $plan = PedPlan::create(['nombre' => 'PED Test', 'periodo_inicio' => 2025, 'periodo_fin' => 2030]);
        $eje = PedEje::create(['ped_plan_id' => $plan->id, 'numero' => 1, 'nombre' => 'Eje Bienestar']);
        $tema = PedTema::create(['ped_eje_id' => $eje->id, 'numero' => 1, 'nombre' => 'Tema Salud']);
        $objetivo = PedObjetivoEstrategico::create(['ped_tema_id' => $tema->id, 'clave' => '1', 'descripcion' => 'Obj Estrategico']);

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin test',
            'orden' => 1,
            'ped_objetivo_estrategico_id' => $objetivo->id,
        ]);

        $this->actingAs($this->planeador);

        Livewire::test(PanelTransversal::class)
            ->assertSee('Evaluacion Transversal')
            ->assertSee('Por Eje PED')
            ->assertSee('Eje Bienestar')
            ->assertSee('75.00%')
            ->assertSee('PTR-001');
    }

    public function test_ods_tab_renders(): void
    {
        $plan = PedPlan::create(['nombre' => 'PED Test', 'periodo_inicio' => 2025, 'periodo_fin' => 2030]);
        $eje = PedEje::create(['ped_plan_id' => $plan->id, 'numero' => 1, 'nombre' => 'Eje Test']);
        $tema = PedTema::create(['ped_eje_id' => $eje->id, 'numero' => 1, 'nombre' => 'Tema Test']);
        $objetivo = PedObjetivoEstrategico::create(['ped_tema_id' => $tema->id, 'clave' => '1', 'descripcion' => 'Obj']);

        $pndEje = PndEje::create(['nombre' => 'PND Eje', 'numero' => 1]);
        $pndObj = PndObjetivo::create(['pnd_eje_id' => $pndEje->id, 'clave' => '1.1', 'descripcion' => 'PND Obj']);

        // Link PED objetivo -> PND objetivo
        $objetivo->pndObjetivos()->attach($pndObj->id);

        $odsObjetivo = OdsObjetivo::create(['numero' => 4, 'nombre' => 'Educacion de Calidad']);
        $odsMeta = OdsMeta::create(['ods_objetivo_id' => $odsObjetivo->id, 'clave' => '4.1', 'descripcion' => 'Meta 4.1']);

        // Link PND objetivo -> ODS meta
        $pndObj->odsMetas()->attach($odsMeta->id);

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin',
            'orden' => 1,
            'ped_objetivo_estrategico_id' => $objetivo->id,
        ]);

        $this->actingAs($this->planeador);

        Livewire::test(PanelTransversal::class, ['tab' => 'ods'])
            ->assertSee('Educacion de Calidad')
            ->assertSee('PTR-001');
    }

    public function test_ur_tab_shows_team_ranking(): void
    {
        // Create a second team with a lower index
        $otherUser = User::factory()->withPersonalTeam()->create();
        $otherPrograma = ProgramaPresupuestario::create([
            'nombre' => 'Programa Otro',
            'clave' => 'POT-002',
            'team_id' => $otherUser->currentTeam->id,
            'ejercicio_fiscal' => (int) config('app.ejercicio_fiscal', now()->year),
        ]);

        EvaluacionPrograma::create([
            'programa_presupuestario_id' => $otherPrograma->id,
            'ejercicio_fiscal' => (int) config('app.ejercicio_fiscal', now()->year),
            'indice_eficacia' => 50.0000,
            'indicadores_evaluados' => 2,
            'indicadores_no_evaluados' => 0,
            'conteo_semaforos' => ['verde' => 1, 'amarillo' => 0, 'rojo' => 1, 'sin_dato' => 0],
            'desglose_niveles' => [],
            'configuracion_calculo' => [],
        ]);

        $this->actingAs($this->planeador);

        Livewire::test(PanelTransversal::class, ['tab' => 'ur'])
            ->assertSee('Por Unidad Responsable')
            ->assertSee($this->planeador->currentTeam->name)
            ->assertSee($otherUser->currentTeam->name)
            ->assertSee('75.00%')
            ->assertSee('50.00%');
    }

    public function test_anexo_tab_shows_indicators_by_theme(): void
    {
        $anexo = AnexoTransversal::create([
            'nombre' => 'Igualdad de Genero',
            'clave' => 'AT-IG',
            'activo' => true,
            'orden' => 1,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Componente test',
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Genero',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $indicador->anexosTransversales()->attach($anexo->id);

        $this->actingAs($this->planeador);

        Livewire::test(PanelTransversal::class, ['tab' => 'anexo'])
            ->assertSee('Igualdad de Genero')
            ->assertSee('AT-IG')
            ->assertSee('Indicador Genero')
            ->assertSee('PTR-001');
    }

    public function test_requires_exportar_reportes_permission(): void
    {
        $sinRol = User::factory()->withPersonalTeam()->create();

        $this->actingAs($sinRol);

        $response = $this->get(route('evaluation.transversal'));
        $response->assertStatus(403);
    }

    public function test_warning_shown_for_programs_without_ped_alignment(): void
    {
        // The program has no MirNivel with ped_objetivo_estrategico_id, so it should appear in sin_alineacion
        $this->actingAs($this->planeador);

        Livewire::test(PanelTransversal::class)
            ->assertSee('Programas sin alineacion PED')
            ->assertSee('PTR-001');
    }
}
