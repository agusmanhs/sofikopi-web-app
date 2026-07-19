<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTransaction extends Model
{
    use BelongsToMitra, LogsActivity;

    protected $fillable = [
        'mitra_id',
        'transaction_no',
        'sales_mode',
        'payment_method',
        'subtotal',
        'discount',
        'grand_total',
        'total_hpp',
        'total_cogs',
        'status',
        'user_id',
        'transacted_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_hpp' => 'decimal:2',
        'total_cogs' => 'decimal:2',
        'transacted_at' => 'datetime',
    ];

    /**
     * URL-facing routes resolve transactions by their globally-unique
     * `transaction_no` instead of the numeric id — see
     * PosTransactionService::findForMitra.
     */
    public function getRouteKeyName(): string
    {
        return 'transaction_no';
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
