<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class MitraStockOpnameRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:255',
            'physical_qty' => 'required|array|min:1',
            'physical_qty.*' => 'required|numeric|min:0',
        ];
    }
}
