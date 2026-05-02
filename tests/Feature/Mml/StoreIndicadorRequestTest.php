<?php

namespace Tests\Feature\Mml;

use App\Http\Requests\Mml\StoreIndicadorRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreIndicadorRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validateRequest(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreIndicadorRequest;
        $request->merge($data);

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_economia_en_nivel_fin_falla(): void
    {
        $validator = $this->validateRequest([
            'nombre' => 'Test',
            'tipo' => 'estrategico',
            'dimension' => 'economia',
            'frecuencia' => 'anual',
            'nivel' => 'fin',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('dimension', $validator->errors()->toArray());
    }

    public function test_gestion_en_nivel_fin_falla(): void
    {
        $validator = $this->validateRequest([
            'nombre' => 'Test',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'nivel' => 'fin',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tipo', $validator->errors()->toArray());
    }

    public function test_estrategico_en_nivel_fin_pasa(): void
    {
        $validator = $this->validateRequest([
            'nombre' => 'Test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'nivel' => 'fin',
        ]);

        $this->assertFalse($validator->fails());
    }

    public function test_mensual_en_nivel_fin_falla(): void
    {
        $validator = $this->validateRequest([
            'nombre' => 'Test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'mensual',
            'nivel' => 'fin',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('frecuencia', $validator->errors()->toArray());
    }

    public function test_componente_acepta_estrategico_y_gestion(): void
    {
        $validatorEstrategico = $this->validateRequest([
            'nombre' => 'Test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'nivel' => 'componente',
        ]);

        $validatorGestion = $this->validateRequest([
            'nombre' => 'Test',
            'tipo' => 'gestion',
            'dimension' => 'calidad',
            'frecuencia' => 'semestral',
            'nivel' => 'componente',
        ]);

        $this->assertFalse($validatorEstrategico->fails());
        $this->assertFalse($validatorGestion->fails());
    }

    public function test_actividad_solo_acepta_gestion(): void
    {
        $validatorGestion = $this->validateRequest([
            'nombre' => 'Test',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'mensual',
            'nivel' => 'actividad',
        ]);

        $validatorEstrategico = $this->validateRequest([
            'nombre' => 'Test',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'mensual',
            'nivel' => 'actividad',
        ]);

        $this->assertFalse($validatorGestion->fails());
        $this->assertTrue($validatorEstrategico->fails());
    }
}
