<?php

namespace App\Http\Requests;

class ShiftRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'divisi_id' => 'required|exists:divisis,id',
            'nama' => 'required|string|max:50',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'is_aktif' => 'nullable|boolean',
            'ikut_libur' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'divisi_id.required' => 'Divisi wajib dipilih.',
            'divisi_id.exists' => 'Divisi tidak valid.',
            'nama.required' => 'Nama shift wajib diisi.',
            'jam_masuk.required' => 'Jam masuk wajib diisi.',
            'jam_pulang.required' => 'Jam pulang wajib diisi.',
        ];
    }
}
