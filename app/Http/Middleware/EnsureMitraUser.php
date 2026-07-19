<?php

namespace App\Http\Middleware;

use App\Services\MitraPos\MitraContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the tenant portal (`mitra-pos/*` routes).
 *
 * Only authenticated users with a mitra_id may pass. Runs BEFORE
 * check.permission in the route middleware stack so that a super-admin's
 * seeded pivot permissions can never grant tenant-context POS access —
 * they simply have no mitra_id and get 403'd here first.
 */
class EnsureMitraUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || Auth::user()->mitra_id === null) {
            abort(403, 'Halaman ini hanya untuk pengguna mitra.');
        }

        app(MitraContext::class)->set(Auth::user()->mitra_id);

        return $next($request);
    }
}
