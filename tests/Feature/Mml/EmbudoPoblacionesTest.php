<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\EmbudoPoblaciones;
use App\Models\Mml\PoblacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmbudoPoblacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);
    }

    public function test_componente_se_renderiza(): void
    {
        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->assertStatus(200)
            ->assertSee('Etapa 5');
    }

    public function test_muestra_atendida_y_cobertura_cuando_esta_sincronizada(): void
    {
        PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Personas',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 50000,
            'objetivo_cantidad' => 5000,
            'anio_ejercicio' => 2026,
            'atendida_cantidad' => 4000,
            'atendida_sync_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->assertSee('Atendida')
            ->assertSee('80')      // cobertura % (4000/5000)
            ->assertSee('4,000');  // atendida formateada
    }

    public function test_muestra_placeholder_si_atendida_no_sincronizada(): void
    {
        PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Personas',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 50000,
            'objetivo_cantidad' => 5000,
            'anio_ejercicio' => 2026,
        ]);

        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->assertSee('Padrón de Beneficiarios');
    }

    public function test_puede_guardar_poblaciones_validas(): void
    {
        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->set('unidad_medida', 'Niños')
            ->set('referencia_cantidad', 100000)
            ->set('potencial_cantidad', 20000)
            ->set('objetivo_cantidad', 5000)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('poblaciones_programa', [
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'Niños',
            'referencia_cantidad' => 100000,
            'potencial_cantidad' => 20000,
            'objetivo_cantidad' => 5000,
        ]);
    }

    public function test_valida_embudo_objetivo_menor_potencial(): void
    {
        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->set('unidad_medida', 'Familias')
            ->set('referencia_cantidad', 10000)
            ->set('potencial_cantidad', 5000)
            ->set('objetivo_cantidad', 6000)
            ->call('guardar')
            ->assertHasErrors('objetivo_cantidad');
    }

    public function test_valida_embudo_potencial_menor_referencia(): void
    {
        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->set('unidad_medida', 'Familias')
            ->set('referencia_cantidad', 5000)
            ->set('potencial_cantidad', 6000)
            ->set('objetivo_cantidad', 3000)
            ->call('guardar')
            ->assertHasErrors('potencial_cantidad');
    }

    public function test_carga_datos_existentes_al_montar(): void
    {
        PoblacionPrograma::create([
            'programa_id' => $this->programa->id,
            'unidad_medida' => 'MIPYMES',
            'referencia_cantidad' => 50000,
            'potencial_cantidad' => 10000,
            'objetivo_cantidad' => 2000,
            'anio_ejercicio' => $this->programa->ejercicio_fiscal,
        ]);

        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->assertSet('unidad_medida', 'MIPYMES')
            ->assertSet('referencia_cantidad', 50000)
            ->assertSet('objetivo_cantidad', 2000);
    }

    public function test_requiere_campos_obligatorios(): void
    {
        Livewire::actingAs($this->user)
            ->test(EmbudoPoblaciones::class, ['programa' => $this->programa])
            ->call('guardar')
            ->assertHasErrors(['unidad_medida', 'referencia_cantidad', 'potencial_cantidad', 'objetivo_cantidad']);
    }
}
