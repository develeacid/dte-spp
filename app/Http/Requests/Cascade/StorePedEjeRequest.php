<?php

namespace App\Http\Requests\Cascade;

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
}
