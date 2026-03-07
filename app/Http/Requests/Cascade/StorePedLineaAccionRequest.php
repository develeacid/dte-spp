<?php

namespace App\Http\Requests\Cascade;

class StorePedLineaAccionRequest extends StorePedNodoRequest
{
    public function rules(): array
    {
        return array_merge(parent::commonRules(), [
            'ped_estrategia_id' => ['required', 'exists:ped_estrategias,id'],
        ]);
    }
}
