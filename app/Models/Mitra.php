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
