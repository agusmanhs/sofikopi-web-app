<?php

namespace App\Models;

use App\Traits\BelongsToMitra;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AkuntansiJournalEntry extends Model
{
    use BelongsToMitra, LogsActivity;

    protected $fillable = [
        'mitra_id',
        'entry_no',
        'entry_date',
        'description',
        'source_type',
        'user_id',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    /**
     * URL-facing routes resolve entries by their globally-unique `entry_no`,
     * the same convention as PosTransaction::transaction_no.
     */
    public function getRouteKeyName(): string
    {
        return 'entry_no';
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AkuntansiJournalLine::class, 'journal_entry_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
