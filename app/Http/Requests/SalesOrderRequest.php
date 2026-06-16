<?php

namespace App\Http\Requests;

class SalesOrderRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mitra_id' => 'nullable|exists:mitras,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string',
            'delivery_type' => 'required|in:delivery,self_pickup',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'additional_discount' => 'nullable|numeric|min:0',
            'use_tax' => 'nullable|boolean',
        ];
    }
}
