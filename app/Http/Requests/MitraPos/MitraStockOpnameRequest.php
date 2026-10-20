<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class MitraStockOpnameRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:255',
            'physical_qty' => 'required|array|min:1',
            'physical_qty.*' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'physical_qty.required' => 'Isi stok fisik minimal satu bahan.',
            'physical_qty.array' => 'Data stok fisik tidak valid.',
            'physical_qty.min' => 'Isi stok fisik minimal satu bahan.',
            'physical_qty.*.numeric' => 'Stok fisik harus berupa angka.',
            'physical_qty.*.min' => 'Stok fisik tidak boleh negatif.',
            'notes.max' => 'Catatan maksimal 255 huruf.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $counted = collect($this->input('physical_qty', []))
                    ->filter(fn ($qty) => $qty !== null && $qty !== '');

                if ($counted->isEmpty() && ! $validator->errors()->has('physical_qty')) {
                    $validator->errors()->add('physical_qty', 'Isi stok fisik minimal satu bahan. Bahan yang dikosongkan tidak dihitung.');
                }
            },
        ];
    }
}
