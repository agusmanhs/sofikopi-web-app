<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class JadwalKerja extends Model
{
    use LogsActivity;

    protected $fillable = [
        'pegawai_id',
        'day_of_week',
        'shift_id',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    /**
     * Pegawai pemilik pola jadwal ini
     */
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * Shift yang dijadwalkan pada hari ini (null = libur)
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
