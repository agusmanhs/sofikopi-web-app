<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class MitraCategory extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mitras()
    {
        return $this->hasMany(Mitra::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
