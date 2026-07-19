<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MitraPosSetting extends Model
{
    use BelongsToMitra;

    protected $fillable = [
        'mitra_id',
        'monthly_revenue_target',
        'receipt_footer',
    ];

    protected $casts = [
        'monthly_revenue_target' => 'decimal:2',
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }
}
