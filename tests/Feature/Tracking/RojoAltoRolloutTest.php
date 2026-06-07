<?php

namespace Tests\Feature\Tracking;

use App\Enums\EstadoAvance;
use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Exports\Pdf\AvanceTrimestralPdfExport;
use App\Exports\Pdf\FmyePdfExport;
use App\Livewire\Evaluation\AcumuladoAnual;
use App\Livewire\Tracking\CapturaAvance;
use App\Livewire\Tracking\PanelSeguimiento;
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

class RojoAltoRolloutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Avance $avance;

    private Indicador $indicador;

    private MetaPeriodo $metaPeriodo;

    private IndicadorVariable $varA;

    private IndicadorVariable $varB;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->withPersonalTeam()->create();
        $this->user->assignRole('planeador');

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Rojo Alto', 'clave' => 'PRA-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin', 'orden' => 1,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Indicador sobrecumplido',
            'formula_texto' => '(A/B) * 100',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100, 'activo_seguimiento' => true, 'orden' => 1,
        ]);

        $this->varA = IndicadorVariable::create([
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'A', 'nombre' => 'Numerador', 'orden' => 1,
        ]);

        $this->varB = IndicadorVariable::create([
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'B', 'nombre' => 'Denominador', 'orden' => 2,
        ]);

        // meta_periodo = 25; resultado 40 => 160% => rojo_alto (umbral 130)
        $this->metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1, 'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026, 'activo' => true,
        ]);

        $this->avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);
    }

    public function test_captura_sobrecumplimiento_produce_rojo_alto_y_exige_analisis(): void
    {
        $this->actingAs($this->user);

        // A=80, B=200 => 40 => 160% de la meta (25) => rojo_alto
        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 80)
            ->set("valores.{$this->varB->id}", 200)
            ->call('calcular')
            ->assertSet('semaforoCalculado', 'rojo_alto')
            ->set('analisis.dato', '')
            ->set('analisis.causa', '')
            ->set('analisis.accion', '')
            ->set('analisis.proyeccion', '')
            ->call('guardar')
            ->assertHasErrors(['analisis.dato', 'analisis.causa', 'analisis.accion', 'analisis.proyeccion']);
    }

    public function test_rojo_alto_guarda_con_los_cuatro_campos_de_analisis(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CapturaAvance::class, ['avance' => $this->avance])
            ->set("valores.{$this->varA->id}", 80)
            ->set("valores.{$this->varB->id}", 200)
            ->call('calcular')
            ->assertSet('semaforoCalculado', 'rojo_alto')
            ->set('analisis.dato', 'El resultado superó la meta en 60%.')
            ->set('analisis.causa', 'Subestimación de la demanda al planear.')
            ->set('analisis.accion', 'Recalibrar la meta del próximo ejercicio.')
            ->set('analisis.proyeccion', 'Se espera estabilizar el indicador.')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->avance->refresh();
        $this->assertEquals('rojo_alto', $this->avance->semaforo_calculado);
    }

    public function test_panel_seguimiento_cuenta_rojo_alto_en_kpi(): void
    {
        $this->avance->update([
            'resultado' => 40,
            'semaforo_calculado' => 'rojo_alto',
        ]);

        $this->actingAs($this->user);

        Livewire::test(PanelSeguimiento::class)
            ->assertSee('Rojo alto');
    }

    public function test_acumulado_anual_color_badge_rojo_alto_es_purpura(): void
    {
        $component = new AcumuladoAnual;
        $reflection = new \ReflectionMethod($component, 'badgeSemaforo');
        $reflection->setAccessible(true);

        $html = $reflection->invoke($component, 'rojo_alto');

        $this->assertStringContainsString('bg-purple-100', $html);
        $this->assertStringContainsString('text-purple-800', $html);
    }

    public function test_fmye_pdf_export_incluye_conteo_rojo_alto(): void
    {
        $this->avance->update([
            'resultado' => 40,
            'semaforo_calculado' => 'rojo_alto',
            'estado' => EstadoAvance::APROBADO->value,
        ]);

        $export = new FmyePdfExport($this->programa, 2026);
        $reflection = new \ReflectionMethod($export, 'obtenerSemaforoHistorico');
        $reflection->setAccessible(true);

        $historico = $reflection->invoke($export);

        $this->assertArrayHasKey('T1', $historico);
        $this->assertArrayHasKey('rojo_alto', $historico['T1']);
        $this->assertEquals(1, $historico['T1']['rojo_alto']);
    }

    public function test_avance_trimestral_pdf_export_rinde_rojo_alto(): void
    {
        $this->avance->update([
            'resultado' => 40,
            'semaforo_calculado' => 'rojo_alto',
            'estado' => EstadoAvance::APROBADO->value,
        ]);

        $html = (new AvanceTrimestralPdfExport($this->programa, 2026, 1))->generateHtml();

        $this->assertStringContainsString('.semaforo-rojo_alto', $html);
        $this->assertStringContainsString('semaforo-rojo_alto', $html);
        $this->assertStringContainsString('Rojo alto', $html);
        $this->assertStringNotContainsString('Rojo_alto', $html);
    }
}
