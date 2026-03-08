<?php

namespace Tests\Feature\Mml;

use App\Contracts\LlmServiceInterface;
use App\DTOs\LlmValidationResult;
use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ValidacionSintaxisTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    private function mockLlm(bool $isValid, array $issues = [], string $suggestion = ''): void
    {
        $mock = Mockery::mock(LlmServiceInterface::class);
        $mock->shouldReceive('validate')
            ->once()
            ->andReturn(new LlmValidationResult(
                isValid: $isValid,
                issues: $issues,
                suggestion: $suggestion,
            ));
        $this->app->instance(LlmServiceInterface::class, $mock);
    }

    public function test_validar_sintaxis_fin_retorna_resultado(): void
    {
        $this->mockLlm(true);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Contribuir a la mejora educativa mediante becas',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarSintaxis', $nivel->id);

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $nivel->id,
            'sintaxis_valida' => true,
        ]);
    }

    public function test_resultado_se_persiste_con_timestamp(): void
    {
        $this->mockLlm(false, ['No inicia con Contribuir a'], 'Contribuir a X mediante Y');

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Mejorar la educación',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarSintaxis', $nivel->id);

        $nivel->refresh();
        $this->assertFalse($nivel->sintaxis_valida);
        $this->assertNotNull($nivel->sintaxis_validada_at);
        $this->assertStringContainsString('No inicia con Contribuir a', $nivel->sintaxis_observacion);
    }

    public function test_sugerencia_de_reescritura_disponible(): void
    {
        $this->mockLlm(false, ['Falta estructura'], 'Contribuir a la mejora mediante becas');

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Texto incorrecto',
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('validarSintaxis', $nivel->id);

        $nivel->refresh();
        $this->assertEquals('Contribuir a la mejora mediante becas', $nivel->sintaxis_sugerencia);
    }

    public function test_validacion_no_bloquea_guardado(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Texto original',
            'sintaxis_valida' => false,
            'orden' => 1,
        ]);

        // Can still edit even when validation fails
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('guardarNivel', $nivel->id, 'resumen_narrativo', 'Texto editado sin restricción');

        $this->assertDatabaseHas('mir_niveles', [
            'id' => $nivel->id,
            'resumen_narrativo' => 'Texto editado sin restricción',
        ]);
    }

    public function test_aceptar_sugerencia_actualiza_resumen(): void
    {
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Texto malo',
            'sintaxis_valida' => false,
            'sintaxis_sugerencia' => 'Contribuir a la mejora educativa mediante becas',
            'sintaxis_validada_at' => now(),
            'orden' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->call('aceptarSugerencia', $nivel->id);

        $nivel->refresh();
        $this->assertEquals('Contribuir a la mejora educativa mediante becas', $nivel->resumen_narrativo);
        $this->assertNull($nivel->sintaxis_valida);
        $this->assertNull($nivel->sintaxis_validada_at);
    }
}
