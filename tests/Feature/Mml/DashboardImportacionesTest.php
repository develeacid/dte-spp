<?php

namespace Tests\Feature\Mml;

use App\Models\Mml\ImportacionReporte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardImportacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    public function test_muestra_reportes_del_equipo(): void
    {
        $reporte = ImportacionReporte::create([
            'team_id' => $this->user->currentTeam->id,
            'archivo_original' => 'mir-test-2026.md',
            'formato' => 'md',
            'datos_parseados' => ['niveles' => []],
            'diagnostico' => null,
            'estado' => 'pendiente',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('mml.importaciones'));

        $response->assertStatus(200);
        $response->assertSee('mir-test-2026.md');
    }

    public function test_no_muestra_reportes_de_otro_equipo(): void
    {
        $otroUser = User::factory()->withPersonalTeam()->create();

        ImportacionReporte::create([
            'team_id' => $otroUser->currentTeam->id,
            'archivo_original' => 'mir-otro-equipo.xlsx',
            'formato' => 'xlsx',
            'datos_parseados' => ['niveles' => []],
            'diagnostico' => null,
            'estado' => 'pendiente',
            'created_by' => $otroUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('mml.importaciones'));

        $response->assertStatus(200);
        $response->assertDontSee('mir-otro-equipo.xlsx');
    }
}
