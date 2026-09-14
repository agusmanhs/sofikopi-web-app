<?php

namespace App\Http\Requests\MitraPos;

use App\Http\Requests\BaseRequest;
use App\Models\Mitra;
use Illuminate\Validation\Rule;

class MitraStockPurchaseRequest extends BaseRequest
{
    public function rules(): array
    {
        $mitraParam = $this->route('mitra');
        $mitraId = $mitraParam instanceof Mitra ? $mitraParam->id : $mitraParam;

        return [
            'material_id' => [
                'required',
                Rule::exists('mitra_materials', 'id')->where(fn ($query) => $query->where('mitra_id', $mitraId)),
            ],
            'qty' => 'required|numeric|min:0.001|max:999999.999',
            'unit_price' => 'required|numeric|min:0|max:999999999',
            'notes' => 'nullable|string|max:255',
        ];
    }
}
