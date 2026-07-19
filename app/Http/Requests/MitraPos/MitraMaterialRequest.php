<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class MitraMaterialRequest extends BaseRequest
{
    /**
     * Money inputs arrive Indonesian-formatted from the rupiah-input JS
     * ("180.000" / "180.000,50"). Normalize to machine format before the
     * numeric rules run — dots are thousand separators, comma is the
     * decimal mark.
     */
    protected function prepareForValidation()
    {
        $price = $this->input('price_per_pack');

        if (is_string($price)) {
            $this->merge([
                'price_per_pack' => str_replace(',', '.', str_replace('.', '', $price)),
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
                Rule::unique('mitra_materials', 'sku')
                    ->where(fn ($query) => $query->where('mitra_id', $mitraId))
                    // {material} is the SKU (slug routes), not the id — ignore
                    // must compare against the sku column or editing a material
                    // without changing its SKU false-positives as "taken".
                    ->ignore($this->route('material'), 'sku'),
            ],
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'unit' => 'required|string|max:20',
            'netto' => 'required|numeric|min:0.001',
            'price_per_pack' => 'required|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}
