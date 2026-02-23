<?php

namespace App\Http\Requests;

class AbsensiRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'device' => 'nullable|string|max:100',
            'shift_id' => 'required|exists:shifts,id',
            'keterangan' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Foto wajib diambil.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 5MB.',
            'latitude.required' => 'Lokasi GPS wajib diaktifkan.',
            'latitude.between' => 'Koordinat latitude tidak valid.',
            'longitude.required' => 'Lokasi GPS wajib diaktifkan.',
            'longitude.between' => 'Koordinat longitude tidak valid.',
        ];
    }
}
