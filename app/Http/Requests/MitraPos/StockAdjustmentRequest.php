<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;

class StockAdjustmentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'delta' => 'required|numeric|not_in:0',
            'notes' => 'required|string|min:3|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'delta.required' => 'Isi jumlah stok yang ditambah atau dikurangi.',
            'delta.numeric' => 'Jumlah stok harus berupa angka.',
            'delta.not_in' => 'Jumlah stok tidak boleh 0.',
            'notes.required' => 'Alasan penyesuaian stok wajib diisi.',
            'notes.min' => 'Alasan penyesuaian stok minimal 3 huruf.',
            'notes.max' => 'Alasan penyesuaian stok maksimal 255 huruf.',
        ];
    }
}
