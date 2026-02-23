<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class JenisIzin extends Model
{
    use LogsActivity;

    protected $table = 'jenis_izins';

    protected $fillable = [
        'nama',
        'kode',
        'butuh_surat',
        'max_hari',
        'keterangan',
        'is_aktif',
    ];

    protected $casts = [
        'butuh_surat' => 'boolean',
        'max_hari' => 'integer',
        'is_aktif' => 'boolean',
    ];

    /**
     * Izin yang menggunakan jenis ini
     */
    public function izins()
    {
        return $this->hasMany(Izin::class);
    }

    /**
     * Scope untuk jenis izin aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
