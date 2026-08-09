<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class JadwalOverride extends Model
{
    use LogsActivity;

    protected $fillable = [
        'pegawai_id',
        'tanggal',
        'shift_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Pegawai pemilik override ini
     */
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * Shift yang dijadwalkan pada tanggal ini (null = libur)
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
