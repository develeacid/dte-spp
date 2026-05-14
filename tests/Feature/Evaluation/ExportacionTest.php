<?php

namespace Tests\Feature\Evaluation;

use App\Enums\TipoNivelMir;
use App\Exports\Pdf\MirPdfExport;
use App\Jobs\GenerarReportePdfJob;
use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportacionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $fakeBusInSetUp = false;

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
            'nombre' => 'Programa Export Test',
            'clave' => 'PEX-001',
            'team_id' => $this->planeador->currentTeam->id,
            'ejercicio_fiscal' => 2026,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Contribuir al bienestar',
            'supuestos' => 'Condiciones favorables',
            'orden' => 1,
        ]);

        Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Tasa de cobertura',
            'formula_texto' => '(A/B)*100',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'meta' => 80,
            'linea_base' => 50,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $this->evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 75.5000,
            'indicadores_evaluados' => 1,
            'indicadores_no_evaluados' => 0,
            'conteo_semaforos' => ['verde' => 1, 'amarillo' => 0, 'rojo' => 0, 'sin_dato' => 0],
            'desglose_niveles' => [
                'fin' => ['peso' => 0.40, 'promedio' => 75.5, 'indicadores_evaluados' => 1, 'indicadores_no_evaluados' => 0],
                'proposito' => ['peso' => 0.30, 'promedio' => null, 'indicadores_evaluados' => 0, 'indicadores_no_evaluados' => 0],
                'componente' => ['peso' => 0.20, 'promedio' => null, 'indicadores_evaluados' => 0, 'indicadores_no_evaluados' => 0],
                'actividad' => ['peso' => 0.10, 'promedio' => null, 'indicadores_evaluados' => 0, 'indicadores_no_evaluados' => 0],
            ],
            'configuracion_calculo' => ['fin' => 0.40, 'proposito' => 0.30, 'componente' => 0.20, 'actividad' => 0.10],
        ]);
    }

    public function test_genera_pdf_mir(): void
    {
        $export = new MirPdfExport($this->programa, 2026);
        $contenido = $export->generate();

        $this->assertNotEmpty($contenido);
        $this->assertStringStartsWith('%PDF', $contenido);
    }

    public function test_genera_excel_avance_trimestral(): void
    {
        $this->actingAs($this->planeador);

        $response = $this->get(route('evaluation.exportar.excel', [
            'tipo' => 'avance-trimestral',
            'id' => $this->programa->id,
            'trimestre' => 1,
            'ejercicio_fiscal' => 2026,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_despacha_job_async(): void
    {
        Queue::fake();

        $this->actingAs($this->planeador);

        $response = $this->postJson(route('evaluation.exportar.async', [
            'formato' => 'pdf',
            'tipo' => 'mir',
        ]), [
            'parametros' => [
                'programa_id' => $this->programa->id,
                'ejercicio_fiscal' => 2026,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['mensaje' => 'El reporte se está generando. Recibirás una notificación cuando esté listo.']);

        Queue::assertPushed(GenerarReportePdfJob::class, function ($job) {
            return $job->tipo === 'mir' && $job->userId === $this->planeador->id;
        });
    }

    public function test_descarga_pdf_con_permiso(): void
    {
        $this->actingAs($this->planeador);

        $response = $this->get(route('evaluation.exportar.pdf', [
            'tipo' => 'mir',
            'id' => $this->programa->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_403_sin_permiso_exportar_reportes(): void
    {
        $sinRol = User::factory()->withPersonalTeam()->create();

        $this->actingAs($sinRol);

        $response = $this->get(route('evaluation.exportar.pdf', [
            'tipo' => 'mir',
            'id' => $this->programa->id,
        ]));

        $response->assertStatus(403);
    }

    public function test_pdf_contiene_encabezado_y_periodo(): void
    {
        $export = new MirPdfExport($this->programa, 2026);
        $contenido = $export->generate();

        // The PDF content is binary, so we check the rendered view instead
        $view = view('exports.pdf.mir', [
            'programa' => $this->programa,
            'niveles' => $this->programa->mirNiveles()->with(['indicadores.mediosVerificacion', 'indicadores.variables'])->get(),
            'ejercicioFiscal' => 2026,
            'encabezado' => config('evaluation.exports.encabezado'),
            'generadoEn' => now()->format('d/m/Y H:i'),
        ])->render();

        $this->assertStringContainsString('Gobierno del Estado', $view);
        $this->assertStringContainsString('Ejercicio Fiscal: 2026', $view);
        $this->assertStringContainsString('PEX-001', $view);
        $this->assertStringContainsString('Generado:', $view);
    }
}
