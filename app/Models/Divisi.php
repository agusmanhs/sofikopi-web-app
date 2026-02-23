<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Divisi extends Model
{
    use LogsActivity;

    protected $table = 'divisis';

    protected $fillable = [
        'kode',
        'nama',
        'toleransi_terlambat',
        'is_aktif',
    ];

    protected $casts = [
        'toleransi_terlambat' => 'integer',
        'is_aktif' => 'boolean',
    ];

    /**
     * Pegawai yang ada di divisi ini
     */
    public function pegawais()
    {
        return $this->hasMany(Pegawai::class);
    }

    /**
     * Shift yang ada di divisi ini
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Scope untuk divisi aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
