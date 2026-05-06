<?php

namespace Tests\Feature\Transparencia\Public;

use App\Enums\EstadoDatasetAbierto;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

/**
 * Regresión: en Laravel 11+ auto-discovery por typehint registraba el listener
 * `DispatchSyncPublicTable` para `DatasetAbiertoPublicado|DatasetAbiertoRetirado`.
 * `AppServiceProvider::boot()` también lo registraba explícitamente. Resultado:
 * cada `publicar()` corría el job 2 veces. Idempotente pero costoso (DB + HTTP).
 *
 * Este test garantiza que un solo `publicar()` produce exactamente UNA
 * publicación en `transparencia_publicaciones`.
 */
class SyncPipelineSingleDispatchTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
        config(['queue.default' => 'sync']);
        Queue::fake([
            \App\Jobs\GeoBase\RegisterProgramOnGeoBase::class,
            \App\Jobs\GeoBase\DeactivateProgramOnGeoBase::class,
            \App\Jobs\GeoBase\SyncProgramaToGeoBase::class,
            \App\Jobs\GeoBase\SyncMirNivelToGeoBase::class,
        ]);
    }

    public function test_publicar_dispara_listener_exactamente_una_vez(): void
    {
        $user = User::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create([
            'padron_geobase_activo' => true,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente', 'resumen_narrativo' => 'C',
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'IND',
            'tipo' => 'gestion', 'dimension' => 'eficacia', 'frecuencia' => 'trimestral',
        ]);
        MetaPeriodo::create([
            'indicador_id' => $indicador->id, 'periodo' => 1,
            'meta_periodo' => 100, 'ejercicio_fiscal' => 2026,
        ]);

        Http::fake([
            '*cobertura-geografica-bulk*' => Http::response(['data' => []], 200),
        ]);

        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-G03',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        $this->actingAs($user);
        $dataset->publicar();

        $this->assertSame(
            1,
            TransparenciaPublicacion::where('dataset_clave', 'DS-G03')->count(),
            'publicar() debe disparar el listener UNA sola vez (no doble dispatch).'
        );
    }

    public function test_retirar_dispara_listener_exactamente_una_vez(): void
    {
        $user = User::factory()->create();

        Http::fake([
            '*cobertura-geografica-bulk*' => Http::response(['data' => []], 200),
        ]);

        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-G03',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        $this->actingAs($user);
        $dataset->retirar('motivo de prueba');

        $this->assertSame(
            1,
            TransparenciaPublicacion::where('dataset_clave', 'DS-G03')->where('action', 'retire')->count(),
            'retirar() debe disparar el listener UNA sola vez.'
        );
    }
}
