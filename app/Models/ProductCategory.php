<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function subCategories()
    {
        return $this->hasMany(ProductSubCategory::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
