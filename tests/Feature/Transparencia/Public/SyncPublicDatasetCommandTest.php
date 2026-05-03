<?php

namespace Tests\Feature\Transparencia\Public;

use App\Enums\EstadoDatasetAbierto;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\Transparencia\TransparenciaPublicacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class SyncPublicDatasetCommandTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_comando_sincroniza_clave_dada(): void
    {
        ProgramaPresupuestario::factory()->count(2)->create();
        DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-01',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        $this->artisan('transparencia:sync-public', ['clave' => 'DS-01'])
            ->assertExitCode(0)
            ->expectsOutputToContain('DS-01');

        $this->assertSame(2, DB::connection('pgsql_public')->table('pub_programas')->count());
        $this->assertSame(1, TransparenciaPublicacion::where('dataset_clave', 'DS-01')->count());
    }

    public function test_comando_falla_con_clave_no_mapeada(): void
    {
        DatasetAbierto::factory()->create([
            'dataset_clave' => 'DS-00',
            'status' => EstadoDatasetAbierto::PUBLICADO,
        ]);

        $this->artisan('transparencia:sync-public', ['clave' => 'DS-00'])
            ->assertExitCode(1)
            ->expectsOutputToContain('sin publisher');
    }

    public function test_comando_falla_si_no_existe_dataset_publicado(): void
    {
        $this->artisan('transparencia:sync-public', ['clave' => 'DS-99'])
            ->assertExitCode(1);
    }
}
