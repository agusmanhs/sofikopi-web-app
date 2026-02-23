<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\LogsActivity;

class Absensi extends Model
{
    use LogsActivity;

    protected $table = 'absensis';

    protected $fillable = [
        'pegawai_id',
        'shift_id',
        'tanggal',
        'jam_masuk',
        'foto_masuk',
        'latitude_masuk',
        'longitude_masuk',
        'lokasi_masuk',
        'device_masuk',
        'jam_pulang',
        'foto_pulang',
        'latitude_pulang',
        'longitude_pulang',
        'lokasi_pulang',
        'device_pulang',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_masuk' => 'datetime:H:i:s',
        'jam_pulang' => 'datetime:H:i:s',
        'latitude_masuk' => 'decimal:8',
        'longitude_masuk' => 'decimal:8',
        'latitude_pulang' => 'decimal:8',
        'longitude_pulang' => 'decimal:8',
    ];

    /**
     * Pegawai pemilik absensi
     */
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * Shift yang diambil
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * URL foto masuk
     */
    public function getFotoMasukUrlAttribute()
    {
        if ($this->foto_masuk) {
            return Storage::disk('public')->url($this->foto_masuk);
        }
        return null;
    }

    /**
     * URL foto pulang
     */
    public function getFotoPulangUrlAttribute()
    {
        if ($this->foto_pulang) {
            return Storage::disk('public')->url($this->foto_pulang);
        }
        return null;
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeTanggal($query, $tanggal)
    {
        return $query->whereDate('tanggal', $tanggal);
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk absensi hari ini
     */
    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal', today());
    }

    /**
     * Scope untuk absensi bulan ini
     */
    public function scopeBulanIni($query)
    {
        return $query->whereMonth('tanggal', now()->month)
                     ->whereYear('tanggal', now()->year);
    }

    /**
     * Check apakah sudah absen masuk
     */
    public function getSudahMasukAttribute()
    {
        return !is_null($this->jam_masuk);
    }

    /**
     * Check apakah sudah absen pulang
     */
    public function getSudahPulangAttribute()
    {
        return !is_null($this->jam_pulang);
    }

    public function getDurasiShiftMenitAttribute()
    {
        if (!$this->jam_masuk || !$this->jam_pulang || !$this->shift) {
            return 0;
        }

        return $this->shift->durasi_menit;
    }

    /**
     * Format durasi shift (berdasarkan shift yang diambil)
     */
    public function getDurasiShiftAttribute()
    {
        $minutes = $this->durasi_shift_menit;
        if ($minutes === 0) return '-';

        $hours = floor($minutes / 60);
        $remMinutes = $minutes % 60;

        return "{$hours} Jam " . ($remMinutes > 0 ? "{$remMinutes} Menit" : "");
    }

    public function getDurasiKerjaMenitAttribute()
    {
        if (!$this->jam_masuk || !$this->jam_pulang || !$this->shift) {
            return 0;
        }

        // 1. Ambil waktu shift
        $shiftMasuk = \Carbon\Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->shift->jam_masuk->format('H:i:s'));
        $shiftPulang = \Carbon\Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->shift->jam_pulang->format('H:i:s'));

        if ($shiftPulang->lt($shiftMasuk)) {
            $shiftPulang->addDay();
        }

        // 2. Ambil waktu absen
        $absenMasuk = \Carbon\Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->jam_masuk->format('H:i:s'));
        $absenPulang = \Carbon\Carbon::parse($this->tanggal->format('Y-m-d') . ' ' . $this->jam_pulang->format('H:i:s'));

        // Handle case if absen pulang is on the next day relative to absen masuk
        if ($absenPulang->lt($absenMasuk)) {
            $absenPulang->addDay();
        }

        // Aturan:
        // Start: Ambil yang paling akhir antara jam masuk shift atau jam absen masuk (jika terlambat)
        $start = $absenMasuk->gt($shiftMasuk) ? $absenMasuk : $shiftMasuk;

        // End: Ambil yang paling awal antara jam pulang shift atau jam absen pulang (jika pulang awal)
        $end = $absenPulang->lt($shiftPulang) ? $absenPulang : $shiftPulang;

        if ($end->lt($start)) {
            return 0;
        }

        return $start->diffInMinutes($end);
    }

    /**
     * Hitung durasi kerja actual (log masuk - log pulang)
     */
    public function getDurasiKerjaAttribute()
    {
        $minutes = (int) $this->durasi_kerja_menit; // Cast to integer to remove decimals
        if ($minutes === 0) return '-';

        $hours = floor($minutes / 60);
        $remMinutes = $minutes % 60;

        return "{$hours} Jam " . ($remMinutes > 0 ? "{$remMinutes} Menit" : "");
    }
}
