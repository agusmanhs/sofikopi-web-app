<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class AkuntansiCoaUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'is_active' => 'nullable|array',
            'is_active.*' => 'nullable',
            'opening_balance' => 'nullable|array',
            'opening_balance.*' => 'nullable|numeric|min:0',
        ];
    }
}
