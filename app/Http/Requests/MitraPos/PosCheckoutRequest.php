<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class PosCheckoutRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.mitra_product_id' => 'required|integer|exists:mitra_products,id',
            'items.*.qty' => 'required|numeric|min:0.001',
            'discount' => 'nullable|numeric|min:0',
            'sales_mode' => 'required|in:dine_in,take_away,online',
            'payment_method' => 'required|in:cash,qris,transfer,edc',
        ];
    }
}
