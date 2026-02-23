<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'properties' => 'array',
    ];

    /**
     * User yang melakukan aksi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Subject/Entity yang terkena aksi (polymorphic)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope untuk filter berdasarkan action
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope untuk filter berdasarkan user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope untuk filter berdasarkan subject type
     */
    public function scopeForModel($query, string $modelClass)
    {
        return $query->where('subject_type', $modelClass);
    }

    /**
     * Scope untuk filter tanggal
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted action label
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Dibuat',
            'updated' => 'Diperbarui',
            'deleted' => 'Dihapus',
            'login' => 'Login',
            'logout' => 'Logout',
            'viewed' => 'Dilihat',
            'exported' => 'Diekspor',
            'imported' => 'Diimpor',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get action badge color for UI
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            'login' => 'info',
            'logout' => 'secondary',
            'viewed' => 'primary',
            default => 'dark',
        };
    }
}
