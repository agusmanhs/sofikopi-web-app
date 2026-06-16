<?php

namespace App\Http\Requests;

class InvoiceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:belum_lunas,lunas',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
