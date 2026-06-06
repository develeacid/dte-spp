<?php

namespace Tests\Feature\Mml;

use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Columnas opcionales del temario MIR (V2-A5/A7/A11):
 * - indicadores.linea_base_anio
 * - medios_verificacion.organismo, medios_verificacion.url
 * - indicador_variables.fuente
 *
 * Todas NULLABLE por diseño (metadatos opcionales, no load-bearing).
 */
class HardeningColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_indicador_persiste_linea_base_anio(): void
    {
        $indicador = Indicador::factory()->create(['linea_base_anio' => 2026]);

        $this->assertSame(2026, $indicador->fresh()->linea_base_anio);
        $this->assertDatabaseHas('indicadores', [
            'id' => $indicador->id,
            'linea_base_anio' => 2026,
        ]);
    }

    public function test_linea_base_anio_es_nullable(): void
    {
        $indicador = Indicador::factory()->create();

        $this->assertNull($indicador->fresh()->linea_base_anio);
    }

    public function test_linea_base_anio_se_castea_a_entero(): void
    {
        $indicador = Indicador::factory()->create(['linea_base_anio' => '2026']);

        $this->assertSame(2026, $indicador->fresh()->linea_base_anio);
    }

    public function test_medio_verificacion_persiste_organismo_y_url(): void
    {
        $indicador = Indicador::factory()->create();

        $medio = MedioVerificacion::create([
            'indicador_id' => $indicador->id,
            'nombre' => 'Registro administrativo',
            'organismo' => 'INEGI',
            'url' => 'https://www.inegi.org.mx/datos',
            'orden' => 1,
        ]);

        $fresh = $medio->fresh();
        $this->assertSame('INEGI', $fresh->organismo);
        $this->assertSame('https://www.inegi.org.mx/datos', $fresh->url);
    }

    public function test_organismo_y_url_son_nullable(): void
    {
        $indicador = Indicador::factory()->create();

        $medio = MedioVerificacion::create([
            'indicador_id' => $indicador->id,
            'nombre' => 'Registro administrativo',
            'orden' => 1,
        ]);

        $fresh = $medio->fresh();
        $this->assertNull($fresh->organismo);
        $this->assertNull($fresh->url);
    }

    public function test_indicador_variable_persiste_fuente(): void
    {
        $indicador = Indicador::factory()->create();

        $variable = IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'A',
            'nombre' => 'Numerador',
            'fuente' => 'Censo de Población y Vivienda 2020',
            'orden' => 1,
        ]);

        $this->assertSame('Censo de Población y Vivienda 2020', $variable->fresh()->fuente);
    }

    public function test_fuente_de_variable_es_nullable(): void
    {
        $indicador = Indicador::factory()->create();

        $variable = IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'A',
            'nombre' => 'Numerador',
            'orden' => 1,
        ]);

        $this->assertNull($variable->fresh()->fuente);
    }

    public function test_mir_editor_guarda_linea_base_anio(): void
    {
        $indicador = $this->indicadorEnPrograma();

        Livewire::actingAs($this->planeador())
            ->test(MirEditor::class, ['programa' => $indicador->mirNivel->programa])
            ->call('guardarLineaBaseAnio', $indicador->id, '2026');

        $this->assertSame(2026, $indicador->fresh()->linea_base_anio);
    }

    public function test_mir_editor_guarda_organismo_y_url_del_medio(): void
    {
        $indicador = $this->indicadorEnPrograma();
        $medio = MedioVerificacion::create([
            'indicador_id' => $indicador->id, 'nombre' => 'X', 'orden' => 1,
        ]);

        Livewire::actingAs($this->planeador())
            ->test(MirEditor::class, ['programa' => $indicador->mirNivel->programa])
            ->call('guardarMedioVerificacion', $medio->id, [
                'nombre' => 'Registro', 'organismo' => 'CONEVAL', 'url' => 'https://www.coneval.org.mx',
            ]);

        $fresh = $medio->fresh();
        $this->assertSame('CONEVAL', $fresh->organismo);
        $this->assertSame('https://www.coneval.org.mx', $fresh->url);
    }

    public function test_mir_editor_rechaza_linea_base_anio_fuera_de_rango(): void
    {
        $indicador = $this->indicadorEnPrograma();

        Livewire::actingAs($this->planeador())
            ->test(MirEditor::class, ['programa' => $indicador->mirNivel->programa])
            ->call('guardarLineaBaseAnio', $indicador->id, '1899')
            ->assertHasErrors(['linea_base_anio']);

        Livewire::actingAs($this->planeador())
            ->test(MirEditor::class, ['programa' => $indicador->mirNivel->programa])
            ->call('guardarLineaBaseAnio', $indicador->id, '3000')
            ->assertHasErrors(['linea_base_anio']);

        $this->assertNull($indicador->fresh()->linea_base_anio);

        Livewire::actingAs($this->planeador())
            ->test(MirEditor::class, ['programa' => $indicador->mirNivel->programa])
            ->call('guardarLineaBaseAnio', $indicador->id, '2026')
            ->assertHasNoErrors(['linea_base_anio']);

        $this->assertSame(2026, $indicador->fresh()->linea_base_anio);
    }

    public function test_mir_editor_acepta_url_de_mv_mayor_a_255_chars(): void
    {
        $indicador = $this->indicadorEnPrograma();
        $medio = MedioVerificacion::create([
            'indicador_id' => $indicador->id, 'nombre' => 'X', 'orden' => 1,
        ]);

        $urlLarga = 'https://www.inegi.org.mx/app/api/indicadores/desarrolladores/jsonxml/INDICATOR/'
            .str_repeat('1234567890', 25).'/es/0700/false/BISE/2.0/token-xxxxxxxx?type=json';
        $this->assertGreaterThan(255, strlen($urlLarga));

        Livewire::actingAs($this->planeador())
            ->test(MirEditor::class, ['programa' => $indicador->mirNivel->programa])
            ->call('guardarMedioVerificacion', $medio->id, [
                'nombre' => 'Registro', 'url' => $urlLarga,
            ])
            ->assertHasNoErrors(['url']);

        $this->assertSame($urlLarga, $medio->fresh()->url);
    }

    public function test_mir_editor_rechaza_url_invalida(): void
    {
        $indicador = $this->indicadorEnPrograma();
        $medio = MedioVerificacion::create([
            'indicador_id' => $indicador->id, 'nombre' => 'X', 'orden' => 1,
        ]);

        Livewire::actingAs($this->planeador())
            ->test(MirEditor::class, ['programa' => $indicador->mirNivel->programa])
            ->call('guardarMedioVerificacion', $medio->id, [
                'nombre' => 'Registro', 'url' => 'no-es-una-url',
            ])
            ->assertHasErrors(['url']);

        $this->assertNull($medio->fresh()->url);
    }

    public function test_mir_editor_guarda_fuente_de_variable(): void
    {
        $indicador = $this->indicadorEnPrograma();
        $variable = IndicadorVariable::create([
            'indicador_id' => $indicador->id, 'simbolo' => 'A', 'nombre' => 'Num', 'orden' => 1,
        ]);

        Livewire::actingAs($this->planeador())
            ->test(MirEditor::class, ['programa' => $indicador->mirNivel->programa])
            ->call('guardarVariable', $variable->id, [
                'simbolo' => 'A', 'nombre' => 'Num', 'fuente' => 'INEGI 2020',
            ]);

        $this->assertSame('INEGI 2020', $variable->fresh()->fuente);
    }

    private function planeador(): User
    {
        return User::factory()->withPersonalTeam()->create();
    }

    private function indicadorEnPrograma(): Indicador
    {
        $user = $this->planeador();
        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Programa Test',
            'clave' => 'PT-'.fake()->unique()->numberBetween(1, 99999),
            'team_id' => $user->currentTeam->id,
        ]);
        $nivel = MirNivel::factory()->create(['programa_presupuestario_id' => $programa->id]);

        return Indicador::factory()->create(['mir_nivel_id' => $nivel->id]);
    }
}
