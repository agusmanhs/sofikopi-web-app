<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitraProductIngredient extends Model
{
    protected $fillable = [
        'mitra_product_id',
        'mitra_material_id',
        'qty',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MitraProduct::class, 'mitra_product_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MitraMaterial::class, 'mitra_material_id');
    }
}
