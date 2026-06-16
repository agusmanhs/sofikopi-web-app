<?php

namespace App\Http\Requests;

class DeliveryOrderAssignRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'assigned_to' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ];
    }
}
