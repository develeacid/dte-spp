<?php

namespace Tests\Feature\Exports;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\DesarrolloSeeder;
use Database\Seeders\QaTestingSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MirAprobadaExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DesarrolloSeeder::class);
        $this->seed(QaTestingSeeder::class);
    }

    public function test_mir_pdf_contains_transparency_footer(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $html = view('exports.pdf.mir', [
            'programa' => $programa,
            'niveles' => $programa->mirNiveles()->with(['indicadores.mediosVerificacion', 'indicadores.variables'])->get(),
            'ejercicioFiscal' => 2025,
            'encabezado' => config('evaluation.exports.encabezado'),
            'generadoEn' => now()->format('d/m/Y H:i'),
        ])->render();

        $this->assertStringContainsString('Art. 70 LGTAIP', $html);
    }

    public function test_mir_publica_route_accessible_by_any_authenticated_user(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();
        $user = User::factory()->create();
        $user->forceFill(['current_team_id' => $programa->team_id])->save();

        $response = $this->actingAs($user)
            ->get(route('evaluation.mir-publica', ['id' => $programa->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_mir_publica_route_requires_authentication(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $response = $this->get(route('evaluation.mir-publica', ['id' => $programa->id]));

        $response->assertRedirect();
    }
}
