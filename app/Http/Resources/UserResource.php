<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role ? $this->role->name : null,
            'pegawai' => null,
            'absensi_history' => [],
        ];

        if ($this->pegawai) {
            $data['pegawai'] = [
                'id' => $this->pegawai->id,
                'nip' => $this->pegawai->nip,
                'nama_lengkap' => $this->pegawai->nama_lengkap,
                'jabatan' => $this->pegawai->jabatan,
                'divisi' => $this->pegawai->divisi ? $this->pegawai->divisi->nama : null,
                'kantor' => $this->pegawai->kantor ? $this->pegawai->kantor->nama : null,
                'foto_url' => $this->pegawai->foto_url,
                'shift' => $this->pegawai->divisi->shifts->where('is_aktif', true)->count() ? [
                    'id' => $this->pegawai->divisi->shifts->where('is_aktif', true)->first()->id,
                    'nama' => $this->pegawai->divisi->shifts->where('is_aktif', true)->first()->nama,
                    'jam_masuk' => $this->pegawai->divisi->shifts->where('is_aktif', true)->first()->jam_masuk->format('H:i'),
                    'jam_pulang' => $this->pegawai->divisi->shifts->where('is_aktif', true)->first()->jam_pulang->format('H:i'),
                ] : null,
            ];

            // Get last 5 attendance history (summary)
            $data['absensi_history'] = $this->pegawai->absensis()
                ->with(['shift'])
                ->latest('tanggal')
                ->take(5)
                ->get()
                ->map(function ($absen) {
                    return [
                        'id' => $absen->id,
                        'tanggal' => $absen->tanggal->format('Y-m-d'),
                        'jam_masuk' => $absen->jam_masuk ? $absen->jam_masuk->format('H:i') : null,
                        'jam_pulang' => $absen->jam_pulang ? $absen->jam_pulang->format('H:i') : null,
                        'status' => $absen->status,
                        'durasi_kerja' => $absen->durasi_kerja,
                        'foto_masuk_url' => $absen->foto_masuk_url,
                        'foto_pulang_url' => $absen->foto_pulang_url,
                    ];
                });
        }

        return $data;
    }
}
