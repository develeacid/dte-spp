<?php

namespace Tests\Feature\Padron;

use App\Enums\SystemRole;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\PadronPrograma;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\User;
use Database\Seeders\AsmPermissionsSeeder;
use Database\Seeders\JuridicoPermissionsSeeder;
use Database\Seeders\PadronPermissionsSeeder;
use Database\Seeders\PresupuestoPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GenerarSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_operador_genera_snapshot_y_aparece_en_lista(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PresupuestoPermissionsSeeder::class);
        $this->seed(JuridicoPermissionsSeeder::class);
        $this->seed(AsmPermissionsSeeder::class);
        $this->seed(PadronPermissionsSeeder::class);

        // Capture sync jobs dispatched by the MirNivelGeoBaseObserver.
        Queue::fake();

        $listCalls = 0;

        Http::fake(function (Request $request) use (&$listCalls) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'POST' && str_contains($url, '/snapshots/generate')) {
                return Http::response([
                    'data' => [
                        'id' => 4421,
                        'snapshot_hash' => 'a3f7c9e2deadbeef',
                        'row_count' => 1847,
                        'cutoff_date' => '2026-03-31T23:59:59Z',
                        'period' => '2026-Q1',
                        'valor_oficial' => 1820,
                    ],
                ], 201);
            }

            if (preg_match('#/snapshots/(\d+)$#', parse_url($url, PHP_URL_PATH) ?? '', $m)) {
                return Http::response([
                    'data' => [
                        'id' => (int) $m[1],
                        'snapshot_hash' => 'a3f7c9e2deadbeef',
                        'row_count' => 1847,
                        'valor_oficial' => 1847,
                        'cutoff_date' => '2026-03-31T23:59:59Z',
                        'period' => '2026-Q1',
                        'metadata' => [
                            'desagregados' => [
                                'por_genero' => ['femenino' => 950, 'masculino' => 897],
                                'por_grupo_edad' => [],
                                'por_indigena' => ['indigena' => 412, 'no_indigena' => 1435],
                                'por_discapacidad' => ['con_discapacidad' => 89, 'sin_discapacidad' => 1758],
                                'por_pueblo' => [],
                            ],
                        ],
                    ],
                ], 200);
            }

            if (str_contains($url, '/snapshots') && $method === 'GET') {
                $listCalls++;
                if ($listCalls === 1) {
                    return Http::response(['data' => [], 'meta' => ['total' => 0]], 200);
                }

                return Http::response([
                    'data' => [[
                        'id' => 4421,
                        'snapshot_hash' => 'a3f7c9e2deadbeef',
                        'cutoff_date' => '2026-03-31T23:59:59Z',
                        'period' => '2026-Q1',
                        'valor_oficial' => 1847,
                        'row_count' => 1847,
                    ]],
                    'meta' => ['total' => 1],
                ], 200);
            }

            return Http::response([], 404);
        });
        Http::preventStrayRequests();

        $programa = ProgramaPresupuestario::factory()->create(['padron_geobase_activo' => true]);
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => 'Componente de prueba',
            'orden' => 1,
        ]);

        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole(SystemRole::OPERADOR->value);

        Livewire::actingAs($user)
            ->test(PadronPrograma::class, ['programa' => $programa])
            ->assertSet('componenteSeleccionado', $componente->id)
            ->assertSet('snapshotsHistoricos', [])
            ->call('generarSnapshot')
            ->assertSet('snapshotsHistoricos', fn ($v) => count($v) === 1)
            ->assertSet('snapshotIdSeleccionado', 4421)
            ->assertSet('kpis.total', 1847);

        $this->assertDatabaseHas('avance_evidencias', [
            'hash_archivo' => 'a3f7c9e2deadbeef',
            'geobase_snapshot_id' => 4421,
        ]);
        $this->assertSame(1, AvanceEvidencia::count());

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'padron-snapshot',
            'subject_type' => AvanceEvidencia::class,
            'causer_id' => $user->id,
        ]);
    }
}
