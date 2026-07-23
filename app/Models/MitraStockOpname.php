<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MitraStockOpname extends Model
{
    use BelongsToMitra, LogsActivity;

    protected $fillable = [
        'mitra_id',
        'opname_no',
        'opname_date',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'opname_date' => 'date',
    ];

    /**
     * URL-facing routes resolve opnames by their globally-unique `opname_no`
     * — same rationale as PosTransaction::getRouteKeyName().
     */
    public function getRouteKeyName(): string
    {
        return 'opname_no';
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MitraStockOpnameItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
