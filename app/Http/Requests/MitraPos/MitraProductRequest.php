<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use App\Http\Requests\MitraPos\Concerns\NormalizesRupiah;
use Illuminate\Validation\Rule;

class MitraProductRequest extends BaseRequest
{
    use NormalizesRupiah;

    /**
     * Money inputs arrive Indonesian-formatted from the rupiah-input JS
     * ("22.000"). Normalize before the numeric rules run — dots are
     * thousand separators, comma is the decimal mark.
     *
     * q_factor is typed as a percent ("20" = 20%) and stored as a fraction
     * (0.2), so the HPP/COGS accessors stay unchanged.
     */
    protected function prepareForValidation()
    {
        $price = $this->input('sale_price');

        if (is_string($price)) {
            $this->merge([
                'sale_price' => $this->normalizeRupiah($price),
            ]);
        }

        $percent = $this->input('q_factor');

        if (is_string($percent) || is_numeric($percent)) {
            $percent = str_replace(',', '.', trim((string) $percent));

            if (is_numeric($percent)) {
                $this->merge(['q_factor' => round((float) $percent / 100, 4)]);
            }
        }
    }

    public function rules(): array
    {
        $mitraParam = $this->route('mitra');
        $mitraId = $mitraParam instanceof \App\Models\Mitra ? $mitraParam->id : $mitraParam;

        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mitra_products', 'sku')
                    ->where(fn ($query) => $query->where('mitra_id', $mitraId))
                    // {product} is the SKU (slug routes), not the id — ignore
                    // must compare against the sku column or editing a product
                    // without changing its SKU false-positives as "taken".
                    ->ignore($this->route('product'), 'sku'),
            ],
            'name' => 'required|string|max:255',
            'variant' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'sub_category' => 'nullable|string|max:100',
            'q_factor' => 'required|numeric|min:0|max:1',
            'sale_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.mitra_material_id' => [
                'required',
                'distinct',
                Rule::exists('mitra_materials', 'id')->where(fn ($query) => $query->where('mitra_id', $mitraId)->whereNull('deleted_at')),
            ],
            'ingredients.*.qty' => 'required|numeric|min:0.001',
        ];
    }

    public function messages(): array
    {
        return [
            'q_factor.required' => 'Q-Factor wajib diisi. Isi dalam persen, contoh: 20 untuk 20%.',
            'q_factor.numeric' => 'Q-Factor harus berupa angka persen, contoh: 20 untuk 20%.',
            'q_factor.min' => 'Q-Factor tidak boleh kurang dari 0%.',
            'q_factor.max' => 'Q-Factor tidak boleh lebih dari 100%. Isi dalam persen, contoh: 20 untuk 20%.',
            'sale_price.required' => 'Harga jual wajib diisi.',
            'sale_price.numeric' => 'Harga jual harus berupa angka.',
            'ingredients.required' => 'Resep wajib punya minimal 1 bahan.',
            'ingredients.min' => 'Resep wajib punya minimal 1 bahan.',
            'ingredients.*.mitra_material_id.required' => 'Pilih material untuk setiap baris resep.',
            'ingredients.*.mitra_material_id.distinct' => 'Ada bahan yang dipilih dua kali di resep. Satu bahan cukup satu baris, gabungkan jumlahnya.',
            'ingredients.*.mitra_material_id.exists' => 'Material yang dipilih tidak ditemukan.',
            'ingredients.*.qty.required' => 'Isi jumlah untuk setiap bahan di resep.',
            'ingredients.*.qty.numeric' => 'Jumlah bahan harus berupa angka.',
            'ingredients.*.qty.min' => 'Jumlah bahan harus lebih dari 0.',
        ];
    }
}
