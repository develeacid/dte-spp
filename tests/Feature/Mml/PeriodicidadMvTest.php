<?php

namespace Tests\Feature\Mml;

use App\Enums\FrecuenciaMedicion;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PeriodicidadMvTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    private MirNivel $nivel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test Periodicidad MV', 'clave' => 'PT-PMV',
            'team_id' => $this->user->currentTeam->id,
        ]);
        $this->nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Propósito',
            'orden' => 1,
        ]);
    }

    private function indicador(?string $frecuencia): Indicador
    {
        return Indicador::create([
            'mir_nivel_id' => $this->nivel->id,
            'nombre' => 'Indicador test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => $frecuencia,
            'orden' => 1,
        ]);
    }

    private function mv(Indicador $indicador, ?string $frecuencia = null): MedioVerificacion
    {
        return MedioVerificacion::create([
            'indicador_id' => $indicador->id,
            'nombre' => 'MV test',
            'frecuencia' => $frecuencia,
            'orden' => 1,
        ]);
    }

    // --- Enum -----------------------------------------------------------------

    public function test_orden_devuelve_secuencia_correcta(): void
    {
        $this->assertSame(1, FrecuenciaMedicion::MENSUAL->orden());
        $this->assertSame(2, FrecuenciaMedicion::TRIMESTRAL->orden());
        $this->assertSame(3, FrecuenciaMedicion::SEMESTRAL->orden());
        $this->assertSame(4, FrecuenciaMedicion::ANUAL->orden());
        $this->assertSame(5, FrecuenciaMedicion::BIANUAL->orden());
        $this->assertSame(6, FrecuenciaMedicion::SEXENAL->orden());
    }

    // --- Normalización (método estático + integración con migración) ----------

    public function test_normalizar_frecuencia_mapea_variantes_legacy(): void
    {
        $this->assertSame('trimestral', MedioVerificacion::normalizarFrecuencia('Trimestral'));
        $this->assertSame('anual', MedioVerificacion::normalizarFrecuencia(' ANUAL '));
        $this->assertSame('mensual', MedioVerificacion::normalizarFrecuencia('mensual'));
        $this->assertNull(MedioVerificacion::normalizarFrecuencia('cada luna llena'));
        $this->assertNull(MedioVerificacion::normalizarFrecuencia(''));
    }

    public function test_migracion_normaliza_valores_legacy_e_ignora_no_reconocibles(): void
    {
        $indicador = $this->indicador('anual');

        $idTrim = DB::table('medios_verificacion')->insertGetId([
            'indicador_id' => $indicador->id, 'nombre' => 'A', 'frecuencia' => 'Trimestral', 'orden' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $idAnual = DB::table('medios_verificacion')->insertGetId([
            'indicador_id' => $indicador->id, 'nombre' => 'B', 'frecuencia' => ' ANUAL ', 'orden' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $idLuna = DB::table('medios_verificacion')->insertGetId([
            'indicador_id' => $indicador->id, 'nombre' => 'C', 'frecuencia' => 'cada luna llena', 'orden' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Ejecuta la lógica de la migración (idempotente).
        (require database_path('migrations/2026_06_07_000002_normalize_mv_frecuencia.php'))->up();

        $this->assertSame('trimestral', DB::table('medios_verificacion')->where('id', $idTrim)->value('frecuencia'));
        $this->assertSame('anual', DB::table('medios_verificacion')->where('id', $idAnual)->value('frecuencia'));
        $this->assertSame('cada luna llena', DB::table('medios_verificacion')->where('id', $idLuna)->value('frecuencia'));
    }

    // --- Validación cruzada B7 (Livewire) -------------------------------------

    public function test_b7_mv_menos_frecuente_que_indicador_falla(): void
    {
        $indicador = $this->indicador('trimestral');
        $mv = $this->mv($indicador);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV anual', 'frecuencia' => 'anual',
            ])
            ->assertHasErrors("frecuencia_mv_{$mv->id}");

        $this->assertNull($mv->fresh()->frecuencia);
    }

    public function test_b7_error_usa_key_namespaced_por_mv(): void
    {
        $indicador = $this->indicador('trimestral');
        $mvA = $this->mv($indicador);
        $mvB = MedioVerificacion::create([
            'indicador_id' => $indicador->id, 'nombre' => 'MV B', 'orden' => 2,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mvB->id, [
                'nombre' => 'MV anual', 'frecuencia' => 'anual',
            ])
            ->assertHasErrors("frecuencia_mv_{$mvB->id}")
            ->assertHasNoErrors("frecuencia_mv_{$mvA->id}");
    }

    public function test_modo_edicion_renderiza_select_frecuencia_de_mv_sin_excepcion(): void
    {
        $indicador = $this->indicador('trimestral');
        $this->mv($indicador, 'mensual');

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->set('editandoNivelId', $this->nivel->id)
            ->assertOk()
            ->assertSee('— Frecuencia —');
    }

    public function test_b7_mv_mas_frecuente_que_indicador_persiste(): void
    {
        $indicador = $this->indicador('trimestral');
        $mv = $this->mv($indicador);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV mensual', 'frecuencia' => 'mensual',
            ])
            ->assertHasNoErrors();

        $this->assertSame('mensual', $mv->fresh()->frecuencia);
    }

    public function test_b7_misma_frecuencia_persiste(): void
    {
        $indicador = $this->indicador('trimestral');
        $mv = $this->mv($indicador);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV trimestral', 'frecuencia' => 'trimestral',
            ])
            ->assertHasNoErrors();

        $this->assertSame('trimestral', $mv->fresh()->frecuencia);
    }

    public function test_frecuencia_vacia_es_nullable(): void
    {
        $indicador = $this->indicador('trimestral');
        $mv = $this->mv($indicador, 'mensual');

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV sin frecuencia', 'frecuencia' => null,
            ])
            ->assertHasNoErrors();

        $this->assertNull($mv->fresh()->frecuencia);
    }

    public function test_frecuencia_no_enum_entrante_falla_rule_in(): void
    {
        $indicador = $this->indicador('trimestral');
        $mv = $this->mv($indicador);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarMedioVerificacion', $mv->id, [
                'nombre' => 'MV inválido', 'frecuencia' => 'cada luna llena',
            ])
            ->assertHasErrors('frecuencia');
    }
}
