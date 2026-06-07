<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Mml\Indicador;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Services\Transparencia\Publishing\MediosVerificacionPublisher;
use App\Services\Transparencia\Publishing\PublisherResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class MediosVerificacionPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    private function crearMedioVerificacion(): MedioVerificacion
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => 'PROG-001',
            'ejercicio_fiscal' => 2026,
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente',
            'resumen_narrativo' => 'Resumen del Componente',
            'supuestos' => 'Supuestos del Componente',
        ]);
        $indicador = Indicador::create([
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

        return MedioVerificacion::create([
            'indicador_id' => $indicador->id,
            'nombre' => 'Registro Administrativo',
            'descripcion' => 'Registro mensual de beneficiarios',
            'fuente' => 'Sistema interno',
            'organismo' => 'Secretaría de Desarrollo Económico',
            'url' => 'https://datos.example.gob.mx/mv-001',
            'frecuencia' => 'mensual',
            'orden' => 1,
        ]);
    }

    public function test_code_es_ds_07(): void
    {
        $this->assertSame('DS-07', app(MediosVerificacionPublisher::class)->code());
    }

    public function test_resolver_resuelve_ds_07(): void
    {
        $resolver = app(PublisherResolver::class);

        $publisher = $resolver->for('DS-07');

        $this->assertInstanceOf(MediosVerificacionPublisher::class, $publisher);
        $this->assertSame('DS-07', $publisher->code());
    }

    public function test_publish_replica_medios_a_pub_medios_verificacion(): void
    {
        $this->crearMedioVerificacion();

        $publisher = app(MediosVerificacionPublisher::class);
        $dataset = DatasetAbierto::factory()->create();

        $result = $publisher->publish($dataset);

        $this->assertSame(1, $result['count']);
        $this->assertSame(64, strlen($result['hash']));

        $row = DB::connection('pgsql_public')->table('pub_medios_verificacion')->first();
        $this->assertSame(2026, (int) $row->ejercicio_fiscal);
        $this->assertSame('PROG-001', $row->programa_clave);
        $this->assertSame('componente', $row->mir_nivel);
        $this->assertSame('Indicador Uno', $row->indicador_nombre);
        $this->assertSame('Registro Administrativo', $row->mv_nombre);
        $this->assertSame('Registro mensual de beneficiarios', $row->descripcion);
        $this->assertSame('Sistema interno', $row->fuente);
        $this->assertSame('Secretaría de Desarrollo Económico', $row->organismo);
        $this->assertSame('https://datos.example.gob.mx/mv-001', $row->url);
        $this->assertSame('mensual', $row->frecuencia);
    }

    public function test_hash_idempotente_entre_corridas(): void
    {
        $this->crearMedioVerificacion();

        $publisher = app(MediosVerificacionPublisher::class);
        $dataset = DatasetAbierto::factory()->create();

        $primera = $publisher->publish($dataset);
        $segunda = $publisher->publish($dataset);

        $this->assertSame($primera['hash'], $segunda['hash']);
        $this->assertSame($primera['count'], $segunda['count']);
    }

    public function test_retire_limpia_la_tabla(): void
    {
        $this->crearMedioVerificacion();

        $publisher = app(MediosVerificacionPublisher::class);
        $dataset = DatasetAbierto::factory()->create();

        $publisher->publish($dataset);
        $this->assertSame(1, DB::connection('pgsql_public')->table('pub_medios_verificacion')->count());

        $publisher->retire($dataset);
        $this->assertSame(0, DB::connection('pgsql_public')->table('pub_medios_verificacion')->count());
    }
}
