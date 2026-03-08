<?php

namespace App\Http\Requests;

use App\Enums\TipoProgramaDerivado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramaDerivadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'tipo' => [
                'required',
                Rule::enum(TipoProgramaDerivado::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del programa es obligatorio.',
            'tipo.required' => 'Debe seleccionar un tipo de programa.',
            'tipo.Illuminate\Validation\Rules\Enum' => 'El tipo seleccionado no es válido.',
        ];
    }
}
