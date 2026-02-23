<?php

namespace App\Http\Requests;

class HariLiburRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('hari_libur');
        return [
            'tanggal' => 'required|date|unique:hari_liburs,tanggal,' . $id,
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_nasional' => 'boolean',
            'is_cuti_bersama' => 'boolean',
        ];
    }
}