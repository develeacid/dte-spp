<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\AlineacionEstrategicaPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class AlineacionEstrategicaPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_replica_alineacion_minima(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-001',
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Resumen del Fin',
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'proposito',
            'resumen_narrativo' => 'Resumen del Proposito',
        ]);

        $publisher = app(AlineacionEstrategicaPublisher::class);
        $result = $publisher->publish(DatasetAbierto::factory()->create());

        $this->assertSame(1, $result['count']);

        $row = DB::connection('pgsql_public')->table('pub_alineacion_estrategica')->first();
        $this->assertSame('PROG-001', $row->programa_clave);
        $this->assertSame('Resumen del Fin', $row->mir_fin_resumen);
        $this->assertSame('Resumen del Proposito', $row->mir_proposito_resumen);
    }

    public function test_code_es_ds_05(): void
    {
        $this->assertSame('DS-05', app(AlineacionEstrategicaPublisher::class)->code());
    }
}
