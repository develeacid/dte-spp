<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedEjeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ped_plan_id' => ['required', 'exists:ped_planes,id'],
            'numero' => ['required', 'string', 'max:10'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.required' => 'El número de eje es obligatorio.',
            'nombre.required' => 'El nombre del eje es obligatorio.',
            'descripcion.max' => 'La descripción no puede exceder 500 caracteres.',
        ];
    }
}
