<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramaDerivadoObjetivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave' => ['required', 'string', 'max:20'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave del objetivo es obligatoria.',
            'descripcion.required' => 'La descripción del objetivo es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ];
    }
}
