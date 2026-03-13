<?php

namespace Tests\Feature\Exports;

use App\Models\ProgramaPresupuestario;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvanceTrimestralExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_avance_trimestral_pdf_contains_vobo(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $team = $programa->team;

        $html = view('exports.pdf.partials.vobo', [
            'titular' => $team->titular,
            'dependencia' => $team->name,
            'fecha' => now()->format('d/m/Y'),
        ])->render();

        $this->assertStringContainsString('Vo. Bo.', $html);
        $this->assertStringContainsString($team->titular, $html);
        $this->assertStringContainsString('Firma', $html);
    }

    public function test_avance_trimestral_pdf_generates_with_vobo(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $export = new \App\Exports\Pdf\AvanceTrimestralPdfExport($programa, 2025, 1);
        $pdfContent = $export->generate();

        $this->assertNotEmpty($pdfContent);
    }

    public function test_avance_trimestral_pdf_endpoint_requires_permission(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $user = User::factory()->create();
        $team = Team::where('clave_ur', 'SE-001')->first();
        $user->forceFill(['current_team_id' => $team->id])->save();

        $response = $this->actingAs($user)
            ->get(route('evaluation.exportar.pdf', ['tipo' => 'avance-trimestral', 'id' => $programa->id, 'trimestre' => 1]));

        $response->assertStatus(403);
    }

    public function test_avance_trimestral_pdf_downloads_for_planeador(): void
    {
        $user = User::where('email', 'ele.planeador@gmail.com')->firstOrFail();
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('evaluation.exportar.pdf', ['tipo' => 'avance-trimestral', 'id' => $programa->id, 'trimestre' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
