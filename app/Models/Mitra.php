<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * URL-facing routes resolve mitras by their unique `code`, not the
     * numeric id — see ResolveMitraScope for how the admin-setup routes
     * (`mitra-pos/manage/{mitra}`) consume this.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(MitraMaterial::class);
    }

    public function mitraProducts(): HasMany
    {
        return $this->hasMany(MitraProduct::class);
    }

    public function posTransactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function posSetting(): HasOne
    {
        return $this->hasOne(MitraPosSetting::class);
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
