<?php

namespace Tests\Feature\Evaluation;

use App\Enums\EstadoAvance;
use App\Enums\TipoNivelMir;
use App\Livewire\Evaluation\EvaluacionProgramaView;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EvaluacionProgramaViewTest extends TestCase
{
    use RefreshDatabase;

    private User $planeador;

    private ProgramaPresupuestario $programa;

    private EvaluacionPrograma $evaluacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->planeador = User::factory()->withPersonalTeam()->create();
        $this->planeador->assignRole('planeador');

        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Evaluacion Test',
            'clave' => 'PEV-001',
            'team_id' => $this->planeador->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);

        $this->evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 72.5000,
            'indicadores_evaluados' => 3,
            'indicadores_no_evaluados' => 1,
            'conteo_semaforos' => ['verde' => 1, 'amarillo' => 1, 'rojo' => 1, 'sin_dato' => 1],
            'desglose_niveles' => [
                'fin' => ['peso' => 0.40, 'promedio' => 90.0, 'indicadores_evaluados' => 1, 'indicadores_no_evaluados' => 0],
                'proposito' => ['peso' => 0.30, 'promedio' => 60.0, 'indicadores_evaluados' => 1, 'indicadores_no_evaluados' => 0],
                'componente' => ['peso' => 0.20, 'promedio' => 40.0, 'indicadores_evaluados' => 1, 'indicadores_no_evaluados' => 0],
                'actividad' => ['peso' => 0.10, 'promedio' => null, 'indicadores_evaluados' => 0, 'indicadores_no_evaluados' => 1],
            ],
            'configuracion_calculo' => ['fin' => 0.40, 'proposito' => 0.30, 'componente' => 0.20, 'actividad' => 0.10],
        ]);
    }

    public function test_renderiza_evaluacion(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin test',
            'orden' => 1,
        ]);

        Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Alpha',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $this->actingAs($this->planeador);

        Livewire::test(EvaluacionProgramaView::class, ['evaluacion' => $this->evaluacion->id])
            ->assertSee('PEV-001')
            ->assertSee('Programa Evaluacion Test')
            ->assertSee('Resumen Ejecutivo')
            ->assertSee('Tablero de Semaforos')
            ->assertSee('72.50%');
    }

    public function test_tendencia_mejoro(): void
    {
        // Create previous year evaluacion
        EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2025,
            'indice_eficacia' => 50.0000,
            'indicadores_evaluados' => 1,
            'indicadores_no_evaluados' => 0,
            'conteo_semaforos' => ['verde' => 0, 'amarillo' => 1, 'rojo' => 0, 'sin_dato' => 0],
            'desglose_niveles' => [],
            'configuracion_calculo' => [],
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin test',
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Tendencia',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        // Previous year avance (lower)
        $mp2025 = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2025,
        ]);

        Avance::create([
            'meta_periodo_id' => $mp2025->id,
            'indicador_id' => $indicador->id,
            'resultado' => 50,
            'semaforo_calculado' => 'amarillo',
            'estado' => EstadoAvance::APROBADO->value,
        ]);

        // Current year avance (higher)
        $mp2026 = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 1,
            'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026,
        ]);

        Avance::create([
            'meta_periodo_id' => $mp2026->id,
            'indicador_id' => $indicador->id,
            'resultado' => 80,
            'semaforo_calculado' => 'verde',
            'estado' => EstadoAvance::APROBADO->value,
        ]);

        $this->actingAs($this->planeador);

        Livewire::test(EvaluacionProgramaView::class, ['evaluacion' => $this->evaluacion->id])
            ->assertSee('Comparativa vs Ejercicio Anterior')
            ->assertSee('Indicador Tendencia')
            ->assertSeeHtml('&#8593;');
    }

    public function test_indicadores_cronicos(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Proposito test',
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Cronico Rojo',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        // Create rojo avances for 2026 and 2025
        foreach ([2026, 2025] as $ejercicio) {
            $mp = MetaPeriodo::create([
                'indicador_id' => $indicador->id,
                'periodo' => 1,
                'meta_periodo' => 25,
                'ejercicio_fiscal' => $ejercicio,
            ]);

            Avance::create([
                'meta_periodo_id' => $mp->id,
                'indicador_id' => $indicador->id,
                'resultado' => 10,
                'semaforo_calculado' => 'rojo',
                'estado' => EstadoAvance::APROBADO->value,
            ]);
        }

        $this->actingAs($this->planeador);

        Livewire::test(EvaluacionProgramaView::class, ['evaluacion' => $this->evaluacion->id])
            ->assertSee('Indicadores Cronicos')
            ->assertSee('Indicador Cronico Rojo')
            ->assertSee('2 / 3');
    }

    public function test_requiere_permiso(): void
    {
        $operador = User::factory()->withPersonalTeam()->create();
        $operador->assignRole('operador');

        $this->actingAs($operador);

        // operador may or may not have exportar_reportes depending on seeder config;
        // create a user with no roles to be safe
        $sinRol = User::factory()->withPersonalTeam()->create();

        $this->actingAs($sinRol);

        $response = $this->get(route('evaluation.programa', $this->evaluacion));
        $response->assertStatus(403);
    }

    public function test_boton_anexo_11_visible_cuando_programa_tiene_geobase_link(): void
    {
        $this->programa->update(['geobase_program_id' => 42]);

        $this->actingAs($this->planeador);

        Livewire::test(EvaluacionProgramaView::class, ['evaluacion' => $this->evaluacion->id])
            ->assertSee('Anexo 11 PEF')
            ->assertSeeHtml(route('evaluation.anexo-11', $this->programa));
    }

    public function test_boton_anexo_11_oculto_cuando_programa_no_tiene_geobase_link(): void
    {
        // programa was created in setUp without geobase_program_id.
        $this->actingAs($this->planeador);

        Livewire::test(EvaluacionProgramaView::class, ['evaluacion' => $this->evaluacion->id])
            ->assertDontSee('Anexo 11 PEF');
    }

    public function test_sin_evaluacion_anterior(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Fin test',
            'orden' => 1,
        ]);

        Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Solo',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $this->actingAs($this->planeador);

        Livewire::test(EvaluacionProgramaView::class, ['evaluacion' => $this->evaluacion->id])
            ->assertSee('Resumen Ejecutivo')
            ->assertDontSee('Comparativa vs Ejercicio Anterior');
    }
}
