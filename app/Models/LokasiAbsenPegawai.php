<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LokasiAbsenPegawai extends Model
{
    protected $table = 'lokasi_absen_pegawais';

    protected $fillable = [
        'pegawai_id',
        'kantor_id',
        'is_aktif',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    /**
     * Pegawai
     */
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * Kantor
     */
    public function kantor()
    {
        return $this->belongsTo(Kantor::class);
    }

    /**
     * Scope untuk lokasi aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
