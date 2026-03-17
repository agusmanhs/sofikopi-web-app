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
            'province_code' => 'nullable|string|exists:provinces,code',
            'regency_code' => 'nullable|string|exists:regencies,code',
            'district_code' => 'nullable|string|exists:districts,code',
            'titik_lokasi' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
