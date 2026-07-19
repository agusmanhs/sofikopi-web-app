<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class MitraProductRequest extends BaseRequest
{
    /**
     * Money inputs arrive Indonesian-formatted from the rupiah-input JS
     * ("22.000"). Normalize before the numeric rules run — dots are
     * thousand separators, comma is the decimal mark.
     */
    protected function prepareForValidation()
    {
        $price = $this->input('sale_price');

        if (is_string($price)) {
            $this->merge([
                'sale_price' => str_replace(',', '.', str_replace('.', '', $price)),
            ]);
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
            'q_factor' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.mitra_material_id' => 'required|exists:mitra_materials,id',
            'ingredients.*.qty' => 'required|numeric|min:0.001',
        ];
    }
}
