<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\CatalogoUnidadMedida;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SemaforoUiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    private MirNivel $nivel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test Semáforo UI', 'clave' => 'PT-SEM',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $this->nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Nivel Fin',
            'orden' => 1,
        ]);
    }

    private function indicador(array $attrs = []): Indicador
    {
        return $this->nivel->indicadores()->create(array_merge([
            'nombre' => 'Indicador X',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'orden' => 1,
        ], $attrs));
    }

    private function rangos(array $overrides = []): array
    {
        return array_merge([
            'rango_verde_min' => null,
            'rango_verde_max' => null,
            'rango_amarillo_min' => null,
            'rango_amarillo_max' => null,
            'rango_rojo_min' => null,
            'rango_rojo_max' => null,
            'rango_rojo_alto_min' => null,
            'rango_rojo_alto_max' => null,
        ], $overrides);
    }

    public function test_guardar_rangos_validos_persiste_los_ocho_campos(): void
    {
        $indicador = $this->indicador(['meta' => 90]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarSemaforo', $indicador->id, $this->rangos([
                'rango_verde_min' => 80, 'rango_verde_max' => 100,
                'rango_amarillo_min' => 60, 'rango_amarillo_max' => 80,
                'rango_rojo_min' => 40, 'rango_rojo_max' => 60,
                'rango_rojo_alto_min' => 100, 'rango_rojo_alto_max' => 120,
            ]))
            ->assertHasNoErrors('semaforo_'.$indicador->id);

        $this->assertDatabaseHas('indicadores', [
            'id' => $indicador->id,
            'rango_verde_min' => 80,
            'rango_verde_max' => 100,
            'rango_amarillo_min' => 60,
            'rango_amarillo_max' => 80,
            'rango_rojo_min' => 40,
            'rango_rojo_max' => 60,
            'rango_rojo_alto_min' => 100,
            'rango_rojo_alto_max' => 120,
        ]);
    }

    public function test_rojo_min_cero_genera_error_y_bd_intacta(): void
    {
        $indicador = $this->indicador(['meta' => null]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarSemaforo', $indicador->id, $this->rangos([
                'rango_rojo_min' => 0, 'rango_rojo_max' => 30,
            ]))
            ->assertHasErrors('semaforo_'.$indicador->id);

        $indicador->refresh();
        $this->assertNull($indicador->rango_rojo_min);
        $this->assertNull($indicador->rango_rojo_max);
    }

    public function test_rangos_solapados_genera_error_y_bd_intacta(): void
    {
        $indicador = $this->indicador(['meta' => null]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarSemaforo', $indicador->id, $this->rangos([
                'rango_verde_min' => 80, 'rango_verde_max' => 100,
                'rango_amarillo_min' => 70, 'rango_amarillo_max' => 85,
            ]))
            ->assertHasErrors('semaforo_'.$indicador->id);

        $indicador->refresh();
        $this->assertNull($indicador->rango_verde_min);
        $this->assertNull($indicador->rango_amarillo_min);
    }

    public function test_pct_con_verde_max_mayor_a_100_genera_error(): void
    {
        $unidad = CatalogoUnidadMedida::firstOrCreate(['clave' => 'PCT'], ['nombre' => 'Porcentaje']);
        $indicador = $this->indicador(['meta' => null, 'unidad_medida_id' => $unidad->id]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarSemaforo', $indicador->id, $this->rangos([
                'rango_verde_min' => 80, 'rango_verde_max' => 150,
            ]))
            ->assertHasErrors('semaforo_'.$indicador->id);

        $indicador->refresh();
        $this->assertNull($indicador->rango_verde_max);
    }

    public function test_strings_vacios_persisten_como_null(): void
    {
        $indicador = $this->indicador([
            'meta' => null,
            'rango_verde_min' => 80,
            'rango_verde_max' => 100,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarSemaforo', $indicador->id, $this->rangos([
                'rango_verde_min' => '', 'rango_verde_max' => '',
            ]))
            ->assertHasNoErrors('semaforo_'.$indicador->id);

        $indicador->refresh();
        $this->assertNull($indicador->rango_verde_min);
        $this->assertNull($indicador->rango_verde_max);
    }

    public function test_valor_no_numerico_genera_error_de_validacion_y_bd_intacta(): void
    {
        $indicador = $this->indicador(['meta' => null]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarSemaforo', $indicador->id, $this->rangos([
                'rango_verde_min' => 'abc', 'rango_verde_max' => 100,
            ]))
            ->assertHasErrors('semaforo_'.$indicador->id);

        $indicador->refresh();
        $this->assertNull($indicador->rango_verde_min);
        $this->assertNull($indicador->rango_verde_max);
    }
}
