<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbsensiKoreksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->role && $this->user()->role->slug === 'super-admin';
    }

    public function rules(): array
    {
        $common = [
            'status' => ['in:Tepat Waktu,Terlambat'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
        ];

        if ($this->isMethod('POST')) {
            return $common + [
                'pegawai_id' => ['required', 'integer', 'exists:pegawais,id'],
                'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'shift_id' => ['required', 'integer', 'exists:shifts,id'],
                'jam_masuk' => ['required', 'date_format:H:i,H:i:s'],
                'jam_pulang' => ['nullable', 'date_format:H:i,H:i:s'],
                'status' => ['required', 'in:Tepat Waktu,Terlambat'],
            ];
        }

        return array_merge($common, [
            'jam_masuk' => ['sometimes', 'required', 'date_format:H:i,H:i:s'],
            'jam_pulang' => ['sometimes', 'nullable', 'date_format:H:i,H:i:s'],
            'status' => ['sometimes', 'required', 'in:Tepat Waktu,Terlambat'],
        ]);
    }
}
