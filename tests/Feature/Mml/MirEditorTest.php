<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNivelMir;
use App\Enums\TipoNodo;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MirEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test MIR', 'clave' => 'PT-MIR',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 5');
    }

    public function test_prellenado_genera_niveles_desde_eap(): void
    {
        // Setup: create árbol de objetivos with nodes
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);

        $objetivo = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL->value,
            'descripcion' => 'Mejorar la calidad educativa',
            'orden' => 1,
        ]);

        $fin = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::FIN_DIRECTO->value,
            'descripcion' => 'Contribuir al desarrollo',
            'orden' => 1,
        ]);

        $medio = ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::MEDIO_DIRECTO->value,
            'descripcion' => 'Becas entregadas',
            'orden' => 1,
        ]);

        // Create selected alternativa
        $alt = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt 1',
            'seleccionada' => true,
            'justificacion_seleccion' => 'Mejor opción',
        ]);
        $alt->nodos()->attach($medio->id);

        // Mount component triggers prefill
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa]);

        // Verify levels were created
        $this->assertDatabaseHas('mir_niveles', [
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Contribuir al desarrollo',
        ]);
        $this->assertDatabaseHas('mir_niveles', [
            'tipo_nivel' => 'proposito',
            'resumen_narrativo' => 'Mejorar la calidad educativa',
        ]);
        $this->assertDatabaseHas('mir_niveles', [
            'tipo_nivel' => 'componente',
            'resumen_narrativo' => 'Becas entregadas',
        ]);
    }

    public function test_editar_resumen_narrativo(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Original',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarNivel', $nivel->id, 'resumen_narrativo', 'Editado');

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $nivel->id,
            'resumen_narrativo' => 'Editado',
        ]);
    }

    public function test_agregar_componente(): void
    {
        // Create required Fin and Propósito
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('agregarComponente');

        $this->assertDatabaseHas('mir_niveles', [
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => 'componente',
        ]);
    }

    public function test_agregar_actividad_bajo_componente(): void
    {
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('agregarActividad', $componente->id);

        $this->assertDatabaseHas('mir_niveles', [
            'tipo_nivel' => 'actividad',
            'componente_id' => $componente->id,
        ]);
    }

    public function test_eliminar_actividad(): void
    {
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => 1,
        ]);
        $actividad = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $componente->id,
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('eliminarNivel', $actividad->id);

        $this->assertDatabaseMissing('mir_niveles', ['id' => $actividad->id]);
    }

    public function test_guardar_supuestos(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarNivel', $nivel->id, 'supuestos', 'Estabilidad económica');

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $nivel->id,
            'supuestos' => 'Estabilidad económica',
        ]);
    }

    public function test_no_prellenar_si_mir_tiene_niveles(): void
    {
        // Create existing level
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Ya existente',
            'orden' => 1,
        ]);

        // Create tree that would generate more levels
        $arbol = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);
        ArbolNodo::create([
            'arbol_id' => $arbol->id,
            'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL->value,
            'descripcion' => 'No debería aparecer',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa]);

        // Should still have only 1 level
        $this->assertEquals(1, $this->programa->mirNiveles()->count());
    }
}
