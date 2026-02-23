<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Shift extends Model
{
    use LogsActivity;

    protected $fillable = [
        'divisi_id',
        'nama',
        'jam_masuk',
        'jam_pulang',
        'is_aktif',
        'ikut_libur',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
        'ikut_libur' => 'boolean',
        'jam_masuk' => 'datetime:H:i',
        'jam_pulang' => 'datetime:H:i',
    ];

    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    public function pegawais()
    {
        return $this->hasMany(Pegawai::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_aktif', true);
    }

    public function getDurasiMenitAttribute()
    {
        if (!$this->jam_masuk || !$this->jam_pulang) {
            return 0;
        }

        $masuk = \Carbon\Carbon::parse($this->jam_masuk->format('H:i:s'));
        $pulang = \Carbon\Carbon::parse($this->jam_pulang->format('H:i:s'));

        if ($pulang->lt($masuk)) {
            $pulang->addDay();
        }

        return $masuk->diffInMinutes($pulang);
    }
}
