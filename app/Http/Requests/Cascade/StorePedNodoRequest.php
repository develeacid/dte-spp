<?php

namespace App\Http\Requests\Cascade;

use Illuminate\Foundation\Http\FormRequest;

abstract class StorePedNodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function commonRules(): array
    {
        return [
            'clave' => ['required', 'string', 'max:40'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave es obligatoria.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ];
    }
}
