<?php

namespace Tests\Feature\GeoBase;

use App\Events\GeoBase\EnrollmentStatusChanged;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateAvanceFromEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_change_triggers_coverage_refresh(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::factory()->create([
            'team_id' => $user->currentTeam->id,
            'padron_geobase_activo' => true,
        ]);

        Http::fake([
            "*/programs/{$programa->id}/coverage" => Http::response([
                'data' => [
                    'total_enrollments' => 501,
                    'aprobados' => 451,
                ],
            ], 200),
        ]);
        Http::preventStrayRequests();

        EnrollmentStatusChanged::dispatch(
            enrollmentId: 42,
            oldStatus: 'solicitado',
            newStatus: 'aprobado',
            sppProgramId: $programa->id,
            timestamp: now()->toIso8601String(),
        );

        Http::assertSent(function ($request) use ($programa) {
            return str_contains($request->url(), "/programs/{$programa->id}/coverage");
        });
    }

    public function test_enrollment_change_ignored_for_unlinked_program(): void
    {
        Http::fake();

        EnrollmentStatusChanged::dispatch(
            enrollmentId: 42,
            oldStatus: 'solicitado',
            newStatus: 'aprobado',
            sppProgramId: 999999, // no programa with this id
            timestamp: now()->toIso8601String(),
        );

        Http::assertNothingSent();
    }
}
