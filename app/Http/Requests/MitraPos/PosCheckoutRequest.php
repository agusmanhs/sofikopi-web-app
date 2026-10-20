<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use App\Services\MitraPos\MitraContext;
use Illuminate\Validation\Rule;

class PosCheckoutRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            // Hanya produk milik mitra aktif: id milik mitra lain tidak boleh
            // dibedakan dari id yang tidak ada.
            'items.*.mitra_product_id' => [
                'required',
                'integer',
                Rule::exists('mitra_products', 'id')->where(fn ($query) => $query->where('mitra_id', app(MitraContext::class)->id())),
            ],
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
            'items.*.mitra_product_id.exists' => 'Ada produk di keranjang yang tidak ditemukan. Muat ulang halaman kasir.',
            'discount.numeric' => 'Diskon harus berupa angka.',
            'discount.min' => 'Diskon tidak boleh negatif.',
        ];
    }
}
