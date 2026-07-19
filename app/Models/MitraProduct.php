<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MitraProduct extends Model
{
    use BelongsToMitra, LogsActivity, SoftDeletes;

    protected $fillable = [
        'mitra_id',
        'sku',
        'name',
        'variant',
        'category',
        'sub_category',
        'q_factor',
        'sale_price',
        'status',
    ];

    protected $casts = [
        'q_factor' => 'decimal:4',
        'sale_price' => 'decimal:2',
    ];

    /**
     * URL-facing routes resolve products by `sku`, scoped to their mitra
     * via the service layer (sku is only unique per-mitra, not globally —
     * see MitraProductService::findForMitra, never rely on implicit
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

    /**
     * BOM recipe lines. Callers computing hpp/cogs/margin SHOULD eager load
     * `ingredients.material` to avoid N+1 (falls back to lazy loading otherwise).
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(MitraProductIngredient::class);
    }

    public function posTransactionItems(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    /**
     * Harga Pokok Produksi: sum of each ingredient's qty x material unit price.
     */
    public function getHppAttribute()
    {
        return $this->ingredients->sum(function ($ingredient) {
            return $ingredient->qty * $ingredient->material->harga_satuan;
        });
    }

    /**
     * Cost of goods sold: hpp inflated by the q_factor (overhead/waste margin).
     */
    public function getCogsAttribute()
    {
        return round($this->hpp * (1 + $this->q_factor));
    }

    public function getMarginAttribute()
    {
        return $this->sale_price - $this->cogs;
    }
}
