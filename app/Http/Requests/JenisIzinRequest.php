<?php

namespace App\Http\Requests;

class JenisIzinRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('jenis_izin');

        return [
            'nama' => 'required|string|max:50',
            'kode' => "nullable|string|max:20|unique:jenis_izins,kode,{$id}",
            'butuh_surat' => 'nullable|boolean',
            'max_hari' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string',
            'is_aktif' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama jenis izin wajib diisi.',
            'kode.unique' => 'Kode jenis izin sudah digunakan.',
            'max_hari.min' => 'Maksimal hari minimal 1.',
        ];
    }
}
