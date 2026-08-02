<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AkuntansiAccount extends Model
{
    use BelongsToMitra, LogsActivity;

    protected $fillable = [
        'mitra_id',
        'code',
        'parent_code',
        'name',
        'level',
        'account_type',
        'position',
        'is_postable',
        'system_role',
        'is_active',
        'opening_balance',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_postable' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(AkuntansiJournalLine::class);
    }
}
