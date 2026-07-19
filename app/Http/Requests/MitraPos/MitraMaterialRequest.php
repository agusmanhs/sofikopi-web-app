<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class MitraMaterialRequest extends BaseRequest
{
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
                    ->ignore($this->route('material')),
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
