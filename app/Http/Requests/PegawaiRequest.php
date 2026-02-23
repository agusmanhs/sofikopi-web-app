<?php

namespace App\Http\Requests;

class PegawaiRequest extends BaseRequest
{
    public function rules(): array
    {
        $id = $this->route('pegawai');

        return [
            'user_id' => "required|exists:users,id|unique:pegawais,user_id,{$id}",
            'divisi_id' => 'required|exists:divisis,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'kantor_id' => 'nullable|exists:kantors,id',
            'nip' => "nullable|string|max:50|unique:pegawais,nip,{$id}",
            'nama_lengkap' => 'required|string|max:150',
            'jabatan' => 'nullable|string|max:100',
            'tgl_masuk' => 'nullable|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'gender' => 'required|in:L,P',
            'no_telp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'status_aktif' => 'nullable|boolean',
            'lokasi_absen' => 'nullable|array',
            'lokasi_absen.*' => 'exists:kantors,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Akun user wajib dipilih.',
            'user_id.exists' => 'Akun user tidak ditemukan.',
            'user_id.unique' => 'Akun user ini sudah digunakan oleh pegawai lain.',
            'divisi_id.required' => 'Divisi wajib dipilih.',
            'divisi_id.exists' => 'Divisi tidak ditemukan.',
            'shift_id.required' => 'Shift wajib dipilih. (Buat shift terlebih dahulu di menu Shift Kerja jika belum ada)',
            'shift_id.exists' => 'Shift tidak ditemukan.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nip.unique' => 'NIP sudah digunakan.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
