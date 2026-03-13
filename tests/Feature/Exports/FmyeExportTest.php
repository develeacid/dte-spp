<?php

namespace Tests\Feature\Exports;

use App\Exports\Pdf\FmyePdfExport;
use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\Cascade\AlineacionesSeeder;
use Database\Seeders\Cascade\PedSeeder;
use Database\Seeders\Cascade\ProgramasDerivadosSeeder;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\Mml\OdsSeeder;
use Database\Seeders\Mml\PndSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FmyeExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(OdsSeeder::class);
        $this->seed(PndSeeder::class);
        $this->seed(PedSeeder::class);
        $this->seed(ProgramasDerivadosSeeder::class);
        $this->seed(AlineacionesSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_fmye_pdf_generates_successfully(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $export = new FmyePdfExport($programa, 2025);
        $pdfContent = $export->generate();

        $this->assertNotEmpty($pdfContent);
    }

    public function test_fmye_pdf_contains_all_sections(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $team = $programa->team;

        $html = view('exports.pdf.fmye', [
            'programa' => $programa,
            'team' => $team,
            'ejercicioFiscal' => 2025,
            'encabezado' => config('evaluation.exports.encabezado'),
            'generadoEn' => now()->format('d/m/Y H:i'),
            'niveles' => $programa->mirNiveles()->with('indicadores')->get(),
            'alineacion' => [],
            'evaluacion' => null,
            'semaforoHistorico' => [],
            'titular' => $team->titular,
            'dependencia' => $team->name,
            'fecha' => now()->format('d/m/Y'),
        ])->render();

        $this->assertStringContainsString('Ficha de Monitoreo y Evaluación', $html);
        $this->assertStringContainsString($programa->clave, $html);
        $this->assertStringContainsString('Vo. Bo.', $html);
        $this->assertStringContainsString('Alineación Estratégica', $html);
        $this->assertStringContainsString('Índice de Eficacia', $html);
    }

    public function test_fmye_endpoint_works_for_planeador(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('evaluation.exportar.pdf', ['tipo' => 'fmye', 'id' => $programa->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_fmye_endpoint_forbidden_without_permission(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $user = User::factory()->create();
        $team = Team::where('clave_ur', 'SE-001')->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $response = $this->actingAs($user)
            ->get(route('evaluation.exportar.pdf', ['tipo' => 'fmye', 'id' => $programa->id]));

        $response->assertStatus(403);
    }
}
