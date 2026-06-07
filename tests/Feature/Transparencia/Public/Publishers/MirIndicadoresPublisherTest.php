<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\MirIndicadoresPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class MirIndicadoresPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_replica_indicadores_a_pub_mir_indicadores(): void
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-001',
            'ejercicio_fiscal' => 2026,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Resumen del Fin',
        ]);
        \App\Models\Mml\MirSupuesto::create([
            'mir_nivel_id' => $nivel->id,
            'descripcion' => 'Supuestos del Fin',
            'orden' => 1,
        ]);
        Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Indicador Uno',
            'formula_texto' => 'A/B*100',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => 'ascendente',
            'meta' => 80.5,
            'linea_base' => 50.0,
        ]);

        $publisher = app(MirIndicadoresPublisher::class);
        $dataset = DatasetAbierto::factory()->create();

        $result = $publisher->publish($dataset);

        $this->assertSame(1, $result['count']);
        $this->assertSame(64, strlen($result['hash']));

        $row = DB::connection('pgsql_public')->table('pub_mir_indicadores')->first();
        $this->assertSame(2026, (int) $row->ejercicio_fiscal);
        $this->assertSame('PROG-001', $row->programa_clave);
        $this->assertSame('fin', $row->mir_nivel);
        $this->assertSame('Resumen del Fin', $row->resumen_narrativo);
        $this->assertSame('Supuestos del Fin', $row->supuestos);
        $this->assertSame('Indicador Uno', $row->indicador_nombre);
        $this->assertSame('A/B*100', $row->formula_texto);
        $this->assertSame('trimestral', $row->frecuencia);
        $this->assertSame('ascendente', $row->sentido);
    }

    public function test_code_es_ds_02(): void
    {
        $this->assertSame('DS-02', app(MirIndicadoresPublisher::class)->code());
    }
}
