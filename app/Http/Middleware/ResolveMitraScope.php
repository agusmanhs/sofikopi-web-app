<?php

namespace App\Http\Middleware;

use App\Models\Mitra;
use App\Services\MitraPos\MitraContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the admin-setup routes shaped `/mitra-pos/manage/{mitra}`.
 *
 * Resolves the {mitra} route parameter (works whether Laravel already
 * route-model-bound it to a Mitra instance, or it's still the raw route
 * key value — this middleware runs before SubstituteBindings, so in
 * practice it's always still raw). Resolved via resolveRouteBinding()
 * rather than findOrFail() so it respects Mitra::getRouteKeyName()
 * ('code', not the numeric id).
 * If a mitra user somehow hits an admin route and the {mitra} doesn't
 * match their own mitra_id, abort 403. Internal staff (mitra_id null)
 * pass through freely — their access is gated by check.permission
 * separately. Also pushes the resolved mitra id into MitraContext so
 * admin-side services can read it from route context too.
 */
class ResolveMitraScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeMitra = $request->route('mitra');

        $mitra = $routeMitra instanceof Mitra
            ? $routeMitra
            : (new Mitra())->resolveRouteBinding($routeMitra);

        if (!$mitra) {
            abort(404);
        }

        if (Auth::check() && Auth::user()->mitra_id !== null && Auth::user()->mitra_id !== $mitra->id) {
            abort(403, 'Anda tidak memiliki akses ke mitra ini.');
        }

        app(MitraContext::class)->set($mitra->id);

        return $next($request);
    }
}
