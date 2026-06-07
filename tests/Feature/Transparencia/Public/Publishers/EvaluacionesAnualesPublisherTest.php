<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\EvaluacionesAnualesPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class EvaluacionesAnualesPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_replica_evaluaciones(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-001',
            'ejercicio_fiscal' => 2026,
        ]);
        EvaluacionPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 87.50,
            'conteo_semaforos' => ['verde' => 5, 'amarillo' => 2, 'rojo' => 1, 'rojo_alto' => 3],
            'indicadores_evaluados' => 11,
            'indicadores_no_evaluados' => 0,
        ]);

        $publisher = app(EvaluacionesAnualesPublisher::class);
        $result = $publisher->publish(DatasetAbierto::factory()->create());

        $this->assertSame(1, $result['count']);

        $row = DB::connection('pgsql_public')->table('pub_evaluaciones_anuales')->first();
        $this->assertSame(2026, (int) $row->ejercicio_fiscal);
        $this->assertSame('PROG-001', $row->programa_clave);
        $this->assertSame('87.50', (string) $row->indice_eficacia);
        $this->assertSame(5, (int) $row->semaforos_verde);
        $this->assertSame(2, (int) $row->semaforos_amarillo);
        $this->assertSame(1, (int) $row->semaforos_rojo);
        $this->assertSame(3, (int) $row->semaforos_rojo_alto);
        $this->assertSame(11, (int) $row->indicadores_total);
    }

    public function test_publish_rojo_alto_default_cero_sin_conteo(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-002',
            'ejercicio_fiscal' => 2026,
        ]);
        EvaluacionPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 90.00,
            'conteo_semaforos' => ['verde' => 4, 'amarillo' => 1, 'rojo' => 0],
            'indicadores_evaluados' => 5,
            'indicadores_no_evaluados' => 0,
        ]);

        app(EvaluacionesAnualesPublisher::class)->publish(DatasetAbierto::factory()->create());

        $row = DB::connection('pgsql_public')->table('pub_evaluaciones_anuales')->first();
        $this->assertSame(0, (int) $row->semaforos_rojo_alto);
    }

    public function test_code_es_ds_04(): void
    {
        $this->assertSame('DS-04', app(EvaluacionesAnualesPublisher::class)->code());
    }
}
