<?php

namespace App\Services\MitraPos;

/**
 * Per-request holder for the "active" mitra id.
 *
 * Registered as a `scoped()` singleton in AppServiceProvider so its state
 * never leaks between requests (or between jobs in the same worker).
 *
 * Populated early in the request lifecycle by EnsureMitraUser (tenant
 * portal routes) or ResolveMitraScope (admin `/mitra-pos/manage/{mitra}`
 * routes) so that downstream controllers/services never need to re-derive
 * mitra_id from Auth themselves — they read it from here (or receive it
 * as an explicit `$mitraId` parameter, per the "services always take
 * $mitraId explicitly" rule).
 *
 * Fails closed: id() throws if the context was never set, instead of
 * silently returning null/0 and letting a query run unscoped.
 */
class MitraContext
{
    private ?int $mitraId = null;

    public function set(int $mitraId): void
    {
        $this->mitraId = $mitraId;
    }

    public function id(): int
    {
        if ($this->mitraId === null) {
            throw new \RuntimeException('MitraContext has not been set for this request.');
        }

        return $this->mitraId;
    }

    public function has(): bool
    {
        return $this->mitraId !== null;
    }
}
