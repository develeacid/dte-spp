<?php

namespace Tests\Feature\Commands;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HydratePadronGeobaseTest extends TestCase
{
    use RefreshDatabase;

    private function programaConComponente(string $clave, bool $activo): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => $clave,
            'padron_geobase_activo' => $activo,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE,
            'resumen_narrativo' => "Componente C1 de {$clave}",
            'orden' => 1,
        ]);

        return $programa;
    }

    public function test_hydrate_llama_register_para_cada_programa_activo(): void
    {
        Http::fake([
            '*/programs*' => Http::response(['data' => ['spp_program_id' => 1]], 200),
            '*/components*' => Http::response(['data' => ['spp_mir_nivel_id' => 1]], 200),
        ]);
        Http::preventStrayRequests();

        $this->programaConComponente('ACT-001', true);
        $this->programaConComponente('ACT-002', true);
        $this->programaConComponente('INACT-001', false);

        $this->artisan('geobase:hydrate-padron')
            ->expectsOutputToContain('2 programas')
            ->expectsOutputToContain('ACT-001')
            ->expectsOutputToContain('ACT-002')
            ->doesntExpectOutputToContain('INACT-001')
            ->assertSuccessful();

        // 2 POST programs + 2 POST components = 4 calls total
        Http::assertSentCount(4);
    }

    public function test_hydrate_es_idempotente(): void
    {
        Http::fake([
            '*/programs*' => Http::response(['data' => ['spp_program_id' => 1]], 200),
            '*/components*' => Http::response(['data' => ['spp_mir_nivel_id' => 1]], 200),
        ]);
        Http::preventStrayRequests();

        $this->programaConComponente('IDEM-001', true);

        $this->artisan('geobase:hydrate-padron')->assertSuccessful();
        $this->artisan('geobase:hydrate-padron')->assertSuccessful();

        // 2 corridas × (1 program POST + 1 component POST) = 4 calls
        Http::assertSentCount(4);
    }

    public function test_hydrate_continua_si_un_programa_falla(): void
    {
        // Programa OK-1 registra bien, FAIL-1 revienta en /programs, OK-2 registra bien.
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/programs')) {
                $payload = $request->data();
                if (($payload['clave'] ?? null) === 'FAIL-1') {
                    return Http::response(['error' => 'down'], 500);
                }

                return Http::response(['data' => ['spp_program_id' => 1]], 200);
            }

            return Http::response(['data' => ['spp_mir_nivel_id' => 1]], 200);
        });
        Http::preventStrayRequests();

        $this->programaConComponente('OK-1', true);
        $this->programaConComponente('FAIL-1', true);
        $this->programaConComponente('OK-2', true);

        $this->artisan('geobase:hydrate-padron')
            ->expectsOutputToContain('OK-1')
            ->expectsOutputToContain('FAIL-1')
            ->expectsOutputToContain('OK-2')
            ->expectsOutputToContain('fallaron')
            ->assertExitCode(1); // exit 1 si hubo errores parciales
    }

    public function test_hydrate_dry_run_no_llama_service(): void
    {
        Http::preventStrayRequests();

        $this->programaConComponente('DRY-001', true);
        $this->programaConComponente('DRY-002', true);

        $this->artisan('geobase:hydrate-padron --dry-run')
            ->expectsOutputToContain('DRY-001')
            ->expectsOutputToContain('DRY-002')
            ->expectsOutputToContain('dry-run')
            ->assertSuccessful();

        // 0 HTTP calls en dry-run
        Http::assertNothingSent();
    }

    public function test_hydrate_sin_programas_activos_termina_ok(): void
    {
        Http::preventStrayRequests();

        $this->programaConComponente('INACT-1', false);

        $this->artisan('geobase:hydrate-padron')
            ->expectsOutputToContain('No hay programas')
            ->assertSuccessful();

        Http::assertNothingSent();
    }
}
