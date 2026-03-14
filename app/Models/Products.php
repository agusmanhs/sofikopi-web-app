<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Products extends Model
{
    use LogsActivity;

    protected $fillable = [
        'product_sub_category_id',
        'sku',
        'name',
        'description',
        'buying_price',
        'selling_price',
        'current_stock',
        'min_stock',
        'unit',
        'netto',
        'gross_weight',
        'attributes',
        'cover',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'attributes' => 'json',
        'buying_price' => 'integer',
        'selling_price' => 'integer',
        'current_stock' => 'integer',
        'min_stock' => 'integer',
        'netto' => 'decimal:2',
        'gross_weight' => 'decimal:2',
    ];

    public function subCategory()
    {
        return $this->belongsTo(ProductSubCategory::class, 'product_sub_category_id');
    }

    public function getCoverUrlAttribute()
    {
        if ($this->cover) {
            return Storage::url($this->cover);
        }
        return asset('assets/img/illustrations/page-pricing-standard.png'); // Placeholder
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }
}
