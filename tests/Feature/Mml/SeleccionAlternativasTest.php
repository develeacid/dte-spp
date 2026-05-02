<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoArbol;
use App\Enums\TipoNodo;
use App\Livewire\Mml\SeleccionAlternativas;
use App\Models\Mml\Alternativa;
use App\Models\Mml\Arbol;
use App\Models\Mml\ArbolNodo;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SeleccionAlternativasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    private Arbol $arbolObjetivos;

    private ArbolNodo $objetivoCentral;

    private ArbolNodo $medio1;

    private ArbolNodo $medio2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->arbolObjetivos = Arbol::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo' => TipoArbol::OBJETIVOS->value,
        ]);
        $this->objetivoCentral = ArbolNodo::create([
            'arbol_id' => $this->arbolObjetivos->id,
            'tipo_nodo' => TipoNodo::OBJETIVO_CENTRAL->value,
            'descripcion' => 'Reducir la deserción escolar',
        ]);
        $this->medio1 = ArbolNodo::create([
            'arbol_id' => $this->arbolObjetivos->id,
            'parent_id' => $this->objetivoCentral->id,
            'tipo_nodo' => TipoNodo::MEDIO_DIRECTO->value,
            'descripcion' => 'Programa de becas',
            'orden' => 1,
        ]);
        $this->medio2 = ArbolNodo::create([
            'arbol_id' => $this->arbolObjetivos->id,
            'parent_id' => $this->objetivoCentral->id,
            'tipo_nodo' => TipoNodo::MEDIO_DIRECTO->value,
            'descripcion' => 'Mejora de infraestructura escolar',
            'orden' => 2,
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 4');
    }

    public function test_muestra_medios_del_arbol_objetivos(): void
    {
        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->assertSee('Programa de becas')
            ->assertSee('Mejora de infraestructura escolar');
    }

    public function test_crear_alternativa(): void
    {
        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->set('nuevaAlternativaNombre', 'Alternativa A')
            ->call('crearAlternativa')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alternativas', [
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alternativa A',
        ]);
    }

    public function test_asignar_nodos_a_alternativa(): void
    {
        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A',
        ]);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->call('toggleNodo', $alternativa->id, $this->medio1->id)
            ->assertHasNoErrors();

        $this->assertTrue($alternativa->nodos()->where('arbol_nodo_id', $this->medio1->id)->exists());
    }

    public function test_seleccionar_alternativa_con_justificacion(): void
    {
        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A',
        ]);
        $alternativa->nodos()->attach($this->medio1->id);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->set('justificacionSeleccion', 'Mayor viabilidad técnica y presupuestal')
            ->call('seleccionarAlternativa', $alternativa->id)
            ->assertHasNoErrors();

        $alternativa->refresh();
        $this->assertTrue($alternativa->seleccionada);
        $this->assertEquals('Mayor viabilidad técnica y presupuestal', $alternativa->justificacion_seleccion);
    }

    public function test_justificacion_requerida_para_seleccionar(): void
    {
        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A',
        ]);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->set('justificacionSeleccion', '')
            ->call('seleccionarAlternativa', $alternativa->id)
            ->assertHasErrors(['justificacionSeleccion' => 'required']);
    }

    public function test_solo_una_alternativa_seleccionada(): void
    {
        $alt1 = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A', 'seleccionada' => true,
            'justificacion_seleccion' => 'Primera opción',
        ]);
        $alt2 = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt B',
        ]);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->set('justificacionSeleccion', 'Mejor opción que la primera')
            ->call('seleccionarAlternativa', $alt2->id);

        $alt1->refresh();
        $alt2->refresh();

        $this->assertFalse($alt1->seleccionada);
        $this->assertTrue($alt2->seleccionada);
    }

    public function test_eliminar_alternativa(): void
    {
        $alternativa = Alternativa::create([
            'programa_presupuestario_id' => $this->programa->id,
            'nombre' => 'Alt A',
        ]);

        Livewire::actingAs($this->user)
            ->test(SeleccionAlternativas::class, ['programa' => $this->programa])
            ->call('eliminarAlternativa', $alternativa->id);

        $this->assertDatabaseMissing('alternativas', ['id' => $alternativa->id]);
    }
}
