<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Livewire\Tracking\CapturaAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalisisDesviacionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Avance $avance;

    private IndicadorVariable $varA;

    private IndicadorVariable $varB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PC-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa de cobertura',
            'formula_texto' => '(A/B) * 100',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100, 'activo_seguimiento' => true, 'orden' => 1,
        ]);

        $this->varA = IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'A', 'nombre' => 'Beneficiarios atendidos',
            'orden' => 1,
        ]);

        $this->varB = IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'B', 'nombre' => 'Poblacion objetivo',
            'orden' => 2,
        ]);

        $this->metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1, 'meta_periodo' => 100,
            'ejercicio_fiscal' => 2026, 'activo' => true,
        ]);

        $this->avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);
    }

    public function test_semaforo_amarillo_exige_los_cuatro_campos_del_analisis(): void
    {
        $this->actingAs($this->user);

        // (50/100)*100 = 50 vs meta 100 ascendente => rojo/amarillo (no verde)
        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 50)
            ->set("valores.{$this->varB->id}", 100)
            ->call('calcular')
            ->set('analisis.dato', '')
            ->set('analisis.causa', '')
            ->set('analisis.accion', '')
            ->set('analisis.proyeccion', '')
            ->call('guardar')
            ->assertHasErrors([
                'analisis.dato',
                'analisis.causa',
                'analisis.accion',
                'analisis.proyeccion',
            ]);
    }

    public function test_semaforo_rojo_con_cuatro_campos_guarda_analisis_estructurado(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 50)
            ->set("valores.{$this->varB->id}", 100)
            ->call('calcular')
            ->set('analisis.dato', 'El resultado fue 50% contra una meta de 100%.')
            ->set('analisis.causa', 'Retraso en la contratacion de personal operativo.')
            ->set('analisis.accion', 'Acelerar el proceso de contratacion en el siguiente trimestre.')
            ->set('analisis.proyeccion', 'Se espera alcanzar el 85% al cierre del ejercicio.')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->avance->refresh();

        $analisis = $this->avance->analisis_desviacion;
        $this->assertIsArray($analisis);
        $this->assertArrayHasKey('dato', $analisis);
        $this->assertArrayHasKey('causa', $analisis);
        $this->assertArrayHasKey('accion', $analisis);
        $this->assertArrayHasKey('proyeccion', $analisis);
        $this->assertEquals('El resultado fue 50% contra una meta de 100%.', $analisis['dato']);

        $this->assertNotEmpty($this->avance->justificacion_final);
        $this->assertStringContainsString(' | ', $this->avance->justificacion_final);
        $this->assertStringContainsString('Acción correctiva:', $this->avance->justificacion_final);
        $this->assertStringContainsString('Proyección:', $this->avance->justificacion_final);
    }

    public function test_semaforo_verde_no_exige_analisis(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 100)
            ->set("valores.{$this->varB->id}", 100)
            ->call('calcular')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->avance->refresh();
        $this->assertEquals('verde', $this->avance->semaforo_calculado);
        $this->assertNull($this->avance->analisis_desviacion);
    }

    public function test_recaptura_verde_limpia_justificacion_final_de_avance_previo_rojo(): void
    {
        // Avance previo en rojo con narrativa de desviación persistida.
        $this->avance->update([
            'semaforo_calculado' => 'rojo',
            'justificacion_final' => 'Dato: previo | Causa: previa | Acción correctiva: previa | Proyección: previa',
            'analisis_desviacion' => [
                'dato' => 'previo',
                'causa' => 'previa',
                'accion' => 'previa',
                'proyeccion' => 'previa',
            ],
        ]);
        $this->avance->refresh();

        $this->actingAs($this->user);

        // Re-captura que resulta en verde.
        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 100)
            ->set("valores.{$this->varB->id}", 100)
            ->call('calcular')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->avance->refresh();
        $this->assertEquals('verde', $this->avance->semaforo_calculado);
        $this->assertNull($this->avance->analisis_desviacion);
        $this->assertNull($this->avance->justificacion_final);
    }

    public function test_mount_precarga_analisis_existente(): void
    {
        $this->avance->update([
            'semaforo_calculado' => 'rojo',
            'analisis_desviacion' => [
                'dato' => 'Dato previo',
                'causa' => 'Causa previa',
                'accion' => 'Accion previa',
                'proyeccion' => 'Proyeccion previa',
            ],
        ]);
        $this->avance->refresh();

        $this->actingAs($this->user);

        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->assertSet('analisis.dato', 'Dato previo')
            ->assertSet('analisis.causa', 'Causa previa')
            ->assertSet('analisis.accion', 'Accion previa')
            ->assertSet('analisis.proyeccion', 'Proyeccion previa');
    }
}
