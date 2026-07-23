<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class VoidTransactionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:255',
        ];
    }
}
