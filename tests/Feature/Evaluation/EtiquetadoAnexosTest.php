<?php

namespace Tests\Feature\Evaluation;

use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Livewire\Tracking\PanelSeguimiento;
use App\Models\Evaluation\AnexoTransversal;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EtiquetadoAnexosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    private MirNivel $nivel;

    private Indicador $indicador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->assignRole('planeador');

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Anexos',
            'clave' => 'PA-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test Fin',
            'orden' => 1,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Tasa de cobertura',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);
    }

    public function test_sync_anexos_a_indicador(): void
    {
        $anexo1 = AnexoTransversal::create([
            'nombre' => 'Igualdad de Género', 'clave' => 'genero', 'orden' => 1, 'activo' => true,
        ]);
        $anexo2 = AnexoTransversal::create([
            'nombre' => 'Anticorrupción', 'clave' => 'anticorrupcion', 'orden' => 2, 'activo' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('syncAnexosTransversales', $this->indicador->id, [$anexo1->id, $anexo2->id]);

        $this->indicador->refresh();
        $this->assertEquals(2, $this->indicador->anexosTransversales()->count());
        $this->assertTrue($this->indicador->anexosTransversales->contains($anexo1));
        $this->assertTrue($this->indicador->anexosTransversales->contains($anexo2));

        // Sync again with only one — should detach the other
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('syncAnexosTransversales', $this->indicador->id, [$anexo1->id]);

        $this->indicador->refresh();
        $this->assertEquals(1, $this->indicador->anexosTransversales()->count());
        $this->assertTrue($this->indicador->anexosTransversales->contains($anexo1));
    }

    public function test_badges_en_panel_seguimiento(): void
    {
        $anexo = AnexoTransversal::create([
            'nombre' => 'Igualdad de Género', 'clave' => 'genero', 'orden' => 1, 'activo' => true,
        ]);

        $this->indicador->anexosTransversales()->attach($anexo->id);

        Livewire::actingAs($this->user)
            ->test(PanelSeguimiento::class)
            ->assertSee('Igualdad de Género');
    }

    public function test_indicador_sin_anexos(): void
    {
        // Ensure indicator has no anexos — should render without errors
        Livewire::actingAs($this->user)
            ->test(PanelSeguimiento::class)
            ->assertDontSee('Igualdad de Género')
            ->assertStatus(200);
    }
}
