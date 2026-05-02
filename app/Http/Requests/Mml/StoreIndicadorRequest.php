<?php

namespace App\Http\Requests\Mml;

use App\Enums\TipoNivelMir;
use App\Services\Mml\IndicadorReglasService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicadorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nivel = TipoNivelMir::tryFrom($this->input('nivel'));

        if (! $nivel) {
            return ['nivel' => 'required|in:'.implode(',', TipoNivelMir::values())];
        }

        $reglas = IndicadorReglasService::reglasParaNivel($nivel);

        return [
            'nombre' => 'required|string|max:255',
            'tipo' => ['required', Rule::in($reglas['tipos'])],
            'dimension' => ['required', Rule::in($reglas['dimensiones'])],
            'frecuencia' => ['required', Rule::in($reglas['frecuencias'])],
            'nivel' => ['required', Rule::in(TipoNivelMir::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo de indicador no es válido para este nivel.',
            'dimension.in' => 'La dimensión seleccionada no es válida para este nivel.',
            'frecuencia.in' => 'La frecuencia seleccionada no es válida para este nivel.',
        ];
    }
}
