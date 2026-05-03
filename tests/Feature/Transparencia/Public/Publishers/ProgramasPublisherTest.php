<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Enums\EstadoPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\ProgramasPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class ProgramasPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_replica_programas_a_pub_programas(): void
    {
        ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-001',
            'nombre' => 'Programa Uno',
            'ejercicio_fiscal' => 2026,
            'estado' => EstadoPrograma::ACTIVO,
        ]);
        ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-002',
            'nombre' => 'Programa Dos',
            'ejercicio_fiscal' => 2026,
            'estado' => EstadoPrograma::CERRADO,
        ]);

        $publisher = app(ProgramasPublisher::class);
        $dataset = DatasetAbierto::factory()->create();

        $result = $publisher->publish($dataset);

        $this->assertSame(2, $result['count']);
        $this->assertSame(64, strlen($result['hash']));

        $pubRows = DB::connection('pgsql_public')->table('pub_programas')->get();
        $this->assertCount(2, $pubRows);
        $this->assertEqualsCanonicalizing(['PROG-001', 'PROG-002'], $pubRows->pluck('programa_clave')->all());

        $activo = $pubRows->firstWhere('programa_clave', 'PROG-001');
        $cerrado = $pubRows->firstWhere('programa_clave', 'PROG-002');
        $this->assertTrue((bool) $activo->activo);
        $this->assertFalse((bool) $cerrado->activo);
    }

    public function test_retire_vacia_pub_programas(): void
    {
        ProgramaPresupuestario::factory()->count(3)->create();
        $publisher = app(ProgramasPublisher::class);
        $dataset = DatasetAbierto::factory()->create();
        $publisher->publish($dataset);

        $publisher->retire($dataset);

        $this->assertSame(0, DB::connection('pgsql_public')->table('pub_programas')->count());
    }

    public function test_code_es_ds_01(): void
    {
        $this->assertSame('DS-01', app(ProgramasPublisher::class)->code());
    }
}
