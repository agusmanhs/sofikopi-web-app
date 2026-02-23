<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Kantor extends Model
{
    use LogsActivity;

    protected $table = 'kantors';

    protected $fillable = [
        'nama',
        'kode',
        'alamat',
        'titik_lokasi',
        'radius_meter',
        'is_aktif',
    ];

    protected $casts = [
        'radius_meter' => 'integer',
        'is_aktif' => 'boolean',
    ];

    /**
     * Get latitude from titik_lokasi
     */
    public function getLatitudeAttribute()
    {
        if (!$this->titik_lokasi) return null;
        $parts = explode(',', $this->titik_lokasi);
        return trim($parts[0] ?? null);
    }

    /**
     * Get longitude from titik_lokasi
     */
    public function getLongitudeAttribute()
    {
        if (!$this->titik_lokasi) return null;
        $parts = explode(',', $this->titik_lokasi);
        return trim($parts[1] ?? null);
    }

    /**
     * Pegawai yang berkantor di sini (kantor utama)
     */
    public function pegawais()
    {
        return $this->hasMany(Pegawai::class);
    }

    /**
     * Pegawai yang bisa absen di lokasi ini (many-to-many)
     */
    public function pegawaiAbsen()
    {
        return $this->belongsToMany(Pegawai::class, 'lokasi_absen_pegawais')
            ->withPivot('is_aktif')
            ->withTimestamps();
    }

    /**
     * Scope untuk kantor aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }
}
