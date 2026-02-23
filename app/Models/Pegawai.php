<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\LogsActivity;

class Pegawai extends Model
{
    use LogsActivity;

    protected $table = 'pegawais';

    protected $fillable = [
        'user_id',
        'divisi_id',
        'shift_id',
        'kantor_id',
        'nip',
        'nama_lengkap',
        'jabatan',
        'tgl_masuk',
        'foto',
        'gender',
        'no_telp',
        'alamat',
        'status_aktif',
    ];

    protected $casts = [
        'tgl_masuk' => 'date',
        'status_aktif' => 'boolean',
    ];

    /**
     * User account
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Divisi pegawai
     */
    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    /**
     * Shift pegawai
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Kantor utama pegawai
     */
    public function kantor()
    {
        return $this->belongsTo(Kantor::class);
    }

    /**
     * Lokasi-lokasi yang bisa digunakan untuk absen (many-to-many)
     */
    public function lokasiAbsen()
    {
        return $this->belongsToMany(Kantor::class, 'lokasi_absen_pegawais')
            ->withPivot('is_aktif')
            ->withTimestamps();
    }

    /**
     * Lokasi absen aktif saja
     */
    public function lokasiAbsenAktif()
    {
        return $this->lokasiAbsen()->wherePivot('is_aktif', true);
    }

    /**
     * Semua absensi pegawai
     */
    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }

    /**
     * Semua izin pegawai
     */
    public function izins()
    {
        return $this->hasMany(Izin::class);
    }

    /**
     * URL foto pegawai
     */
    public function getFotoUrlAttribute()
    {
        if ($this->foto) {
            return Storage::url($this->foto);
        }
        return asset('assets/img/avatars/' . ($this->gender == 'P' ? '8.png' : '1.png'));
    }

    /**
     * Scope untuk pegawai aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('status_aktif', true);
    }

    /**
     * Absensi hari ini
     */
    public function absensiHariIni()
    {
        return $this->absensis()->whereDate('tanggal', today())->first();
    }

    /**
     * Check apakah sudah absen masuk hari ini
     */
    public function sudahAbsenMasukHariIni()
    {
        $absensi = $this->absensiHariIni();
        return $absensi && $absensi->jam_masuk;
    }

    /**
     * Check apakah sudah absen pulang hari ini
     */
    public function sudahAbsenPulangHariIni()
    {
        $absensi = $this->absensiHariIni();
        return $absensi && $absensi->jam_pulang;
    }
}
