<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MitraStockMovement extends Model
{
    use BelongsToMitra;

    protected $fillable = [
        'mitra_id',
        'mitra_material_id',
        'type',
        'qty',
        'unit_cost',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'balance_after' => 'decimal:3',
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(MitraMaterial::class, 'mitra_material_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
