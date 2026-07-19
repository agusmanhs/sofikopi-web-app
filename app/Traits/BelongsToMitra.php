<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Applies hard tenant isolation by mitra_id.
 *
 * - Global scope: automatically constrains queries to the authenticated
 *   mitra user's own mitra_id. No-op for internal staff (mitra_id null)
 *   and no-op when Auth is unavailable (console/queue/seeders).
 * - creating hook: auto-fills mitra_id for mitra users when not already set.
 * - scopeForMitra(): explicit admin-path escape hatch that removes the
 *   global scope and filters by a given mitra id.
 */
trait BelongsToMitra
{
    public static function bootBelongsToMitra(): void
    {
        static::addGlobalScope('mitra', function (Builder $builder) {
            if (Auth::check() && Auth::user()->mitra_id !== null) {
                $builder->where(
                    $builder->getModel()->getTable() . '.mitra_id',
                    Auth::user()->mitra_id
                );
            }
        });

        static::creating(function (Model $model) {
            if (is_null($model->mitra_id) && Auth::check() && Auth::user()->mitra_id !== null) {
                $model->mitra_id = Auth::user()->mitra_id;
            }
        });
    }

    public function scopeForMitra(Builder $query, int $mitraId): Builder
    {
        return $query->withoutGlobalScope('mitra')->where('mitra_id', $mitraId);
    }
}
