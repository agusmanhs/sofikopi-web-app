<?php

namespace App\Http\Requests;

class MitraRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('mitra');
        return [
            'mitra_category_id' => 'required|exists:mitra_categories,id',
            'code' => 'required|string|unique:mitras,code,' . $id,
            'pic' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'titik_lokasi' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
