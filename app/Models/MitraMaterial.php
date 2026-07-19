<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MitraMaterial extends Model
{
    use BelongsToMitra, LogsActivity, SoftDeletes;

    protected $fillable = [
        'mitra_id',
        'product_id',
        'sku',
        'name',
        'category',
        'brand',
        'unit',
        'netto',
        'price_per_pack',
        'current_stock',
        'min_stock',
        'is_active',
    ];

    protected $casts = [
        'netto' => 'decimal:3',
        'price_per_pack' => 'decimal:2',
        'current_stock' => 'decimal:3',
        'min_stock' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    /**
     * URL-facing routes resolve materials by `sku`, scoped to their mitra
     * via the service layer (sku is only unique per-mitra, not globally —
     * see MitraMaterialService::findForMitra, never rely on implicit
     * route-model binding for this column).
     */
    public function getRouteKeyName(): string
    {
        return 'sku';
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(MitraStockMovement::class);
    }

    public function productIngredients(): HasMany
    {
        return $this->hasMany(MitraProductIngredient::class);
    }

    /**
     * Unit price derived from pack price and pack size.
     * e.g. price_per_pack 180.000 / netto 1000 gr = 180/gr.
     */
    public function getHargaSatuanAttribute()
    {
        return $this->netto > 0 ? $this->price_per_pack / $this->netto : 0;
    }
}
