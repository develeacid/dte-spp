<?php

namespace Tests\Feature\Mml;

use App\Enums\DimensionIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\IndicadorReglasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EficaciaObligatoriaTest extends TestCase
{
    use RefreshDatabase;

    private function programaConNivel(TipoNivelMir $tipo = TipoNivelMir::COMPONENTE): array
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $nivel = MirNivel::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => $tipo->value,
        ]);

        return [$programa, $nivel];
    }

    public function test_nivel_con_solo_calidad_se_reporta(): void
    {
        [$programa, $nivel] = $this->programaConNivel();
        Indicador::factory()->create([
            'mir_nivel_id' => $nivel->id,
            'dimension' => DimensionIndicador::CALIDAD->value,
        ]);

        $reportados = IndicadorReglasService::nivelesSinEficacia($programa);

        $this->assertCount(1, $reportados);
        $this->assertSame($nivel->id, $reportados[0]['id']);
        $this->assertSame(TipoNivelMir::COMPONENTE->label(), $reportados[0]['tipo_nivel']);
        $this->assertArrayHasKey('resumen', $reportados[0]);
    }

    public function test_al_agregar_eficacia_ya_no_se_reporta(): void
    {
        [$programa, $nivel] = $this->programaConNivel();
        Indicador::factory()->create([
            'mir_nivel_id' => $nivel->id,
            'dimension' => DimensionIndicador::CALIDAD->value,
        ]);

        $this->assertCount(1, IndicadorReglasService::nivelesSinEficacia($programa));

        Indicador::factory()->create([
            'mir_nivel_id' => $nivel->id,
            'dimension' => DimensionIndicador::EFICACIA->value,
        ]);

        $this->assertCount(0, IndicadorReglasService::nivelesSinEficacia($programa->fresh()));
    }

    public function test_nivel_sin_indicadores_se_reporta(): void
    {
        [$programa, $nivel] = $this->programaConNivel();

        $reportados = IndicadorReglasService::nivelesSinEficacia($programa);

        $this->assertCount(1, $reportados);
        $this->assertSame($nivel->id, $reportados[0]['id']);
    }

    public function test_programa_completo_no_reporta_nada(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        foreach ([TipoNivelMir::FIN, TipoNivelMir::PROPOSITO, TipoNivelMir::COMPONENTE, TipoNivelMir::ACTIVIDAD] as $tipo) {
            $nivel = MirNivel::factory()->create([
                'programa_presupuestario_id' => $programa->id,
                'tipo_nivel' => $tipo->value,
            ]);
            Indicador::factory()->create([
                'mir_nivel_id' => $nivel->id,
                'dimension' => DimensionIndicador::EFICACIA->value,
            ]);
        }

        $this->assertCount(0, IndicadorReglasService::nivelesSinEficacia($programa));
    }

    public function test_editor_muestra_warning_cuando_falta_eficacia(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test EFICACIA', 'clave' => 'PT-EFI',
            'team_id' => $user->currentTeam->id,
        ]);
        $nivel = MirNivel::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
        ]);
        Indicador::factory()->create([
            'mir_nivel_id' => $nivel->id,
            'dimension' => DimensionIndicador::CALIDAD->value,
        ]);

        Livewire::actingAs($user)
            ->test(MirEditor::class, ['programa' => $programa])
            ->assertSee('Niveles sin indicador de EFICACIA');
    }

    public function test_editor_no_muestra_warning_con_mir_completa(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test EFICACIA OK', 'clave' => 'PT-EFI-OK',
            'team_id' => $user->currentTeam->id,
        ]);
        $nivel = MirNivel::factory()->create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
        ]);
        Indicador::factory()->create([
            'mir_nivel_id' => $nivel->id,
            'dimension' => DimensionIndicador::EFICACIA->value,
        ]);

        Livewire::actingAs($user)
            ->test(MirEditor::class, ['programa' => $programa])
            ->assertDontSee('Niveles sin indicador de EFICACIA');
    }
}
