<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransactionItem extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'mitra_product_id',
        'product_name',
        'qty',
        'unit_price',
        'hpp_snapshot',
        'cogs_snapshot',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'hpp_snapshot' => 'decimal:2',
        'cogs_snapshot' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(MitraProduct::class, 'mitra_product_id');
    }
}
