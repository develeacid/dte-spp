<?php

namespace Tests\Feature\Transparencia\Public;

use App\Enums\EstadoDatasetAbierto;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class SyncPipelineIntegrationTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected bool $fakeBusInSetUp = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
        config(['queue.default' => 'sync']);
    }

    public function test_publicar_ds01_propaga_a_pub_programas_y_audita(): void
    {
        $user = User::factory()->create();
        ProgramaPresupuestario::factory()->count(3)->create();
        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-01',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        $this->actingAs($user);
        $dataset->publicar();

        $this->assertSame(3, DB::connection('pgsql_public')->table('pub_programas')->count());
        $this->assertSame(1, DB::connection('pgsql_public')->table('pub_datasets_catalogo')->where('codigo', 'DS-01')->count());

        $publish = TransparenciaPublicacion::firstWhere('dataset_clave', 'DS-01');
        $this->assertNotNull($publish);
        $this->assertTrue($publish->success);
        $this->assertSame('publish', $publish->action);
        $this->assertSame($user->id, $publish->publicado_por_user_id);
        $this->assertNotNull($publish->payload_hash);
        $this->assertSame(3, $publish->registros_count);
    }

    public function test_retirar_ds01_vacia_pub_programas_y_audita(): void
    {
        ProgramaPresupuestario::factory()->count(3)->create();
        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-01',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        $dataset->publicar();
        $this->assertSame(3, DB::connection('pgsql_public')->table('pub_programas')->count());

        $dataset->retirar('motivo de prueba');

        $this->assertSame(0, DB::connection('pgsql_public')->table('pub_programas')->count());
        $this->assertSame(0, DB::connection('pgsql_public')->table('pub_datasets_catalogo')->where('codigo', 'DS-01')->count());

        $retire = TransparenciaPublicacion::where('dataset_clave', 'DS-01')->where('action', 'retire')->first();
        $this->assertNotNull($retire);
        $this->assertTrue($retire->success);
    }

    public function test_re_publicar_es_idempotente_mismo_hash(): void
    {
        ProgramaPresupuestario::factory()->count(2)->create();
        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-01',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);
        $dataset->publicar();
        $hash1 = TransparenciaPublicacion::where('dataset_clave', 'DS-01')
            ->where('success', true)->latest('id')->first()->payload_hash;

        $dataset->retirar('test');
        $dataset->refresh();
        $dataset->status = EstadoDatasetAbierto::APROBADO;
        $dataset->save();
        $dataset->publicar();

        $hash2 = TransparenciaPublicacion::where('dataset_clave', 'DS-01')
            ->where('success', true)->where('action', 'publish')->latest('id')->first()->payload_hash;

        $this->assertSame($hash1, $hash2, 'Hash debe ser idempotente para mismo source data');
    }

    public function test_codigo_no_mapeado_no_falla_no_audita_publish(): void
    {
        $dataset = DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-00',
            'status' => EstadoDatasetAbierto::APROBADO,
        ]);

        $dataset->publicar();

        $this->assertSame(0, TransparenciaPublicacion::where('dataset_clave', 'DS-00')->count());
    }
}
