<?php

namespace App\Http\Requests;

class IzinRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'jenis_izin_id' => 'required|exists:jenis_izins,id',
            'tgl_mulai' => 'required|date|after_or_equal:today',
            'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
            'alasan' => 'required|string|min:5',
            'file_surat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_izin_id.required' => 'Jenis izin wajib dipilih.',
            'jenis_izin_id.exists' => 'Jenis izin tidak ditemukan.',
            'tgl_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tgl_mulai.after_or_equal' => 'Tanggal mulai minimal hari ini.',
            'tgl_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tgl_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'alasan.required' => 'Alasan pengajuan izin wajib diisi.',
            'alasan.min' => 'Alasan minimal 5 karakter.',

            'file_surat.mimes' => 'File surat harus berupa PDF atau gambar.',
            'file_surat.max' => 'Ukuran file maksimal 5MB.',
        ];
    }
}
