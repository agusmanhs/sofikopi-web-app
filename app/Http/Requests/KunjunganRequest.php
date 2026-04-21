<?php

namespace App\Http\Requests;

class KunjunganRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'mitra_id' => 'required|exists:mitras,id',
            'visit_type' => 'required|in:routine,by_request',
            'tanggal_kunjungan' => 'required|date',
            'espresso_calibration' => 'required|string|min:5',
            'taste_notes' => 'required|string|min:5',
            'flow_of_customers' => 'nullable|string',
            'feedback' => 'nullable|string',
            'problem' => 'nullable|string',
            'note' => 'nullable|string',
            'foto_kunjungan' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'user_lat' => 'required|numeric',
            'user_lng' => 'required|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'mitra_id.required' => 'Outlet/Mitra wajib dipilih.',
            'mitra_id.exists' => 'Outlet/Mitra tidak ditemukan.',
            'tanggal_kunjungan.required' => 'Tanggal kunjungan wajib diisi.',
            'espresso_calibration.required' => 'Espresso Calibration wajib diisi.',
            'taste_notes.required' => 'Taste Notes wajib diisi.',
            'foto_kunjungan.required' => 'Foto kunjungan wajib diunggah.',
            'user_lat.required' => 'Titik lokasi (GPS) tidak terdeteksi. Pastikan GPS aktif.',
            'user_lng.required' => 'Titik lokasi (GPS) tidak terdeteksi. Pastikan GPS aktif.',
            'foto_kunjungan.image' => 'File harus berupa gambar.',
            'foto_kunjungan.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
            'foto_kunjungan.max' => 'Ukuran foto maksimal 5MB.',
        ];
    }
}
