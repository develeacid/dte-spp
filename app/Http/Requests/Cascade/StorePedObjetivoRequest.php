<?php

namespace App\Http\Requests\Cascade;

class StorePedObjetivoRequest extends StorePedNodoRequest
{
    public function rules(): array
    {
        return array_merge(parent::commonRules(), [
            'ped_tema_id' => ['required', 'exists:ped_temas,id'],
        ]);
    }
}
