<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    protected $fillable = [
        'user_id',
        'mitra_id',
        'visit_type',
        'tanggal_kunjungan',
        'espresso_calibration',
        'taste_notes',
        'flow_of_customers',
        'feedback',
        'problem',
        'note',
        'foto_kunjungan',
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
    ];

    // ============== RELATIONSHIPS ==============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mitra()
    {
        return $this->belongsTo(Mitra::class);
    }

    // ============== ACCESSORS ==============

    public function getFotoUrlAttribute()
    {
        if ($this->foto_kunjungan) {
            return asset('storage/' . $this->foto_kunjungan);
        }
        return null;
    }

    // ============== SCOPES ==============

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByMitra($query, $mitraId)
    {
        return $query->where('mitra_id', $mitraId);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('tanggal_kunjungan', [$from, $to]);
    }
}
