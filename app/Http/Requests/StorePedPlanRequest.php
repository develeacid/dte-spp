<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'nivel_gobierno' => ['required', 'in:estatal,municipal'],
            'periodo_inicio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo_fin' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                'gt:periodo_inicio'
            ],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del plan es obligatorio.',
            'periodo_fin.gt' => 'El año de fin debe ser posterior al año de inicio.',
        ];
    }
}
