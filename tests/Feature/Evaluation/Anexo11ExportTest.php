<?php

namespace Tests\Feature\Evaluation;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Evaluation\Anexo11ExportService;
use App\Services\Evaluation\Anexo11ReportData;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Anexo11ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guarded_by_exportar_reportes_permission(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        // No role assigned -> no exportar_reportes permission.
        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);

        $this->actingAs($user)
            ->get(route('evaluation.anexo-11', $programa))
            ->assertForbidden();
    }

    public function test_returns_xlsx_with_expected_headers(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');

        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
            'nombre' => 'Becas Básicas',
        ]);

        $this->mock(Anexo11ExportService::class, function ($mock) use ($programa) {
            $mock->shouldReceive('build')
                ->once()
                ->with($programa->id, 'Becas Básicas')
                ->andReturn(new Anexo11ReportData(
                    programaId: $programa->id,
                    programaNombre: 'Becas Básicas',
                    totalBeneficiarios: 100,
                    porGenero: ['masculino' => 40, 'femenino' => 58, 'otro' => '<5'],
                    porGrupoEdad: [],
                    porPueblo: [],
                    porTipoDiscapacidad: [],
                    refreshedAt: '2026-04-24T12:00:00Z',
                ));
        });

        $response = $this->actingAs($user)->get(route('evaluation.anexo-11', $programa));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString('anexo-11', $response->headers->get('Content-Disposition'));
    }

    public function test_returns_404_when_program_has_no_geobase_link(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => false]);

        $this->actingAs($user)
            ->get(route('evaluation.anexo-11', $programa))
            ->assertNotFound();
    }
}
