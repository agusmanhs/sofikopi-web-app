<?php

namespace App\Http\Requests;

class KantorRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('kantor');

        return [
            'nama' => 'required|string|max:100',
            'kode' => "nullable|string|max:20|unique:kantors,kode,{$id}",
            'alamat' => 'nullable|string',
            'titik_lokasi' => 'required|string|regex:/^-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?$/',
            'radius_meter' => 'nullable|integer|min:10|max:5000',
            'is_aktif' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kantor wajib diisi.',
            'titik_lokasi.required' => 'Titik lokasi wajib diisi.',
            'titik_lokasi.regex' => 'Format lokasi tidak valid. Contoh: -6.123, 106.456',
            'radius_meter.min' => 'Radius minimal 10 meter.',
            'radius_meter.max' => 'Radius maksimal 5000 meter.',
            'kode.unique' => 'Kode kantor sudah digunakan.',
        ];
    }
}
