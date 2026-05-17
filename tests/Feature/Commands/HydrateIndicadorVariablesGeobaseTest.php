<?php

namespace Tests\Feature\Commands;

use App\Enums\SentidoIndicador;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class HydrateIndicadorVariablesGeobaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Programas con padron_geobase_activo=true disparan jobs GeoBase via observer.
        Bus::fake();
    }

    private function programaCompleto(
        string $clave,
        bool $activo,
        TipoNivelMir $tipoNivel = TipoNivelMir::COMPONENTE,
        int $numVariables = 2,
    ): array {
        $programa = ProgramaPresupuestario::factory()->create([
            'clave' => $clave,
            'padron_geobase_activo' => $activo,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => $tipoNivel,
            'resumen_narrativo' => "Nivel de {$clave}",
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => "Indicador de {$clave}",
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => SentidoIndicador::ASCENDENTE->value,
            'meta' => 100,
            'activo_seguimiento' => true,
            'orden' => 1,
        ]);

        $variables = [];
        for ($i = 1; $i <= $numVariables; $i++) {
            $variables[] = IndicadorVariable::create([
                'indicador_id' => $indicador->id,
                'simbolo' => chr(64 + $i), // A, B, C...
                'nombre' => "Variable {$i} de {$clave}",
                'orden' => $i,
            ]);
        }

        return ['programa' => $programa, 'nivel' => $nivel, 'indicador' => $indicador, 'variables' => $variables];
    }

    public function test_vincula_primera_variable_de_componente_cuando_programa_tiene_padron_activo(): void
    {
        ['variables' => $vars, 'nivel' => $nivel] = $this->programaCompleto('ACT-001', activo: true);

        $this->artisan('geobase:hydrate-indicador-variables')->assertSuccessful();

        $vars[0]->refresh();
        $vars[1]->refresh();

        $this->assertSame('component_coverage', $vars[0]->geobase_endpoint_type);
        $this->assertSame($nivel->id, $vars[0]->spp_reference_id);
        $this->assertSame('count', $vars[0]->geobase_value_key);
        // Solo la variable orden=1 se vincula; orden=2 sigue null.
        $this->assertNull($vars[1]->geobase_endpoint_type);
    }

    public function test_no_vincula_si_programa_no_tiene_padron_activo(): void
    {
        ['variables' => $vars] = $this->programaCompleto('INACT-001', activo: false);

        $this->artisan('geobase:hydrate-indicador-variables')->assertSuccessful();

        $vars[0]->refresh();
        $this->assertNull($vars[0]->geobase_endpoint_type);
    }

    public function test_ism_001_tambien_vincula_proposito_pero_otros_programas_no(): void
    {
        ['variables' => $varsIsm] = $this->programaCompleto('ISM-001', activo: true, tipoNivel: TipoNivelMir::PROPOSITO);
        ['variables' => $varsOtro] = $this->programaCompleto('EDU-003', activo: true, tipoNivel: TipoNivelMir::PROPOSITO);

        $this->artisan('geobase:hydrate-indicador-variables')->assertSuccessful();

        $varsIsm[0]->refresh();
        $varsOtro[0]->refresh();

        $this->assertSame('program_coverage', $varsIsm[0]->geobase_endpoint_type, 'ISM-001 propósito SÍ se vincula');
        $this->assertNull($varsOtro[0]->geobase_endpoint_type, 'Otros programas a nivel propósito NO se vinculan');
    }

    public function test_es_idempotente_no_sobreescribe_vinculaciones_existentes(): void
    {
        ['variables' => $vars] = $this->programaCompleto('IDEM-001', activo: true);
        $vars[0]->update([
            'geobase_endpoint_type' => 'program_coverage', // diferente del default que pondría el comando
            'spp_reference_id' => 999,
            'geobase_value_key' => 'total',
        ]);

        $this->artisan('geobase:hydrate-indicador-variables')
            ->expectsOutputToContain('ya vinculada')
            ->assertSuccessful();

        $vars[0]->refresh();
        // Mantiene el valor original, no lo pisa.
        $this->assertSame('program_coverage', $vars[0]->geobase_endpoint_type);
        $this->assertSame(999, $vars[0]->spp_reference_id);
        $this->assertSame('total', $vars[0]->geobase_value_key);
    }

    public function test_dry_run_no_escribe_en_bd(): void
    {
        ['variables' => $vars] = $this->programaCompleto('DRY-001', activo: true);

        $this->artisan('geobase:hydrate-indicador-variables --dry-run')
            ->expectsOutputToContain('dry-run')
            ->assertSuccessful();

        $vars[0]->refresh();
        $this->assertNull($vars[0]->geobase_endpoint_type, 'Dry-run no debe escribir');
    }

    public function test_sin_programas_activos_termina_ok(): void
    {
        $this->programaCompleto('INACT-1', activo: false);

        $this->artisan('geobase:hydrate-indicador-variables')
            ->expectsOutputToContain('No hay programas')
            ->assertSuccessful();
    }
}
