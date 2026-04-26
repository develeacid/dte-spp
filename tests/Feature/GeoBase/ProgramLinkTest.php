<?php

namespace Tests\Feature\GeoBase;

use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProgramLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
    }

    public function test_padron_geobase_activo_can_be_set_to_true(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'padron_geobase_activo' => true,
        ]);

        $this->assertTrue($programa->padron_geobase_activo);
        $this->assertDatabaseHas('programa_presupuestarios', [
            'id' => $programa->id,
            'padron_geobase_activo' => true,
        ]);
    }

    public function test_padron_geobase_activo_defaults_to_false(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);

        // The DB column defaults to false; refresh to read the persisted value.
        $this->assertFalse($programa->fresh()->padron_geobase_activo);
    }

    public function test_has_geobase_link_returns_correct_boolean(): void
    {
        $linked = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'padron_geobase_activo' => true,
        ]);
        $unlinked = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'padron_geobase_activo' => false,
        ]);

        $this->assertTrue($linked->hasGeoBaseLink());
        $this->assertFalse($unlinked->hasGeoBaseLink());
    }

    public function test_can_fetch_geobase_coverage(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'padron_geobase_activo' => true,
        ]);

        Http::fake([
            "*/programs/{$programa->id}/coverage" => Http::response([
                'data' => [
                    'total_enrollments' => 500,
                    'aprobados' => 450,
                ],
            ], 200),
        ]);

        $coverage = $programa->getGeoBaseCoverage();

        $this->assertEquals(500, $coverage['data']['total_enrollments']);
    }

    public function test_geobase_coverage_returns_null_when_not_linked(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'padron_geobase_activo' => false,
        ]);

        $this->assertNull($programa->getGeoBaseCoverage());
    }
}
