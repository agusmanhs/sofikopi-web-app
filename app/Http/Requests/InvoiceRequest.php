<?php

namespace App\Http\Requests;

class InvoiceRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|in:belum_lunas,lunas',
            'due_date' => 'nullable|date',
            'payment_method' => 'nullable|required_if:status,lunas|in:cash,cashless',
            'paid_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ];
    }
}
