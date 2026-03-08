<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UrCoadyuvanteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;
    private MirNivel $componente;
    private MirNivel $fin;
    private Team $otherTeam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin',
            'orden' => 1,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Propósito',
            'orden' => 1,
        ]);

        $this->componente = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Componente 1',
            'orden' => 1,
        ]);

        $this->otherTeam = Team::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'UR Coadyuvante Test',
            'personal_team' => false,
        ]);
    }

    public function test_asignar_ur_actualiza_team_id(): void
    {
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('asignarUrCoadyuvante', $this->componente->id, $this->otherTeam->id);

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $this->componente->id,
            'team_id' => $this->otherTeam->id,
        ]);
    }

    public function test_asignar_ur_crea_programa_team(): void
    {
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('asignarUrCoadyuvante', $this->componente->id, $this->otherTeam->id);

        $this->assertDatabaseHas('programa_team', [
            'programa_presupuestario_id' => $this->programa->id,
            'team_id' => $this->otherTeam->id,
            'rol' => 'coadyuvante',
        ]);
    }

    public function test_quitar_ur_elimina_de_programa_team_si_no_tiene_otros(): void
    {
        // Assign first
        $this->componente->update(['team_id' => $this->otherTeam->id]);
        $this->programa->equipos()->syncWithoutDetaching([
            $this->otherTeam->id => ['rol' => 'coadyuvante'],
        ]);

        // Remove assignment
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('asignarUrCoadyuvante', $this->componente->id, null);

        $this->assertDatabaseMissing('programa_team', [
            'programa_presupuestario_id' => $this->programa->id,
            'team_id' => $this->otherTeam->id,
        ]);
    }

    public function test_quitar_ur_mantiene_programa_team_si_tiene_otros(): void
    {
        // Create actividad with same team
        $actividad = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $this->componente->id,
            'resumen_narrativo' => 'Actividad',
            'team_id' => $this->otherTeam->id,
            'orden' => 1,
        ]);

        $this->componente->update(['team_id' => $this->otherTeam->id]);
        $this->programa->equipos()->syncWithoutDetaching([
            $this->otherTeam->id => ['rol' => 'coadyuvante'],
        ]);

        // Remove from componente but actividad still uses it
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('asignarUrCoadyuvante', $this->componente->id, null);

        // Should still exist because actividad uses same team
        $this->assertDatabaseHas('programa_team', [
            'programa_presupuestario_id' => $this->programa->id,
            'team_id' => $this->otherTeam->id,
        ]);
    }

    public function test_no_asigna_ur_a_fin(): void
    {
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('asignarUrCoadyuvante', $this->fin->id, $this->otherTeam->id);

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $this->fin->id,
            'team_id' => null,
        ]);
    }

    public function test_cambiar_ur_limpia_anterior(): void
    {
        $anotherTeam = Team::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Otra UR',
            'personal_team' => false,
        ]);

        // Assign first team
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('asignarUrCoadyuvante', $this->componente->id, $this->otherTeam->id);

        // Change to another team
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('asignarUrCoadyuvante', $this->componente->id, $anotherTeam->id);

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $this->componente->id,
            'team_id' => $anotherTeam->id,
        ]);

        // Old team removed from pivot (no other niveles using it)
        $this->assertDatabaseMissing('programa_team', [
            'programa_presupuestario_id' => $this->programa->id,
            'team_id' => $this->otherTeam->id,
        ]);
    }
}
