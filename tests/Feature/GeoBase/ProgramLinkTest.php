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

    public function test_programa_can_store_geobase_program_id(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'geobase_program_id' => 3,
        ]);

        $this->assertEquals(3, $programa->geobase_program_id);
        $this->assertDatabaseHas('programa_presupuestarios', [
            'id' => $programa->id,
            'geobase_program_id' => 3,
        ]);
    }

    public function test_geobase_program_id_is_nullable(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->assertNull($programa->geobase_program_id);
    }

    public function test_has_geobase_link_returns_correct_boolean(): void
    {
        $linked = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'geobase_program_id' => 3,
        ]);
        $unlinked = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->assertTrue($linked->hasGeoBaseLink());
        $this->assertFalse($unlinked->hasGeoBaseLink());
    }

    public function test_can_fetch_geobase_coverage(): void
    {
        Http::fake([
            '*/programs/3/coverage' => Http::response([
                'data' => [
                    'total_enrollments' => 500,
                    'aprobados' => 450,
                ],
            ], 200),
        ]);

        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
            'geobase_program_id' => 3,
        ]);

        $coverage = $programa->getGeoBaseCoverage();

        $this->assertEquals(500, $coverage['data']['total_enrollments']);
    }

    public function test_geobase_coverage_returns_null_when_not_linked(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $this->user->currentTeam->id,
        ]);

        $this->assertNull($programa->getGeoBaseCoverage());
    }
}
