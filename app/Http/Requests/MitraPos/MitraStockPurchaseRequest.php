<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\MitraPos\Concerns\NormalizesRupiah;
use App\Models\Mitra;
use Illuminate\Validation\Rule;

class MitraStockPurchaseRequest extends BaseRequest
{
    use NormalizesRupiah;

    protected function prepareForValidation()
    {
        $price = $this->input('unit_price');

        if (is_string($price)) {
            $this->merge(['unit_price' => $this->normalizeRupiah($price)]);
        }
    }

    public function rules(): array
    {
        $mitraParam = $this->route('mitra');
        $mitraId = $mitraParam instanceof Mitra ? $mitraParam->id : $mitraParam;

        return [
            'material_id' => [
                'required',
                Rule::exists('mitra_materials', 'id')->where(fn ($query) => $query->where('mitra_id', $mitraId)->whereNull('deleted_at')),
            ],
            'qty' => 'required|numeric|min:0.001|max:999999.999',
            'unit_price' => 'required|numeric|gt:0|max:999999999',
            'notes' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'material_id.required' => 'Pilih material yang dibeli.',
            'qty.required' => 'Jumlah beli wajib diisi.',
            'qty.min' => 'Jumlah beli harus lebih dari 0.',
            'unit_price.required' => 'Harga beli per satuan wajib diisi.',
            'unit_price.numeric' => 'Harga beli harus berupa angka.',
            'unit_price.gt' => 'Harga beli tidak boleh Rp 0. Isi harga per satuan (bukan harga satu pack).',
        ];
    }
}
