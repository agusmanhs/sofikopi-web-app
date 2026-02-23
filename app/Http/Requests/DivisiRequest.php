<?php

namespace App\Http\Requests;

class DivisiRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('divisi');

        return [
            'kode' => "nullable|string|max:20|unique:divisis,kode,{$id}",
            'nama' => 'required|string|max:100',
            'toleransi_terlambat' => 'nullable|integer|min:0|max:120',
            'is_aktif' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama divisi wajib diisi.',
            'kode.unique' => 'Kode divisi sudah digunakan.',
        ];
    }
}
