<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use App\Services\Transparencia\Publishing\AvancesTrimestralesPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class AvancesTrimestralesPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_replica_avances(): void
    {
        $user = User::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-001',
            'ejercicio_fiscal' => 2026,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente',
            'resumen_narrativo' => 'C1',
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'IND-A',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
        ]);
        $meta = MetaPeriodo::create([
            'indicador_id' => $indicador->id,
            'periodo' => 2,
            'meta_periodo' => 100.0,
            'ejercicio_fiscal' => 2026,
        ]);
        Avance::create([
            'meta_periodo_id' => $meta->id,
            'indicador_id' => $indicador->id,
            'resultado' => 80.0,
            'semaforo_calculado' => 'verde',
            'estado' => 'en_captura',
            'justificacion_final' => 'porque sí',
            'capturado_por' => $user->id,
        ]);

        $publisher = app(AvancesTrimestralesPublisher::class);
        $result = $publisher->publish(DatasetAbierto::factory()->create());

        $this->assertSame(1, $result['count']);
        $row = DB::connection('pgsql_public')->table('pub_avances_trimestrales')->first();
        $this->assertSame(2026, (int) $row->ejercicio_fiscal);
        $this->assertSame(2, (int) $row->trimestre);
        $this->assertSame('PROG-001', $row->programa_clave);
        $this->assertSame('componente', $row->mir_nivel);
        $this->assertSame('IND-A', $row->indicador_nombre);
        $this->assertSame('verde', $row->semaforo);
        $this->assertTrue((bool) $row->tiene_justificacion);
    }

    public function test_code_es_ds_03(): void
    {
        $this->assertSame('DS-03', app(AvancesTrimestralesPublisher::class)->code());
    }
}
