<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitraStockOpnameItem extends Model
{
    protected $fillable = [
        'mitra_stock_opname_id',
        'mitra_material_id',
        'system_qty',
        'physical_qty',
        'difference',
        'unit_cost',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'physical_qty' => 'decimal:3',
        'difference' => 'decimal:3',
        'unit_cost' => 'decimal:4',
    ];

    public function opname(): BelongsTo
    {
        return $this->belongsTo(MitraStockOpname::class, 'mitra_stock_opname_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MitraMaterial::class, 'mitra_material_id');
    }

    /**
     * Rupiah value of the discrepancy — sheet PERSEDIAAN's "SELISIH NILAI".
     */
    public function getDifferenceValueAttribute()
    {
        return $this->difference * $this->unit_cost;
    }
}
