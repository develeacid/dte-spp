<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\ClavePresupuestalEditor;
use App\Models\Presupuesto\ClasificacionFuncional;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\Cascade\ClasificacionFuncionalSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClavePresupuestalEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ClasificacionFuncionalSeeder::class);
        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole('planeador');
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PC-001',
            'team_id' => $this->planeador->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);
    }

    private function idDe(string $nivel, string $clave): int
    {
        return ClasificacionFuncional::where('nivel', $nivel)->where('clave', $clave)->value('id');
    }

    public function test_planeador_accede_a_la_ruta(): void
    {
        $this->actingAs($this->planeador)
            ->get(route('mml.clave-presupuestal', $this->programa))
            ->assertOk();
    }

    public function test_operador_no_accede_a_la_ruta(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $this->actingAs($operador)
            ->get(route('mml.clave-presupuestal', $this->programa))
            ->assertForbidden();
    }

    public function test_guarda_los_campos_de_la_clave(): void
    {
        Livewire::actingAs($this->planeador)
            ->test(ClavePresupuestalEditor::class, ['programa' => $this->programa])
            ->set('grupo', 1)
            ->set('unidad_responsable', 1)
            ->set('unidad_ejecutora', 1)
            ->set('programa_clave', 144)
            ->set('subprograma', 2)
            ->set('proyecto', 0)
            ->set('actividad', 1)
            ->set('finalidad_id', $this->idDe('finalidad', '1'))
            ->set('funcion_id', $this->idDe('funcion', '11'))
            ->set('subfuncion_id', $this->idDe('subfuncion', '111'))
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('programa_presupuestarios', [
            'id' => $this->programa->id,
            'grupo' => 1,
            'unidad_responsable' => 1,
            'programa_clave' => 144,
            'finalidad_id' => $this->idDe('finalidad', '1'),
            'subfuncion_id' => $this->idDe('subfuncion', '111'),
        ]);
    }

    public function test_funciones_disponibles_se_filtran_por_finalidad(): void
    {
        $component = Livewire::actingAs($this->planeador)
            ->test(ClavePresupuestalEditor::class, ['programa' => $this->programa])
            ->set('finalidad_id', $this->idDe('finalidad', '1'));

        $claves = collect($component->get('funcionesDisponibles'))->pluck('clave')->all();

        $this->assertContains('11', $claves);   // Legislación (de finalidad 1)
        $this->assertNotContains('21', $claves); // Protección Ambiental (de finalidad 2)
    }

    public function test_guardar_rechaza_jerarquia_funcional_invalida(): void
    {
        Livewire::actingAs($this->planeador)
            ->test(ClavePresupuestalEditor::class, ['programa' => $this->programa])
            ->set('finalidad_id', $this->idDe('finalidad', '1'))
            ->set('funcion_id', $this->idDe('funcion', '11'))
            ->set('subfuncion_id', $this->idDe('subfuncion', '121')) // de función 12, no 11
            ->call('guardar')
            ->assertHasErrors('subfuncion_id');

        $this->assertDatabaseMissing('programa_presupuestarios', [
            'id' => $this->programa->id,
            'subfuncion_id' => $this->idDe('subfuncion', '121'),
        ]);
    }

    public function test_preview_refleja_la_clave_compuesta(): void
    {
        Livewire::actingAs($this->planeador)
            ->test(ClavePresupuestalEditor::class, ['programa' => $this->programa])
            ->set('grupo', 1)
            ->set('unidad_responsable', 1)
            ->set('unidad_ejecutora', 1)
            ->set('programa_clave', 144)
            ->set('subprograma', 2)
            ->set('proyecto', 0)
            ->set('actividad', 1)
            ->assertSee('10100114402000001');
    }
}
