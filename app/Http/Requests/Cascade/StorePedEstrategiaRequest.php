<?php

namespace App\Http\Requests\Cascade;

class StorePedEstrategiaRequest extends StorePedNodoRequest
{
    public function rules(): array
    {
        return array_merge(parent::commonRules(), [
            'ped_objetivo_estrategico_id' => ['required', 'exists:ped_objetivos_estrategicos,id'],
        ]);
    }
}
