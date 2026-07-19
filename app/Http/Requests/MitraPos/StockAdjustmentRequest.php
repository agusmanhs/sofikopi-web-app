<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class StockAdjustmentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'delta' => 'required|numeric|not_in:0',
            'notes' => 'nullable|string|max:255',
        ];
    }
}
