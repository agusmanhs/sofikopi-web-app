<?php

namespace App\Http\Requests;

class JadwalKerjaRequest extends BaseRequest
{
    /**
     * Rules untuk menyimpan satu override jadwal (pegawai + tanggal spesifik).
     */
    public function rules(): array
    {
        return [
            'pegawai_id' => 'required|exists:pegawais,id',
            'tanggal' => 'required|date',
            'shift_id' => 'nullable|exists:shifts,id',
            'keterangan' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'pegawai_id.required' => 'Pegawai wajib dipilih.',
            'pegawai_id.exists' => 'Pegawai tidak valid.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'shift_id.exists' => 'Shift tidak valid.',
        ];
    }
}
