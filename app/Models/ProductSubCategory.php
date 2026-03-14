<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ProductSubCategory extends Model
{
    use LogsActivity;

    protected $fillable = [
        'product_category_id',
        'name',
        'template_fields',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'template_fields' => 'json',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function products()
    {
        return $this->hasMany(Products::class, 'product_sub_category_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
