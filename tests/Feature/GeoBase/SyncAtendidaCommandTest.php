<?php

namespace Tests\Feature\GeoBase;

use App\Models\Mml\PoblacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncAtendidaCommandTest extends TestCase
{
    use RefreshDatabase;

    private int $ejercicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ejercicio = (int) now()->year;
        config([
            'services.geobase.url' => 'http://geobase-test:8081/api/v1/geobase',
            'services.geobase.token' => 'test-token-123',
        ]);
    }

    private function programa(bool $padronActivo, ?int $objetivo = 5000): ProgramaPresupuestario
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test '.fake()->unique()->word(),
            'clave' => 'PT-'.fake()->unique()->numerify('####'),
            'team_id' => $user->currentTeam->id,
            'padron_geobase_activo' => $padronActivo,
        ]);

        if ($objetivo !== null) {
            PoblacionPrograma::create([
                'programa_id' => $programa->id,
                'unidad_medida' => 'Personas',
                'referencia_cantidad' => 100000,
                'potencial_cantidad' => 50000,
                'objetivo_cantidad' => $objetivo,
                'anio_ejercicio' => $this->ejercicio,
            ]);
        }

        return $programa;
    }

    private function fakeAtendida(int $valor): void
    {
        Http::fake([
            '*/atendida-proposito*' => Http::response([
                'spp_program_id' => 1,
                'ejercicio' => $this->ejercicio,
                'poblacion_atendida' => $valor,
                'por_componente' => [],
            ], 200),
        ]);
        Http::preventStrayRequests();
    }

    public function test_actualiza_atendida_de_la_fila_del_ejercicio(): void
    {
        $programa = $this->programa(padronActivo: true, objetivo: 5000);
        $this->fakeAtendida(3200);

        $this->artisan('geobase:sync-atendida')->assertExitCode(0);

        $this->assertDatabaseHas('poblaciones_programa', [
            'programa_id' => $programa->id,
            'anio_ejercicio' => $this->ejercicio,
            'atendida_cantidad' => 3200,
        ]);
        $this->assertNotNull($programa->poblacion->fresh()->atendida_sync_at);
    }

    public function test_omite_programa_sin_embudo_del_ejercicio(): void
    {
        $this->programa(padronActivo: true, objetivo: null); // sin poblacion
        $this->fakeAtendida(999);

        $this->artisan('geobase:sync-atendida')
            ->expectsOutputToContain('sin embudo')
            ->assertExitCode(0);

        $this->assertDatabaseCount('poblaciones_programa', 0);
    }

    public function test_ignora_programas_sin_padron_activo(): void
    {
        $programa = $this->programa(padronActivo: false, objetivo: 5000);
        Http::fake();
        Http::preventStrayRequests(); // no debe llamar a geobase

        $this->artisan('geobase:sync-atendida')->assertExitCode(0);

        $this->assertNull($programa->poblacion->fresh()->atendida_cantidad);
    }

    public function test_es_idempotente(): void
    {
        $programa = $this->programa(padronActivo: true, objetivo: 5000);
        $this->fakeAtendida(3200);

        $this->artisan('geobase:sync-atendida')->assertExitCode(0);
        $this->artisan('geobase:sync-atendida')->assertExitCode(0);

        $this->assertEquals(3200, $programa->poblacion->fresh()->atendida_cantidad);
    }

    public function test_dry_run_no_escribe(): void
    {
        $programa = $this->programa(padronActivo: true, objetivo: 5000);
        $this->fakeAtendida(3200);

        $this->artisan('geobase:sync-atendida', ['--dry-run' => true])->assertExitCode(0);

        $this->assertNull($programa->poblacion->fresh()->atendida_cantidad);
    }
}
