<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    use LogsActivity;

    protected $fillable = [
        'mitra_category_id',
        'code',
        'pic',
        'name',
        'phone',
        'address',
        'province_code',
        'regency_code',
        'district_code',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(MitraCategory::class, 'mitra_category_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class, 'regency_code', 'code');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    public function getTitikLokasiAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "{$this->latitude}, {$this->longitude}";
        }
        return null;
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
