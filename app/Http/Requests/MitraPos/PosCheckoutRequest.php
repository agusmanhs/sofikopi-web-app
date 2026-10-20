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
            'client_token' => 'nullable|string|max:64',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang masih kosong. Tambahkan minimal 1 produk.',
            'items.*.qty.min' => 'Jumlah produk minimal 1.',
            'discount.numeric' => 'Diskon harus berupa angka.',
            'discount.min' => 'Diskon tidak boleh negatif.',
        ];
    }
}
